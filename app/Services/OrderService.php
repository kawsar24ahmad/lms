<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Order placement, manual payment recording/verification and course-access
 * granting. All amounts are computed server-side from the course records —
 * client-submitted prices and payment statuses are never trusted — and each
 * order item keeps a price snapshot so later course price or discount
 * changes leave historical orders untouched.
 */
class OrderService
{
    /**
     * Place an order for the given courses in a single database
     * transaction. Free orders grant course access immediately; anything
     * unpaid stays pending until a payment is verified.
     *
     * @param  list<int|string>  $courseIds
     */
    public function placeOrder(User $student, array $courseIds, float $orderDiscount = 0.0): Order
    {
        $courseIds = array_values(array_unique(array_map('intval', $courseIds)));

        if ($courseIds === []) {
            throw ValidationException::withMessages([
                'courses' => 'Select at least one course to checkout.',
            ]);
        }

        $courses = Course::query()
            ->published()
            ->whereIn('id', $courseIds)
            ->get()
            ->keyBy('id');

        $invalid = collect($courseIds)
            ->reject(fn (int $id) => $courses->has($id));

        if ($invalid->isNotEmpty()) {
            throw ValidationException::withMessages([
                'courses' => 'Some selected courses are unavailable or no longer published.',
            ]);
        }

        $alreadyEnrolledCourseIds = Enrollment::query()
            ->active()
            ->where('student_id', $student->getKey())
            ->whereIn('course_id', $courseIds)
            ->pluck('course_id');

        if ($alreadyEnrolledCourseIds->isNotEmpty()) {
            $titles = $alreadyEnrolledCourseIds
                ->map(fn (int $id) => $courses->get($id)->title ?? "Course #$id")
                ->implode(', ');

            throw ValidationException::withMessages([
                'courses' => "Already actively enrolled in: $titles.",
            ]);
        }

        return DB::transaction(function () use ($student, $courses, $orderDiscount): Order {
            /** @var array<int, array{id: int, originalCents: int, finalCents: int}> $itemQuotes */
            $itemQuotes = [];

            foreach ($courses as $course) {
                $itemQuotes[] = [
                    'id' => $course->getKey(),
                    'originalCents' => self::toCents($course->price),
                    'finalCents' => self::toCents($course->effectivePrice()),
                ];
            }

            $totalCents = array_sum(array_column($itemQuotes, 'originalCents'));
            $courseDiscountCents = array_sum(array_map(
                fn (array $quote): int => $quote['originalCents'] - $quote['finalCents'],
                $itemQuotes,
            ));

            $discountCents = min(
                self::toCents($orderDiscount),
                min(max(0, $totalCents - $courseDiscountCents), $totalCents),
            );

            $finalCents = max(0, $totalCents - $courseDiscountCents - $discountCents);

            $order = Order::query()->create([
                'student_id' => $student->getKey(),
                'number' => $this->generateOrderNumber(),
                'total_amount' => self::fromCents($totalCents),
                'discount_amount' => self::fromCents($courseDiscountCents + $discountCents),
                'final_amount' => self::fromCents($finalCents),
                'paid_amount' => 0,
                'payment_status' => self::paymentStatusFor($finalCents, 0),
                'status' => $finalCents <= 0 ? 'completed' : 'pending',
            ]);

            $order->items()->createMany(array_map(
                fn (array $quote): array => [
                    'course_id' => $quote['id'],
                    'original_price' => self::fromCents($quote['originalCents']),
                    'discount_amount' => self::fromCents($quote['originalCents'] - $quote['finalCents']),
                    'final_price' => self::fromCents($quote['finalCents']),
                ],
                $itemQuotes,
            ));

            if ($finalCents <= 0) {
                $order->items->each(fn (OrderItem $item) => $this->grantEnrollment($order, $item));
            }

            return $order->refresh();
        });
    }

    /**
     * Record a manual payment for the order. The payment starts out pending
     * and contributes nothing to the paid balance until it is verified.
     */
    public function recordPayment(Order $order, float $amount, ?string $paymentMethod = null, ?string $transactionId = null): Payment
    {
        $amountCents = self::toCents($amount);

        if ($amountCents <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'The payment amount must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($order, $amountCents, $paymentMethod, $transactionId): Payment {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->first();

            if ($lockedOrder->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'order' => 'Payments cannot be recorded on a cancelled order.',
                ]);
            }

            if ($lockedOrder->status === 'completed' && $lockedOrder->dueAmount() <= 0) {
                throw ValidationException::withMessages([
                    'order' => 'This order is already fully paid.',
                ]);
            }

            $dueCents = self::toCents($lockedOrder->dueAmount());

            if ($amountCents > $dueCents) {
                throw ValidationException::withMessages([
                    'amount' => 'The payment amount exceeds the amount still due on this order.',
                ]);
            }

            return Payment::query()->create([
                'order_id' => $lockedOrder->getKey(),
                'amount' => self::fromCents($amountCents),
                'payment_method' => $paymentMethod,
                'transaction_id' => $transactionId,
                'status' => Payment::STATUS_PENDING,
            ]);
        });
    }

    /**
     * Approve a pending (or manually flagged) payment. Idempotent: repeat
     * callbacks and repeated approvals neither duplicate the payment nor
     * create duplicate enrollments. Successful payments move the paid
     * balance; once the order reaches its final amount, course access is
     * granted to every eligible line item.
     */
    public function verifyPayment(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment): Payment {
            $lockedPayment = Payment::query()
                ->whereKey($payment->getKey())
                ->lockForUpdate()
                ->first();

            if ($lockedPayment->isSucceeded()) {
                return $lockedPayment;
            }

            $paidAt = now();

            $lockedPayment->forceFill([
                'status' => Payment::STATUS_SUCCEEDED,
                'paid_at' => $paidAt,
            ])->save();

            $this->recalculateOrder($this->lockedOrderOf($lockedPayment), $paidAt);

            return $lockedPayment->refresh();
        });
    }

    /**
     * Mark a payment as failed so it does not count toward the paid balance.
     */
    public function rejectPayment(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment): Payment {
            $lockedPayment = Payment::query()
                ->whereKey($payment->getKey())
                ->lockForUpdate()
                ->first();

            if ($lockedPayment->isSucceeded()) {
                throw ValidationException::withMessages([
                    'payment' => 'A verified payment cannot be marked as failed. Reverse it instead.',
                ]);
            }

            $lockedPayment->forceFill([
                'status' => Payment::STATUS_FAILED,
                'paid_at' => null,
            ])->save();

            $this->recalculateOrder($this->lockedOrderOf($lockedPayment), null);

            return $lockedPayment->refresh();
        });
    }

    /**
     * The order behind a payment, locked for a consistent recalculation.
     * Cancelled orders are never recalculated or granted access.
     */
    private function lockedOrderOf(Payment $payment): Order
    {
        $order = Order::query()
            ->whereKey($payment->order_id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($order->status === 'cancelled') {
            throw ValidationException::withMessages([
                'order' => 'This order was cancelled; its payments can no longer be processed.',
            ]);
        }

        return $order;
    }

    /**
     * Cancel an order that is still pending. Cancelled orders never grant
     * course access and are rejected when new payments are recorded.
     */
    public function cancelOrder(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->first();

            if ($lockedOrder->status === 'completed') {
                throw ValidationException::withMessages([
                    'order' => 'A fully paid order cannot be cancelled. Reverse its payments instead.',
                ]);
            }

            $lockedOrder->forceFill(['status' => 'cancelled'])->save();

            return $lockedOrder;
        });
    }

    /**
     * Recompute the paid balance, payment status and order status from the
     * verified payment records only, granting course access when the order
     * is fully covered.
     */
    private function recalculateOrder(Order $order, ?\DateTimeInterface $paidAt): Order
    {
        $paidCents = min(
            self::toCents((float) $order->payments()
                ->where('status', Payment::STATUS_SUCCEEDED)
                ->sum('amount')),
            self::toCents((float) $order->final_amount),
        );

        $paidCents = max(0, $paidCents);

        $order->forceFill([
            'paid_amount' => self::fromCents($paidCents),
            'payment_status' => self::paymentStatusFor(self::toCents((float) $order->final_amount), $paidCents),
            'status' => $paidCents >= self::toCents((float) $order->final_amount) ? 'completed' : 'pending',
        ])->save();

        if ((float) $order->final_amount > 0 && $paidCents >= self::toCents((float) $order->final_amount)) {
            $order->items->each(fn (OrderItem $item) => $this->grantEnrollment($order, $item, $paidAt));
        }

        return $order;
    }

    /**
     * Grant course access for the course of the order item. Idempotent: the
     * unique (student, course) constraint on enrollments plus firstOrCreate
     * make repeated grants and duplicate payment callbacks safe.
     */
    private function grantEnrollment(Order $order, OrderItem $item, ?\DateTimeInterface $enrolledAt = null): Enrollment
    {
        return Enrollment::query()->firstOrCreate(
            [
                'student_id' => $order->student_id,
                'course_id' => $item->course_id,
            ],
            [
                'order_item_id' => $item->getKey(),
                'status' => 'active',
                'enrolled_at' => $enrolledAt ?? now(),
            ],
        );
    }

    /**
     * Derive the order payment status from the final and paid amounts.
     */
    private static function paymentStatusFor(int $finalCents, int $paidCents): string
    {
        return match (true) {
            $finalCents <= 0 || $finalCents - $paidCents <= 0 => 'paid',
            $paidCents > 0 => 'partial',
            default => 'pending',
        };
    }

    /**
     * A collision-safe, human-readable order number.
     */
    private function generateOrderNumber(): string
    {
        return 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }

    /**
     * Convert a decimal amount into integer cents for exact math.
     */
    private static function toCents(mixed $value): int
    {
        return (int) round(max(0.0, (float) $value) * 100);
    }

    /**
     * Convert integer cents back into a two-decimal amount.
     */
    private static function fromCents(int $cents): float
    {
        return round($cents / 100, 2);
    }
}

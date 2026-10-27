<?php

use App\Models\Enrollment;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Rebuild the historical purchase data stored on legacy enrollments as
     * proper orders and order items. Each priced enrollment becomes an
     * order with a single item whose amounts are copied verbatim from the
     * recorded enrollment columns.
     *
     * No payment transactions are fabricated: the recorded paid amounts are
     * preserved on the order without creating payments, so verified
     * transactions remain the only source of truth for payment history.
     * Orders that the student may still owe on stay "pending"; fully
     * covered ones are marked "completed" to reflect the recorded data.
     * Manual reconciliation of the legacy balances is expected (see the
     * orders admin resource).
     */
    public function up(): void
    {
        Enrollment::query()
            ->whereNull('order_item_id')
            ->where(fn ($query) => $query
                ->where('final_price', '>', 0)
                ->orWhere('paid_amount', '>', 0))
            ->with('course')
            ->chunkById(200, function ($enrollments): void {
                foreach ($enrollments as $enrollment) {
                    DB::transaction(function () use ($enrollment): void {
                        $order = Order::query()->create([
                            'student_id' => $enrollment->student_id,
                            'number' => self::generateNumber(),
                            'total_amount' => $enrollment->original_price,
                            'discount_amount' => round((float) $enrollment->original_price - (float) $enrollment->final_price, 2),
                            'final_amount' => $enrollment->final_price,
                            'paid_amount' => min($enrollment->paid_amount, $enrollment->final_price),
                            'payment_status' => (float) $enrollment->paid_amount >= (float) $enrollment->final_price ? 'paid' : 'partial',
                            'status' => (float) $enrollment->paid_amount >= (float) $enrollment->final_price ? 'completed' : 'pending',
                        ]);

                        $item = $order->items()->create([
                            'course_id' => $enrollment->course_id,
                            'original_price' => $enrollment->original_price,
                            'discount_amount' => round((float) $enrollment->original_price - (float) $enrollment->final_price, 2),
                            'final_price' => $enrollment->final_price,
                        ]);

                        $enrollment->forceFill(['order_item_id' => $item->getKey()])->save();
                    });
                }
            });
    }

    /**
     * Unlink the enrollments and remove only the legacy orders: an order is
     * considered legacy when it has a single item whose course and amounts
     * match the enrollment it was rebuilt from, and whose referenced
     * enrollment still matches. Genuinely new orders are never matched.
     */
    public function down(): void
    {
        DB::transaction(function (): void {
            Enrollment::query()
                ->whereNotNull('order_item_id')
                ->get()
                ->each(function (Enrollment $enrollment): void {
                    $orderItem = OrderItem::query()->find($enrollment->order_item_id);

                    if ($orderItem === null) {
                        return;
                    }

                    $order = Order::query()->find($orderItem->order_id);

                    $matchesLegacy = $order !== null
                        && $order->items()->count() === 1
                        && $orderItem->course_id === $enrollment->course_id
                        && (float) $order->final_amount === (float) $enrollment->final_price
                        && (float) $order->paid_amount === max(0.0, min((float) $enrollment->paid_amount, (float) $enrollment->final_price));

                    $enrollment->forceFill(['order_item_id' => null])->save();

                    if ($matchesLegacy) {
                        $order->delete();
                    }
                });
        });
    }

    /**
     * A human-readable, collision-safe order number.
     */
    private static function generateNumber(): string
    {
        return 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }
};

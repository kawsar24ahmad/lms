<?php

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Policies\OrderPolicy;
use App\Policies\PaymentPolicy;
use App\Services\OrderService;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

function placeOrderFor(User $student, array $courses, float $orderDiscount = 0.0): Order
{
    return app(OrderService::class)->placeOrder(
        $student,
        collect($courses)->map(fn (Course $course) => $course->getKey())->all(),
        $orderDiscount,
    );
}

test('a single course can be purchased in one order with a price snapshot', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create(['price' => 100]);

    $order = placeOrderFor($student, [$course]);

    expect((float) $order->total_amount)->toBe(100.0)
        ->and((float) $order->discount_amount)->toBe(0.0)
        ->and((float) $order->final_amount)->toBe(100.0)
        ->and((float) $order->paid_amount)->toBe(0.0)
        ->and($order->payment_status)->toBe('pending')
        ->and($order->status)->toBe('pending')
        ->and($order->items->count())->toBe(1)
        ->and((float) $order->items->first()->final_price)->toBe(100.0)
        ->and(Enrollment::count())->toBe(0);
});

test('multiple courses can be purchased in a single order', function () {
    $student = User::factory()->create();
    $courses = Course::factory()->published()->count(3)->create(['price' => 40]);

    $order = placeOrderFor($student, $courses->all());

    expect($order->items->count())->toBe(3)
        ->and((float) $order->total_amount)->toBe(120.0)
        ->and((float) $order->final_amount)->toBe(120.0);
});

test('course-level discounts are snapshotted on the order item', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create([
        'price' => 100,
        'discount_type' => Course::DISCOUNT_TYPE_PERCENT,
        'discount_amount' => 15,
    ]);

    $order = placeOrderFor($student, [$course]);

    $item = $order->items->first();

    expect((float) $item->original_price)->toBe(100.0)
        ->and((float) $item->discount_amount)->toBe(15.0)
        ->and((float) $item->final_price)->toBe(85.0)
        ->and((float) $order->discount_amount)->toBe(15.0)
        ->and((float) $order->final_amount)->toBe(85.0);
});

test('an order-level discount reduces the final amount without changing item snapshots', function () {
    $student = User::factory()->create();
    $courses = Course::factory()->published()->count(2)->create(['price' => 100]);

    $order = placeOrderFor($student, $courses->all(), 50);

    expect((float) $order->total_amount)->toBe(200.0)
        ->and((float) $order->final_amount)->toBe(150.0)
        ->and($order->items->first()->final_price)->toBe('100.00')
        ->and($order->dueAmount())->toBe(150.0);
});

test('an order-level discount is capped at the remaining total', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create(['price' => 60]);

    $order = placeOrderFor($student, [$course], 500);

    expect((float) $order->final_amount)->toBe(0.0)
        ->and($order->payment_status)->toBe('paid')
        ->and(Enrollment::count())->toBe(1);
});

test('free courses are enrolled immediately without any payment', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->free()->create();

    $order = placeOrderFor($student, [$course]);

    expect((float) $order->final_amount)->toBe(0.0)
        ->and($order->payment_status)->toBe('paid')
        ->and($order->status)->toBe('completed')
        ->and($order->payments()->count())->toBe(0)
        ->and(Enrollment::count())->toBe(1)
        ->and($order->enrollments->first()->status)->toBe('active')
        ->and($order->enrollments->first()->enrolled_at)->not->toBeNull();
});

test('a fully verified order payment grants access to all eligible courses', function () {
    $student = User::factory()->create();
    $courses = Course::factory()->published()->count(2)->create(['price' => 50]);
    $order = placeOrderFor($student, $courses->all());

    $payment = app(OrderService::class)->recordPayment($order, 100, 'bank_transfer', 'TX-1');
    app(OrderService::class)->verifyPayment($payment);

    $order->refresh();

    $order->items->each(fn ($item) => $item->load('enrollment'));

    expect((float) $order->paid_amount)->toBe(100.0)
        ->and($order->payment_status)->toBe('paid')
        ->and($order->status)->toBe('completed')
        ->and($order->payments()->where('status', Payment::STATUS_SUCCEEDED)->count())->toBe(1)
        ->and($order->enrollments()->count())->toBe(2)
        ->and($order->enrollments()->whereNull('order_item_id')->count())->toBe(0)
        ->and(Enrollment::query()->whereNull('enrolled_at')->count())->toBe(0);
});

test('partial payments leave an outstanding balance and no enrollments', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create(['price' => 100]);
    $order = placeOrderFor($student, [$course]);

    $payment = app(OrderService::class)->recordPayment($order, 40);
    app(OrderService::class)->verifyPayment($payment);

    $order->refresh();

    expect((float) $order->paid_amount)->toBe(40.0)
        ->and($order->dueAmount())->toBe(60.0)
        ->and($order->payment_status)->toBe('partial')
        ->and($order->status)->toBe('pending')
        ->and(Enrollment::count())->toBe(0);

    $rest = app(OrderService::class)->recordPayment($order, 60);
    app(OrderService::class)->verifyPayment($rest);

    $order->refresh();

    expect((float) $order->paid_amount)->toBe(100.0)
        ->and($order->status)->toBe('completed')
        ->and(Enrollment::count())->toBe(1);
});

test('failed and pending payments do not count toward the paid amount', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create(['price' => 100]);
    $order = placeOrderFor($student, [$course]);

    $pending = app(OrderService::class)->recordPayment($order, 25);
    $failed = app(OrderService::class)->recordPayment($order, 30);
    app(OrderService::class)->rejectPayment($failed);
    app(OrderService::class)->verifyPayment($pending);

    $order->refresh();

    expect((float) $order->paid_amount)->toBe(25.0)
        ->and($order->payment_status)->toBe('partial')
        ->and(Enrollment::count())->toBe(0)
        ->and($failed->refresh()->status)->toBe('failed')
        ->and($order->dueAmount())->toBe(75.0);
});

test('duplicate payment verification callbacks are idempotent', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create(['price' => 100]);
    $order = placeOrderFor($student, [$course]);

    $payment = app(OrderService::class)->recordPayment($order, 100, 'bank_transfer', 'TX-DUP');
    $orders = app(OrderService::class);

    $verified = $orders->verifyPayment($payment);
    $again = $orders->verifyPayment($payment);

    expect($again->getKey())->toBe($verified->getKey())
        ->and($order->payments()->where('status', Payment::STATUS_SUCCEEDED)->count())->toBe(1)
        ->and(Enrollment::count())->toBe(1);
});

test('a transaction reference cannot be reused across payments', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create(['price' => 100]);
    $order = placeOrderFor($student, [$course]);

    app(OrderService::class)->recordPayment($order, 10, null, 'TX-4242');

    $order->refresh();

    expect(fn () => app(OrderService::class)->recordPayment($order, 10, null, 'TX-4242'))
        ->toThrow(QueryException::class);
});

test('payments cannot exceed what is still due on the order', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create(['price' => 100]);
    $order = placeOrderFor($student, [$course]);

    expect(fn () => app(OrderService::class)->recordPayment($order, 100.01))
        ->toThrow(ValidationException::class);

    // An already fully-paid order does not accept more money.
    $payment = app(OrderService::class)->recordPayment($order, 100);
    app(OrderService::class)->verifyPayment($payment);

    expect(fn () => app(OrderService::class)->recordPayment($order->refresh(), 0.01))
        ->toThrow(ValidationException::class);
});

test('several verified payments on one order grant only a single enrollment per course', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create(['price' => 100]);
    $orders = app(OrderService::class);
    $order = placeOrderFor($student, [$course]);

    $first = $orders->recordPayment($order, 60, null, 'TX-A');
    $second = $orders->recordPayment($order, 40, null, 'TX-B');

    $orders->verifyPayment($first);
    $orders->verifyPayment($second);
    $orders->verifyPayment($first->fresh());

    $order->refresh();

    expect($order->payments()->where('status', Payment::STATUS_SUCCEEDED)->count())->toBe(2)
        ->and((float) $order->paid_amount)->toBe(100.0)
        ->and(Enrollment::count())->toBe(1)
        ->and($order->status)->toBe('completed');
});

test('an already active enrollment blocks a duplicate course purchase', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create(['price' => 50]);
    $course->enroll($student);

    expect(fn () => placeOrderFor($student, [$course]))
        ->toThrow(ValidationException::class);
});

test('unpublished and unknown courses are rejected and nothing is stored', function () {
    $student = User::factory()->create();
    $draft = Course::factory()->create(['price' => 50]);

    expect(fn () => placeOrderFor($student, [$draft]))
        ->toThrow(ValidationException::class);

    expect(fn () => app(OrderService::class)->placeOrder($student, [999999]))
        ->toThrow(ValidationException::class);

    expect(fn () => app(OrderService::class)->placeOrder($student, []))
        ->toThrow(ValidationException::class);

    expect(Order::count())->toBe(0)
        ->and(Enrollment::count())->toBe(0);
});

test('duplicate courses inside a single request become a single item', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create(['price' => 70]);

    $order = app(OrderService::class)->placeOrder($student, [$course->id, $course->id, $course->id]);

    expect($order->items->count())->toBe(1)
        ->and((float) $order->final_amount)->toBe(70.0);
});

test('order item snapshots preserve historical prices when course prices change later', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create([
        'price' => 100,
        'discount_type' => Course::DISCOUNT_TYPE_FIXED,
        'discount_amount' => 20,
    ]);

    $order = placeOrderFor($student, [$course]);

    $course->forceFill([
        'price' => 500,
        'discount_amount' => 100,
    ])->save();

    $course->refresh();

    $order->items->first()->load('course');

    expect((float) $order->items->first()->original_price)->toBe(100.0)
        ->and((float) $order->items->first()->discount_amount)->toBe(20.0)
        ->and((float) $order->items->first()->final_price)->toBe(80.0)
        ->and((float) $order->final_amount)->toBe(80.0);
});

test('enrollment grants are idempotent when an order is recalculated multiple times', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create(['price' => 100]);
    $orders = app(OrderService::class);
    $order = placeOrderFor($student, [$course]);

    $orders->recordPayment($order, 100);
    $payment = $order->payments()->first();

    $orders->verifyPayment($payment);
    $orders->verifyPayment($payment->fresh());

    expect(Enrollment::count())->toBe(1);
});

test('orders and payments are admin-only resources', function () {
    $student = User::factory()->create();

    expect(app(OrderPolicy::class)->viewAny($student))->toBeFalse()
        ->and(app(PaymentPolicy::class)->viewAny($student))->toBeFalse()
        ->and(app(OrderPolicy::class)->viewAny(User::factory()->admin()->create()))->toBeTrue();
});

test('students cannot open the orders admin pages', function () {
    $this->actingAs(User::factory()->create());

    $this->get(OrderResource::getUrl('index'))->assertForbidden();
    $this->get(OrderResource::getUrl('create'))->assertForbidden();
});

test('admins can open the orders admin pages', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(OrderResource::getUrl('index'))->assertSuccessful();
    $this->get(OrderResource::getUrl('create'))->assertSuccessful();
});

test('guests are sent to the student login from the checkout page', function () {
    $course = Course::factory()->published()->create(['price' => 50]);

    $this->get(route('checkout.create', ['courses' => [$course->slug]]))
        ->assertRedirect(route('login'));

    expect(Order::count())->toBe(0);
});

test('the checkout page places a pending order for multiple selected courses', function () {
    $student = User::factory()->create();
    $courses = Course::factory()->published()->count(2)->create(['price' => 75]);

    Livewire::actingAs($student)
        ->test('pages::checkout.create', ['courses' => $courses->pluck('slug')->all()])
        ->call('place')
        ->assertHasNoErrors();

    $order = Order::query()->sole();

    expect($order->student_id)->toBe($student->id)
        ->and($order->items()->count())->toBe(2)
        ->and((float) $order->final_amount)->toBe(150.0)
        ->and($order->status)->toBe('pending')
        ->and(Enrollment::count())->toBe(0);
});

test('the checkout page enrolls students instantly for free orders', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->free()->create();

    Livewire::actingAs($student)
        ->test('pages::checkout.create', ['courses' => [$course->slug]])
        ->call('place')
        ->assertRedirect(route('courses.show', ['course' => $course->slug]));

    expect(Enrollment::count())->toBe(1)
        ->and(Order::query()->sole()->status)->toBe('completed');
});

test('the checkout page surfaces invalid selections without creating anything', function () {
    $student = User::factory()->create();

    Livewire::actingAs($student)
        ->test('pages::checkout.create', ['courses' => ['not-a-real-course']])
        ->call('place');

    expect(Order::count())->toBe(0);
});

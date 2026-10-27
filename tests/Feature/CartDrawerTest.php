<?php

use App\Livewire\CartDrawer;
use App\Models\Course;
use App\Models\User;

test('the drawer lists the selected courses and totals', function () {
    $course = Course::factory()->published()->create([
        'price' => 100,
        'discount_type' => Course::DISCOUNT_TYPE_PERCENT,
        'discount_amount' => 25,
    ]);

    session(['course-cart' => [$course->id => true]]);

    Livewire::test(CartDrawer::class)
        ->assertSet('cart', [$course->id => true])
        ->assertSee($course->title)
        ->assertSee('$75.00')
        ->assertSee('$25.00');
});

test('the drawer shows the empty state when nothing is selected', function () {
    Livewire::test(CartDrawer::class)
        ->assertSet('cart', [])
        ->assertSee('Your cart is empty')
        ->assertDontSee('Proceed to Secure Checkout');
});

test('the drawer removes a course and notifies listeners', function () {
    $courses = Course::factory()->published()->count(2)->create();

    session(['course-cart' => [
        $courses[0]->id => true,
        $courses[1]->id => true,
    ]]);

    Livewire::test(CartDrawer::class)
        ->call('removeFromCart', $courses[0]->id)
        ->assertSet('cart', [$courses[1]->id => true])
        ->assertDispatched('cart-removed', count: 1)
        ->assertDispatched('cart-updated', count: 1);

    expect(session('course-cart'))->toBe([$courses[1]->id => true]);
});

test('the drawer clears the cart and notifies listeners', function () {
    $course = Course::factory()->published()->create();

    session(['course-cart' => [$course->id => true]]);

    Livewire::test(CartDrawer::class)
        ->call('clearCart')
        ->assertSet('cart', [])
        ->assertDispatched('cart-cleared')
        ->assertDispatched('cart-updated', count: 0);

    expect(session()->has('course-cart'))->toBeFalse();
});

test('the drawer syncs when another page changes the cart', function () {
    $course = Course::factory()->published()->create();

    Livewire::test(CartDrawer::class)
        ->assertSet('cart', [])
        ->dispatch('cart-updated', cart: [$course->id => true], count: 1)
        ->assertSet('cart', [$course->id => true])
        ->assertSee($course->title);
});

test('guests are sent to login when they start checkout from the drawer', function () {
    $course = Course::factory()->published()->create();

    session(['course-cart' => [$course->id => true]]);

    Livewire::test(CartDrawer::class)
        ->call('startCheckout')
        ->assertRedirect(route('login'));
});

test('checkout only receives the selected published courses', function () {
    $student = User::factory()->create();
    $selected = Course::factory()->published()->create();
    Course::factory()->published()->create();
    $draft = Course::factory()->create();

    session(['course-cart' => [
        $selected->id => true,
        $draft->id => true,
    ]]);

    Livewire::actingAs($student)
        ->test(CartDrawer::class)
        ->call('startCheckout')
        ->assertRedirect(route('checkout.create', ['courses' => [$selected->slug]]));
});

test('the global drawer is rendered on every page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('id="cart-drawer"', false)
        ->assertSee('Your cart');
});

test('the header cart button opens the global drawer', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee("x-on:click.prevent=\"\$dispatch('cart-open')\"", false);
});

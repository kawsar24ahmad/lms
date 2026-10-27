<?php

use App\Livewire\CartCount;
use App\Livewire\CartDrawer;
use App\Models\Course;

test('the header badge is hidden when the cart is empty', function () {
    Livewire::test(CartCount::class)
        ->assertSet('count', 0)
        ->assertSee('Cart, empty')
        ->assertDontSee('Cart, 1 course');
});

test('the header badge counts the published courses in the session cart', function () {
    $courses = Course::factory()->published()->count(2)->create();

    session(['course-cart' => [
        $courses[0]->id => true,
        $courses[1]->id => true,
    ]]);

    Livewire::test(CartCount::class)
        ->assertSet('count', 2)
        ->assertSee('Cart, 2 courses');
});

test('draft courses in the session cart are not counted', function () {
    $published = Course::factory()->published()->create();
    $draft = Course::factory()->create();

    session(['course-cart' => [
        $published->id => true,
        $draft->id => true,
    ]]);

    Livewire::test(CartCount::class)
        ->assertSet('count', 1)
        ->assertSee('Cart, 1 course');
});

test('the header badge uses the count carried by the cart-updated event', function () {
    Livewire::test(CartCount::class)
        ->dispatch('cart-updated', count: 3)
        ->assertSet('count', 3);
});

test('the header badge re-reads the session when the event has no count', function () {
    $course = Course::factory()->published()->create();

    $component = Livewire::test(CartCount::class)
        ->assertSet('count', 0);

    session(['course-cart' => [$course->id => true]]);

    $component->dispatch('cart-updated')
        ->assertSet('count', 1);
});

test('the catalog notifies the header badge when the cart changes', function () {
    $course = Course::factory()->published()->create();

    Livewire::test('pages::courses.index')
        ->call('toggleCart', $course->id)
        ->assertDispatched('cart-updated', count: 1)
        ->call('toggleCart', $course->id)
        ->assertDispatched('cart-updated', count: 0);
});

test('clearing the cart notifies the header badge', function () {
    $course = Course::factory()->published()->create();

    session(['course-cart' => [$course->id => true]]);

    Livewire::test(CartDrawer::class)
        ->call('clearCart')
        ->assertDispatched('cart-updated', count: 0);
});

test('the drawer notifies the header badge when an item is removed', function () {
    $courses = Course::factory()->published()->count(2)->create();

    session(['course-cart' => [
        $courses[0]->id => true,
        $courses[1]->id => true,
    ]]);

    Livewire::test(CartDrawer::class)
        ->call('removeFromCart', $courses[0]->id)
        ->assertDispatched('cart-updated', count: 1);
});

test('the header badge accepts the full cart payload from the drawer', function () {
    $course = Course::factory()->published()->create();

    Livewire::test(CartCount::class)
        ->dispatch('cart-updated', cart: [$course->id => true], count: 1)
        ->assertSet('count', 1);
});

test('the header reads the cart written by the catalog', function () {
    $course = Course::factory()->published()->create();

    Livewire::test('pages::courses.index')
        ->call('toggleCart', $course->id);

    Livewire::test(CartCount::class)
        ->assertSet('count', 1)
        ->assertSee('Cart, 1 course');
});

test('the page header renders the live cart count from the session', function () {
    $courses = Course::factory()->published()->count(2)->create();

    $this->withSession(['course-cart' => [
        $courses[0]->id => true,
        $courses[1]->id => true,
    ]])
        ->get(route('courses.index'))
        ->assertOk()
        ->assertSee('Cart, 2 courses');
});

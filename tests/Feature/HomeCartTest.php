<?php

use App\Livewire\CartCount;
use App\Models\Course;

test('the home page shows an add to cart button for published courses', function () {
    Course::factory()->published()->create(['title' => 'Home course']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Home course')
        ->assertSee('Add to cart');
});

test('students can add and remove a course from the home page', function () {
    $course = Course::factory()->published()->create();

    Livewire::test('pages::home.index')
        ->call('toggleCart', $course->id)
        ->assertSet("cart.{$course->id}", true)
        ->assertDispatched('cart-updated', count: 1)
        ->call('toggleCart', $course->id)
        ->assertSet('cart', [])
        ->assertDispatched('cart-updated', count: 0);
});

test('draft courses cannot be added to the cart from the home page', function () {
    $draft = Course::factory()->create();

    Livewire::test('pages::home.index')
        ->call('toggleCart', $draft->id)
        ->assertSet('cart', []);
});

test('the home page cart is shared with the catalog and the header badge', function () {
    $course = Course::factory()->published()->create();

    Livewire::test('pages::home.index')
        ->call('toggleCart', $course->id);

    Livewire::test(CartCount::class)
        ->assertSet('count', 1);

    Livewire::test('pages::courses.index')
        ->assertSet("cart.{$course->id}", true);
});

test('the home page card reflects the selected cart state', function () {
    $course = Course::factory()->published()->create();

    Livewire::test('pages::home.index')
        ->call('toggleCart', $course->id)
        ->assertSee('In cart')
        ->assertSee('Remove');
});

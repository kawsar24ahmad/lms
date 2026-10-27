<?php

use App\Models\Course;
use App\Models\User;

test('students can add and remove a published course from the cart', function () {
    $course = Course::factory()->published()->create();

    Livewire::test('pages::courses.index')
        ->call('toggleCart', $course->id)
        ->assertSet("cart.{$course->id}", true)
        ->assertDispatched('cart-added')
        ->assertDispatched('cart-updated', count: 1)
        ->call('toggleCart', $course->id)
        ->assertSet('cart', [])
        ->assertDispatched('cart-removed')
        ->assertDispatched('cart-updated', count: 0);
});

test('draft courses cannot be added to the cart', function () {
    $draft = Course::factory()->create();

    Livewire::test('pages::courses.index')
        ->call('toggleCart', $draft->id)
        ->assertSet('cart', []);
});

test('the cart selection survives pagination', function () {
    $courses = Course::factory()->published()->count(12)->create();

    Livewire::test('pages::courses.index')
        ->call('toggleCart', $courses->first()->id)
        ->call('setPage', 2)
        ->assertSet("cart.{$courses->first()->id}", true);
});

test('the cart selection survives a fresh page load', function () {
    $course = Course::factory()->published()->create();

    Livewire::test('pages::courses.index')
        ->call('toggleCart', $course->id);

    Livewire::test('pages::courses.index')
        ->assertSet("cart.{$course->id}", true);
});

test('placing an order clears the persisted catalog cart', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->free()->create();

    Livewire::actingAs($student)
        ->test('pages::courses.index')
        ->call('toggleCart', $course->id);

    expect(session()->has('course-cart'))->toBeTrue();

    Livewire::actingAs($student)
        ->test('pages::checkout.create', ['courses' => [$course->slug]])
        ->call('place')
        ->assertRedirect(route('courses.show', ['course' => $course->slug]));

    expect(session()->has('course-cart'))->toBeFalse();
});

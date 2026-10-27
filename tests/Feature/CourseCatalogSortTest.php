<?php

use App\Models\Course;
use App\Models\Review;

test('price-low sorts by the effective discounted price and floors it at zero', function () {
    $free = Course::factory()->published()->create([
        'title' => 'Free after discount',
        'price' => 10,
        'discount_type' => Course::DISCOUNT_TYPE_FIXED,
        'discount_amount' => 20,
    ]);

    $fixed = Course::factory()->published()->create([
        'title' => 'Fixed discount course',
        'price' => 60,
        'discount_type' => Course::DISCOUNT_TYPE_FIXED,
        'discount_amount' => 30,
    ]);

    $fullPrice = Course::factory()->published()->create([
        'title' => 'Full price course',
        'price' => 40,
        'discount_type' => null,
        'discount_amount' => null,
    ]);

    $percent = Course::factory()->published()->create([
        'title' => 'Percent discount course',
        'price' => 100,
        'discount_type' => Course::DISCOUNT_TYPE_PERCENT,
        'discount_amount' => 50,
    ]);

    Livewire::test('pages::courses.index')
        ->set('sortBy', 'price-low')
        ->assertSeeHtmlInOrder([
            $free->title,
            $fixed->title,
            $fullPrice->title,
            $percent->title,
        ]);
});

test('highest-rated sorts by average rating and leaves unrated courses last', function () {
    $fiveStars = Course::factory()->published()->create(['title' => 'Five star course']);
    $threeStars = Course::factory()->published()->create(['title' => 'Three star course']);
    $unrated = Course::factory()->published()->create(['title' => 'Unrated course']);

    Review::factory()->for($fiveStars)->create(['rating' => 5]);
    Review::factory()->for($threeStars)->create(['rating' => 3]);

    Livewire::test('pages::courses.index')
        ->set('sortBy', 'highest-rated')
        ->assertSeeHtmlInOrder([
            $fiveStars->title,
            $threeStars->title,
            $unrated->title,
        ]);
});

test('popular sorts by review count', function () {
    $manyReviews = Course::factory()->published()->create(['title' => 'Many reviews course']);
    $fewReviews = Course::factory()->published()->create(['title' => 'Few reviews course']);
    $noReviews = Course::factory()->published()->create(['title' => 'No reviews course']);

    Review::factory()->for($manyReviews)->count(3)->create();
    Review::factory()->for($fewReviews)->count(1)->create();

    Livewire::test('pages::courses.index')
        ->set('sortBy', 'popular')
        ->assertSeeHtmlInOrder([
            $manyReviews->title,
            $fewReviews->title,
            $noReviews->title,
        ]);
});

test('newest uses the course id as a deterministic tie-breaker', function () {
    $publishedAt = now()->subMinute();

    $first = Course::factory()->published()->create([
        'title' => 'First published course',
        'published_at' => $publishedAt,
    ]);

    $second = Course::factory()->published()->create([
        'title' => 'Second published course',
        'published_at' => $publishedAt,
    ]);

    Livewire::test('pages::courses.index')
        ->set('sortBy', 'newest')
        ->assertSeeHtmlInOrder([
            $second->title,
            $first->title,
        ]);
});

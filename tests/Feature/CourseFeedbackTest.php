<?php

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\QueryException;

test('a user can only review a course once', function () {
    $course = Course::factory()->create();
    $user = User::factory()->create();

    Review::factory()->create([
        'course_id' => $course->id,
        'user_id' => $user->id,
    ]);

    expect(fn () => Review::factory()->create([
        'course_id' => $course->id,
        'user_id' => $user->id,
    ]))->toThrow(QueryException::class);
});

test('reviews can be filtered by minimum rating', function () {
    Course::factory()->has(Review::factory()->fiveStars(), 'reviews')->create();
    Course::factory()->has(Review::factory(['rating' => 2]), 'reviews')->create();

    expect(Review::withRatingAtLeast(4)->count())->toBe(1);
});

test('a user earns at most one certificate per course', function () {
    $course = Course::factory()->create();
    $user = User::factory()->create();

    Certificate::factory()->create([
        'course_id' => $course->id,
        'user_id' => $user->id,
    ]);

    expect(fn () => Certificate::factory()->create([
        'course_id' => $course->id,
        'user_id' => $user->id,
    ]))->toThrow(QueryException::class);
});

test('certificate numbers are unique', function () {
    Certificate::factory()->create(['certificate_number' => 'CERT-2026-001']);

    expect(fn () => Certificate::factory()->create(['certificate_number' => 'CERT-2026-001']))
        ->toThrow(QueryException::class);
});

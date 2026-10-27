<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\QueryException;

test('a user can only be enrolled once in a course', function () {
    $course = Course::factory()->create();
    $user = User::factory()->create();

    Enrollment::factory()->create([
        'course_id' => $course->id,
        'student_id' => $user->id,
    ]);

    expect(fn () => Enrollment::factory()->create([
        'course_id' => $course->id,
        'student_id' => $user->id,
    ]))->toThrow(QueryException::class);
});

test('deleting a user removes their enrollments', function () {
    $user = User::factory()->create();
    Enrollment::factory()->for($user, 'student')->count(3)->create();

    $user->delete();

    expect(Enrollment::count())->toBe(0);
});

test('the completed state marks the enrollment complete', function () {
    $enrollment = Enrollment::factory()->completed()->create();

    expect($enrollment->status)->toBe('completed')
        ->and($enrollment->completed_at)->not->toBeNull()
        ->and(Enrollment::completed()->whereKey($enrollment->getKey())->exists())->toBeTrue();
});

test('the completed scope excludes active enrollments', function () {
    Enrollment::factory()->for(User::factory(), 'student')->completed()->create();
    Enrollment::factory()->for(User::factory(), 'student')->create();

    expect(Enrollment::active()->count())->toBe(1)
        ->and(Enrollment::completed()->count())->toBe(1);
});

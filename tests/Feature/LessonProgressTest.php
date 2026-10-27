<?php

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Database\QueryException;

test('a user can only have one progress record per lesson', function () {
    $lesson = Lesson::factory()->create();
    $user = User::factory()->create();

    LessonProgress::factory()->create([
        'lesson_id' => $lesson->id,
        'user_id' => $user->id,
    ]);

    expect(fn () => LessonProgress::factory()->create([
        'lesson_id' => $lesson->id,
        'user_id' => $user->id,
    ]))->toThrow(QueryException::class);
});

test('the completed state marks the lesson completed', function () {
    $progress = LessonProgress::factory()->completed()->create();

    expect($progress->is_completed)->toBeTrue()
        ->and($progress->completed_at)->not->toBeNull()
        ->and(LessonProgress::completed()->whereKey($progress->getKey())->exists())->toBeTrue();
});

test('progress records can be filtered by user', function () {
    $user = User::factory()->create();
    LessonProgress::factory()->for($user, 'student')->count(2)->create();
    LessonProgress::factory()->for(User::factory(), 'student')->create();

    expect(LessonProgress::forUser($user)->count())->toBe(2);
});

test('deleting a lesson removes its progress records', function () {
    $lesson = Lesson::factory()->has(LessonProgress::factory(3), 'progress')->create();

    $lesson->delete();

    expect(LessonProgress::count())->toBe(0);
});

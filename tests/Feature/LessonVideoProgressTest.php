<?php

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Section;
use App\Models\User;

/**
 * Build a course with one published section and return the enrolled lesson.
 *
 * @return array{0: User, 1: Course, 2: Lesson}
 */
function enrolledVideoLesson(string $videoUrl = 'https://example.com/lesson.mp4'): array
{
    $student = User::factory()->create();
    $course = Course::factory()->create();
    $section = Section::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create([
        'section_id' => $section->id,
        'video_url' => $videoUrl,
        'duration_minutes' => 2,
    ]);

    $course->enroll($student);

    return [$student, $course, $lesson];
}

test('the lesson page renders the tracked video player and progress panel', function () {
    [$student, $course, $lesson] = enrolledVideoLesson('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

    $this->actingAs($student)
        ->get(route('lessons.show', ['course' => $course, 'lesson' => $lesson]))
        ->assertSuccessful()
        ->assertSee('Video progress')
        ->assertSee('data-video-tracker', false)
        ->assertSee('enablejsapi=1', false);
});

test('guests cannot save lesson video progress', function () {
    $lesson = Lesson::factory()->create();

    $this->postJson(route('lessons.progress.update', $lesson), [
        'last_position' => 10,
        'furthest_position' => 10,
        'seconds_watched' => 10,
        'duration_seconds' => 100,
    ])->assertUnauthorized();
});

test('an enrolled student can save video progress', function () {
    [$student, , $lesson] = enrolledVideoLesson();

    $this->actingAs($student)
        ->postJson(route('lessons.progress.update', $lesson), [
            'last_position' => 42,
            'furthest_position' => 42,
            'seconds_watched' => 42,
            'duration_seconds' => 600,
        ])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'completed' => false,
            'percentage' => 7,
            'last_position' => 42,
            'furthest_position' => 42,
            'seconds_watched' => 42,
            'duration_seconds' => 600,
        ]);

    $progress = LessonProgress::query()
        ->where('user_id', $student->id)
        ->where('lesson_id', $lesson->id)
        ->firstOrFail();

    expect($progress->last_position)->toBe(42)
        ->and($progress->furthest_position)->toBe(42)
        ->and($progress->seconds_watched)->toBe(42)
        ->and($progress->duration_seconds)->toBe(600)
        ->and($progress->is_completed)->toBeFalse();
});

test('seeking backwards never lowers saved progress', function () {
    [$student, , $lesson] = enrolledVideoLesson();

    $this->actingAs($student)->postJson(route('lessons.progress.update', $lesson), [
        'last_position' => 60,
        'furthest_position' => 60,
        'seconds_watched' => 60,
        'duration_seconds' => 100,
    ])->assertOk();

    $this->actingAs($student)
        ->postJson(route('lessons.progress.update', $lesson), [
            'last_position' => 30,
            'furthest_position' => 30,
            'seconds_watched' => 30,
            'duration_seconds' => 100,
        ])
        ->assertOk()
        ->assertJson([
            'percentage' => 60,
            'last_position' => 30,
            'furthest_position' => 60,
            'seconds_watched' => 60,
        ]);
});

test('reaching the completion threshold completes the lesson and course', function () {
    [$student, $course, $lesson] = enrolledVideoLesson();

    $this->actingAs($student)
        ->postJson(route('lessons.progress.update', $lesson), [
            'last_position' => 95,
            'furthest_position' => 95,
            'seconds_watched' => 95,
            'duration_seconds' => 100,
        ])
        ->assertOk()
        ->assertJson([
            'completed' => true,
            'percentage' => 100,
        ]);

    $progress = LessonProgress::query()
        ->where('user_id', $student->id)
        ->where('lesson_id', $lesson->id)
        ->firstOrFail();

    expect($progress->is_completed)->toBeTrue()
        ->and($progress->completed_at)->not->toBeNull()
        ->and($course->enrollments()->where('student_id', $student->id)->firstOrFail()->status)->toBe('completed')
        ->and(Certificate::query()->where('course_id', $course->id)->where('user_id', $student->id)->exists())->toBeTrue();
});

test('watching below the completion threshold does not complete the lesson', function () {
    [$student, , $lesson] = enrolledVideoLesson();

    $this->actingAs($student)
        ->postJson(route('lessons.progress.update', $lesson), [
            'last_position' => 89,
            'furthest_position' => 89,
            'seconds_watched' => 89,
            'duration_seconds' => 100,
        ])
        ->assertOk()
        ->assertJson(['completed' => false, 'percentage' => 89]);

    expect($lesson->isCompletedFor($student))->toBeFalse();
});

test('students who are not enrolled cannot save progress for a locked lesson', function () {
    $student = User::factory()->create();
    $lesson = Lesson::factory()->create();

    $this->actingAs($student)
        ->postJson(route('lessons.progress.update', $lesson), [
            'last_position' => 10,
            'furthest_position' => 10,
            'seconds_watched' => 10,
            'duration_seconds' => 100,
        ])
        ->assertForbidden();

    expect(LessonProgress::count())->toBe(0);
});

test('authenticated guests can track progress on free preview lessons', function () {
    $student = User::factory()->create();
    $lesson = Lesson::factory()->preview()->create();

    $this->actingAs($student)
        ->postJson(route('lessons.progress.update', $lesson), [
            'last_position' => 10,
            'furthest_position' => 10,
            'seconds_watched' => 10,
            'duration_seconds' => 100,
        ])
        ->assertOk()
        ->assertJson(['completed' => false]);

    expect(LessonProgress::query()->where('user_id', $student->id)->where('lesson_id', $lesson->id)->exists())->toBeTrue();
});

test('progress payloads are validated', function () {
    $student = User::factory()->create();
    $lesson = Lesson::factory()->preview()->create();

    $this->actingAs($student)
        ->postJson(route('lessons.progress.update', $lesson), [
            'last_position' => -1,
            'furthest_position' => -1,
            'seconds_watched' => -1,
            'duration_seconds' => -1,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'last_position',
            'furthest_position',
            'seconds_watched',
            'duration_seconds',
        ]);
});

test('the progress percentage uses the furthest position and falls back to the lesson duration', function () {
    $lesson = Lesson::factory()->create(['duration_minutes' => 10]);

    $progress = LessonProgress::factory()->create([
        'lesson_id' => $lesson->id,
        'duration_seconds' => 600,
        'last_position' => 120,
        'furthest_position' => 300,
    ]);

    expect($progress->percentage())->toBe(50);

    $progress->update(['duration_seconds' => 0]);

    expect($progress->refresh()->percentage())->toBe(50);

    $progress->update(['is_completed' => true]);

    expect($progress->refresh()->percentage())->toBe(100);
});

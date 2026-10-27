<?php

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\Submission;
use App\Models\User;

/**
 * Build an enrolled student with one assignment in a course.
 *
 * @return array{0: User, 1: Course, 2: Lesson, 3: Assignment}
 */
function assignmentScenario(array $assignmentAttributes = []): array
{
    $student = User::factory()->create();
    $course = Course::factory()->published()->create();
    $section = Section::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['section_id' => $section->id]);

    $course->enroll($student);

    $assignment = Assignment::factory()->create(array_merge([
        'lesson_id' => $lesson->id,
        'title' => 'Build a landing page',
        'max_score' => 100,
    ], $assignmentAttributes));

    return [$student, $course, $lesson, $assignment];
}

test('guests are redirected from the assignments page', function () {
    $this->get(route('assignments.index'))->assertRedirect(route('login'));
});

test('the assignments page lists assignments from enrolled courses only', function () {
    [$student, $course, $lesson, $assignment] = assignmentScenario();

    $otherCourse = Course::factory()->published()->create();
    $otherSection = Section::factory()->create(['course_id' => $otherCourse->id]);
    $otherLesson = Lesson::factory()->create(['section_id' => $otherSection->id]);
    Assignment::factory()->create([
        'lesson_id' => $otherLesson->id,
        'title' => 'Hidden assignment',
    ]);

    $this->actingAs($student)
        ->get(route('assignments.index'))
        ->assertSuccessful()
        ->assertSee('Build a landing page')
        ->assertDontSee('Hidden assignment')
        ->assertSee('Pending');
});

test('assignments are shown under their lesson', function () {
    [$student, $course, $lesson, $assignment] = assignmentScenario();

    $this->actingAs($student)
        ->get(route('lessons.show', ['course' => $course, 'lesson' => $lesson]))
        ->assertSuccessful()
        ->assertSee('Build a landing page')
        ->assertSee(route('assignments.show', $assignment));
});

test('an enrolled student can submit a link as their assignment', function () {
    [$student, , , $assignment] = assignmentScenario();

    Livewire::actingAs($student)
        ->test('pages::assignments.show', ['assignment' => $assignment])
        ->assertSuccessful()
        ->set('content', 'https://docs.google.com/document/d/example-link')
        ->call('submit')
        ->assertHasNoErrors();

    $submission = Submission::query()
        ->where('assignment_id', $assignment->id)
        ->where('user_id', $student->id)
        ->firstOrFail();

    expect($submission->content)->toBe('https://docs.google.com/document/d/example-link')
        ->and($submission->status)->toBe(Submission::STATUS_PENDING)
        ->and($submission->submitted_at)->not->toBeNull();
});

test('a student can update a pending submission', function () {
    [$student, , , $assignment] = assignmentScenario();

    Livewire::actingAs($student)
        ->test('pages::assignments.show', ['assignment' => $assignment])
        ->set('content', 'https://github.com/student/first-version')
        ->call('submit')
        ->assertHasNoErrors()
        ->set('content', 'https://github.com/student/final-version')
        ->call('submit')
        ->assertHasNoErrors();

    expect(Submission::query()->where('assignment_id', $assignment->id)->count())->toBe(1)
        ->and(Submission::query()->first()->content)->toBe('https://github.com/student/final-version');
});

test('a submission requires some content', function () {
    [$student, , , $assignment] = assignmentScenario();

    Livewire::actingAs($student)
        ->test('pages::assignments.show', ['assignment' => $assignment])
        ->set('content', '')
        ->call('submit')
        ->assertHasErrors(['content' => 'required']);

    expect(Submission::count())->toBe(0);
});

test('an unenrolled student cannot open an assignment', function () {
    [, $course, $lesson, $assignment] = assignmentScenario();

    $outsider = User::factory()->create();
    Course::factory()->published()->create();

    $this->actingAs($outsider)
        ->get(route('assignments.show', $assignment))
        ->assertForbidden();
});

test('a graded submission can no longer be changed', function () {
    [$student, , , $assignment] = assignmentScenario();

    Submission::factory()->graded()->create([
        'assignment_id' => $assignment->id,
        'user_id' => $student->id,
        'content' => 'https://github.com/student/original',
        'score' => 90,
    ]);

    Livewire::actingAs($student)
        ->test('pages::assignments.show', ['assignment' => $assignment])
        ->set('content', 'https://github.com/student/changed')
        ->call('submit')
        ->assertHasErrors(['content']);

    expect(Submission::query()->first()->content)->toBe('https://github.com/student/original');
});

test('a past-due assignment is locked for new submissions', function () {
    [$student, , , $assignment] = assignmentScenario([
        'due_date' => now()->subDay(),
    ]);

    $this->actingAs($student)
        ->get(route('assignments.show', $assignment))
        ->assertSuccessful()
        ->assertSee('due date has passed');

    Livewire::actingAs($student)
        ->test('pages::assignments.show', ['assignment' => $assignment])
        ->set('content', 'https://github.com/student/late')
        ->call('submit')
        ->assertStatus(403);

    expect(Submission::count())->toBe(0);
});

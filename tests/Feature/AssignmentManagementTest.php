<?php

use App\Filament\Resources\Assignments\AssignmentResource;
use App\Filament\Resources\Assignments\Pages\CreateAssignment;
use App\Filament\Resources\Assignments\Pages\EditAssignment;
use App\Filament\Resources\Submissions\SubmissionResource;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\Submission;
use App\Models\User;
use Filament\Forms\Components\Select;

test('an admin creates an assignment by choosing a course and one of its lessons', function () {
    $this->actingAs(User::factory()->admin()->create());

    $course = Course::factory()->create();
    $section = Section::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['section_id' => $section->id, 'title' => 'Intro lesson']);

    Livewire::test(CreateAssignment::class)
        ->fillForm([
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'title' => 'Build a landing page',
            'description' => 'Submit the link to your GitHub repository.',
            'max_score' => 50,
            'due_date' => now()->addWeek(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $assignment = Assignment::query()->where('title', 'Build a landing page')->firstOrFail();

    expect($assignment->lesson_id)->toBe($lesson->id)
        ->and($assignment->max_score)->toBe(50)
        ->and($assignment->description)->toBe('Submit the link to your GitHub repository.');
});

test('the edit form preselects the course of the assignment lesson', function () {
    $this->actingAs(User::factory()->admin()->create());

    $course = Course::factory()->create();
    $section = Section::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['section_id' => $section->id]);
    $assignment = Assignment::factory()->create([
        'lesson_id' => $lesson->id,
        'title' => 'Original title',
    ]);

    Livewire::test(EditAssignment::class, ['record' => $assignment->getRouteKey()])
        ->assertFormSet([
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
        ])
        ->fillForm(['title' => 'Updated title'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($assignment->refresh()->title)->toBe('Updated title')
        ->and($assignment->lesson_id)->toBe($lesson->id);
});

test('lessons from other courses are not offered for the selected course', function () {
    $this->actingAs(User::factory()->admin()->create());

    $course = Course::factory()->create();
    $section = Section::factory()->create(['course_id' => $course->id]);
    $matchingLesson = Lesson::factory()->create(['section_id' => $section->id, 'title' => 'Matching lesson']);

    $otherCourse = Course::factory()->create();
    $otherSection = Section::factory()->create(['course_id' => $otherCourse->id]);
    $foreignLesson = Lesson::factory()->create(['section_id' => $otherSection->id, 'title' => 'Foreign lesson']);

    Livewire::test(CreateAssignment::class)
        ->fillForm(['course_id' => $course->id])
        ->assertFormFieldExists('lesson_id', function (Select $field) use ($matchingLesson, $foreignLesson): bool {
            $options = collect($field->getOptions())
                ->flatMap(fn (array $group): array => array_keys($group))
                ->all();

            return in_array($matchingLesson->id, $options, true)
                && ! in_array($foreignLesson->id, $options, true);
        });
});

test('the admin assignment and submission edit pages render', function () {
    $this->actingAs(User::factory()->admin()->create());

    $assignment = Assignment::factory()->create();
    $submission = Submission::factory()->for($assignment)->create();

    $this->get(AssignmentResource::getUrl('edit', ['record' => $assignment]))
        ->assertSuccessful()
        ->assertSee('Submissions');

    $this->get(SubmissionResource::getUrl('edit', ['record' => $submission]))
        ->assertSuccessful()
        ->assertSee('Submission (link or note)');
});

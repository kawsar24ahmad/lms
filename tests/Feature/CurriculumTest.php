<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Section;

test('deleting a course cascades to its sections and lessons', function () {
    $course = Course::factory()->has(Section::factory(2)->has(Lesson::factory(3)), 'sections')->create();

    $course->delete();

    expect(Section::count())->toBe(0)
        ->and(Lesson::count())->toBe(0);
});

test('deleting a section removes its lessons', function () {
    $section = Section::factory()->has(Lesson::factory(3))->create();

    $section->delete();

    expect(Lesson::count())->toBe(0);
});

test('deleting a quiz cascades to its questions', function () {
    $quiz = Quiz::factory()->has(Question::factory(3))->create();

    $quiz->delete();

    expect(Question::count())->toBe(0);
});

test('deleting a question removes its options', function () {
    $question = Question::factory()->has(Option::factory(4))->create();

    $question->delete();

    expect(Option::count())->toBe(0);
});

test('sections and lessons are returned in display order', function () {
    $section = Section::factory()->create();

    Lesson::factory()->create(['section_id' => $section->id, 'title' => 'Last lesson', 'position' => 2]);
    Lesson::factory()->create(['section_id' => $section->id, 'title' => 'Middle lesson', 'position' => 1]);
    Lesson::factory()->create(['section_id' => $section->id, 'title' => 'First lesson', 'position' => 0]);

    expect($section->lessons()->pluck('title')->all())->toBe([
        'First lesson',
        'Middle lesson',
        'Last lesson',
    ]);
});

<?php

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;

test('the catalog page lists published courses only', function () {
    $published = Course::factory()->published()->create(['title' => 'A published course']);
    Course::factory()->create(['title' => 'A draft course']);

    $this->get(route('courses.index'))
        ->assertSuccessful()
        ->assertSee($published->title)
        ->assertDontSee('A draft course');
});

test('guests can browse a course page with its curriculum', function () {
    $course = Course::factory()->published()->create(['title' => 'PHP for beginners']);
    Section::factory()->has(Lesson::factory(3), 'lessons')->create(['course_id' => $course->id]);

    $this->get(route('courses.show', $course))
        ->assertSuccessful()
        ->assertSee('PHP for beginners')
        ->assertSee('Curriculum');
});

test('guests get redirected to the login page when they try to enroll', function () {
    $course = Course::factory()->published()->create();

    Livewire::test('pages::courses.show', ['course' => $course])
        ->call('enroll')
        ->assertRedirect(route('login'));

    expect(Enrollment::count())->toBe(0);
});

test('guests can watch preview lessons but not locked lessons', function () {
    $course = Course::factory()->published()->create();
    $section = Section::factory()->create(['course_id' => $course->id]);
    $preview = Lesson::factory()->preview()->create(['section_id' => $section->id, 'title' => 'Free preview']);
    $locked = Lesson::factory()->create(['section_id' => $section->id, 'title' => 'Members only']);

    $this->get(route('lessons.show', ['course' => $course, 'lesson' => $preview]))->assertSuccessful();

    $this->get(route('lessons.show', ['course' => $course, 'lesson' => $locked]))->assertForbidden();
});

test('the enroll action creates an active enrollment and lands on the next lesson', function () {
    $course = Course::factory()->published()->free()->create();
    $section = Section::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['section_id' => $section->id, 'position' => 0]);
    $student = User::factory()->create();

    Livewire::actingAs($student)
        ->test('pages::courses.show', ['course' => $course])
        ->call('enroll')
        ->assertRedirect(route('lessons.show', ['course' => $course, 'lesson' => $lesson]));

    expect(Enrollment::query()
        ->where('course_id', $course->id)
        ->where('student_id', $student->id)
        ->where('status', 'active')
        ->exists())->toBeTrue();
});

test('paid courses hand the student off to the checkout instead of enrolling directly', function () {
    $course = Course::factory()->published()->create(['price' => 100]);
    $student = User::factory()->create();

    Livewire::actingAs($student)
        ->test('pages::courses.show', ['course' => $course])
        ->call('enroll')
        ->assertRedirect(route('checkout.create', ['courses' => [$course->slug]]));

    expect(Enrollment::count())->toBe(0);
});

test('completing every lesson completes the enrollment and issues a certificate', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create();
    $section = Section::factory()->create(['course_id' => $course->id]);
    $lessons = Lesson::factory(2)->create(['section_id' => $section->id]);
    $course->enroll($student);

    $component = Livewire::actingAs($student)
        ->test('pages::lessons.show', ['course' => $course, 'lesson' => $lessons->first()])
        ->call('markComplete')
        ->assertSuccessful();

    foreach ($lessons->skip(1) as $lesson) {
        Livewire::actingAs($student)
            ->test('pages::lessons.show', ['course' => $course, 'lesson' => $lesson])
            ->call('markComplete');
    }

    $enrollment = Enrollment::query()
        ->where('course_id', $course->id)
        ->where('student_id', $student->id)
        ->firstOrFail();

    expect($enrollment->status)->toBe('completed')
        ->and(Certificate::where('course_id', $course->id)->where('user_id', $student->id)->exists())->toBeTrue();
});

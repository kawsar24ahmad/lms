<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;

test('guests are redirected from the dashboard to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('students without enrollments see the empty state', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('not enrolled in any course', false);
});

test('the dashboard lists enrolled courses with progress', function () {
    $user = User::factory()->create();
    $course = Course::factory()->published()->create(['title' => 'My enrolled course']);
    $section = Section::factory()->create(['course_id' => $course->id]);
    $lessons = Lesson::factory(2)->create(['section_id' => $section->id]);
    $lessons->first()->completeFor($user);
    $course->enroll($user);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('My enrolled course')
        ->assertSee('1 / 2 lessons')
        ->assertSee('50%')
        ->assertSee('Continue');
});

test('fully completed courses show the certificate and review CTA', function () {
    $user = User::factory()->create();
    $course = Course::factory()->published()->create();
    $section = Section::factory()->create(['course_id' => $course->id]);
    Lesson::factory(2)->create(['section_id' => $section->id]);
    $course->enroll($user);

    $course->sections->flatMap->lessons->each(
        fn (Lesson $lesson) => $lesson->completeFor($user),
    );

    $enrollment = Enrollment::query()
        ->where('course_id', $course->id)
        ->where('student_id', $user->id)
        ->firstOrFail();

    expect($enrollment->status)->toBe('completed');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Completed', false)
        ->assertSee('Your certificates')
        ->assertSee('CERT-');
});

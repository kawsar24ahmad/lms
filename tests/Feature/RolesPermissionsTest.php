<?php

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Resources\Quizzes\QuizResource;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;
use App\Policies\OwnedResourcePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('students cannot access the admin panel', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/admin')->assertForbidden();
});

test('instructors and admins can access the admin panel', function () {
    $instructor = User::factory()->instructor()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($instructor)->get('/admin')->assertSuccessful();

    $this->actingAs($admin)->get('/admin')->assertSuccessful();
});

test('instructors can open their own course but not another instructor\'s course', function () {
    grantRolePermissions('instructor', [
        'ViewAny:Course',
        'View:Course',
        'Update:Course',
    ]);

    $instructor = User::factory()->instructor()->create();
    $ownCourse = Course::factory()->create(['user_id' => $instructor->id]);
    $otherCourse = Course::factory()->create();

    $this->actingAs($instructor);

    $this->get(CourseResource::getUrl('edit', ['record' => $ownCourse]))->assertSuccessful();

    // Scoping makes foreign records invisible: Filament serves 404 for them.
    $this->get(CourseResource::getUrl('edit', ['record' => $otherCourse]))->assertNotFound();
});

test('instructors cannot touch admin-only resources', function () {
    $this->actingAs(User::factory()->instructor()->create());

    $this->get(CategoryResource::getUrl('index'))->assertForbidden();
});

test('instructors can touch admin-only resources', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(CategoryResource::getUrl('index'))->assertSuccessful();
});

test('admins can touch admin-only resources', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(CategoryResource::getUrl('index'))->assertSuccessful();
});

test('instructors only reach resources their Shield role permits', function () {
    $instructor = User::factory()->instructor()->create();
    $ownCourse = Course::factory()->create(['user_id' => $instructor->id]);
    Quiz::factory()->create(['course_id' => $ownCourse->id]);

    $this->actingAs($instructor);

    // No Shield permission for quizzes, so the resource is unreachable.
    $this->get(QuizResource::getUrl('index'))->assertForbidden();

    grantRolePermissions('instructor', ['ViewAny:Quiz']);

    // The permission now opens the resource, scoped to owned courses.
    $this->get(QuizResource::getUrl('index'))->assertSuccessful();
});

test('resource queries hide courses the instructor does not own', function () {
    $instructor = User::factory()->instructor()->create();
    $ownCourse = Course::factory()->create(['user_id' => $instructor->id]);
    $otherCourse = Course::factory()->create();

    $topSection = Section::factory()->create(['course_id' => $otherCourse->id]);
    Lesson::factory()->create(['section_id' => $topSection->id]);
    $ownSection = Section::factory()->create(['course_id' => $ownCourse->id]);
    $ownLesson = Lesson::factory()->create(['section_id' => $ownSection->id]);

    $this->actingAs($instructor);

    $visibleCourseIds = CourseResource::getEloquentQuery()->pluck('id')->all();
    $visibleLessonIds = LessonResource::getEloquentQuery()->pluck('id')->all();

    expect($visibleCourseIds)->toBe([$ownCourse->id]);
    expect($visibleLessonIds)->toBe([$ownLesson->id]);
});

test('instructors with no courses see nothing instead of everything', function () {
    Course::factory()->create();

    $this->actingAs(User::factory()->instructor()->create());

    expect(CourseResource::getEloquentQuery()->exists())->toBeFalse();
});

test('the ownership policy allows admins and record owners only', function () {
    grantRolePermissions('instructor', ['Update:Course']);

    $instructor = User::factory()->instructor()->create();
    $ownCourse = Course::factory()->create(['user_id' => $instructor->id]);
    $otherCourse = Course::factory()->create();

    $policy = app(OwnedResourcePolicy::class);

    expect($policy->update($instructor, $ownCourse))->toBeTrue();
    expect($policy->update($instructor, $otherCourse))->toBeFalse();
    expect($policy->update(User::factory()->admin()->create(), $otherCourse))->toBeTrue();
    expect($policy->update(User::factory()->create(), $otherCourse))->toBeFalse();
});

test('self-registration always produces students', function () {
    Livewire::test('pages::auth.register', [])
        ->set('name', 'Aspiring Admin')
        ->set('email', 'new.student@lms.test')
        ->set('password', 'Sup3rSecret!123')
        ->set('password_confirmation', 'Sup3rSecret!123')
        ->call('save');

    $user = User::query()->where('email', 'new.student@lms.test')->firstOrFail();

    expect($user->isStudent())->toBeTrue()
        ->and($user->roles()->count())->toBe(0)
        ->and($user->isInstructor())->toBeFalse()
        ->and($user->isAdmin())->toBeFalse();
});

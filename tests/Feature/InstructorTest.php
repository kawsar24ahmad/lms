<?php

use App\Filament\Resources\Instructors\InstructorResource;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('admins can manage the instructors resource', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(InstructorResource::getUrl('index'))->assertSuccessful();
    $this->get(InstructorResource::getUrl('create'))->assertSuccessful();
});

test('plain instructors cannot manage the instructors resource', function () {
    $this->actingAs(User::factory()->instructor()->create());

    $this->get(InstructorResource::getUrl('index'))->assertForbidden();
});

test('instructor profiles list their users and are unique per user', function () {
    $instructor = User::factory()->instructor()->create();

    // The profile is created automatically by EnsureInstructorProfile
    // when the Shield instructor role is attached to the user.
    $profile = $instructor->instructor;

    $this->actingAs(User::factory()->admin()->create());

    $this->get(InstructorResource::getUrl('index'))
        ->assertSuccessful()
        ->assertSee($instructor->name);

    expect(Instructor::query()->where('user_id', $instructor->id)->count())->toBe(1)
        ->and($profile->user->is($instructor))->toBeTrue()
        ->and($profile->user_id)->toBe($instructor->id);

    Course::factory()->create(['user_id' => $instructor->id]);
    expect($instructor->courses()->count())->toBe(1);
});

test('assigning a role other than instructor creates no profile', function () {
    $instructor = User::factory()->create();

    $instructor->assignRole(Role::create(['name' => 'some_other_role']));

    expect(Instructor::query()->count())->toBe(0);
});

<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('admins can manage the users resource', function () {
    $this->actingAs(User::factory()->admin()->create());

    $user = User::factory()->create();

    $this->get(UserResource::getUrl('index'))->assertSuccessful();
    $this->get(UserResource::getUrl('create'))->assertSuccessful();
    $this->get(UserResource::getUrl('view', ['record' => $user]))->assertSuccessful();
    $this->get(UserResource::getUrl('edit', ['record' => $user]))->assertSuccessful();
});

test('plain instructors cannot manage the users resource', function () {
    $this->actingAs(User::factory()->instructor()->create());

    $this->get(UserResource::getUrl('index'))->assertForbidden();
});

test('plain students cannot manage the users resource', function () {
    $this->actingAs(User::factory()->create());

    $this->get(UserResource::getUrl('index'))->assertForbidden();
});

test('creating a user through the resource hashes the password and syncs shield roles', function () {
    $this->actingAs(User::factory()->admin()->create());
    $instructorRole = Role::query()->firstOrCreate(['name' => User::ROLE_INSTRUCTOR, 'guard_name' => 'web']);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Manually Made',
            'email' => 'manually.made@lms.test',
            'password' => 'Sup3rSecret!123',
            'roles' => [$instructorRole->getKey()],
        ])
        ->call('create');

    $user = User::query()->where('email', 'manually.made@lms.test')->firstOrFail();

    expect(Hash::check('Sup3rSecret!123', $user->password))->toBeTrue()
        ->and($user->hasRole(User::ROLE_INSTRUCTOR))->toBeTrue()
        ->and($user->isInstructor())->toBeTrue();
});

test('editing leaves the password untouched when blank', function () {
    $this->actingAs(User::factory()->admin()->create());
    $oldPassword = 'password';
    $user = User::factory()->create(['password' => Hash::make($oldPassword)]);

    Livewire::test(EditUser::class, ['record' => $user->getKey()])
        ->fillForm(['name' => 'Renamed'])
        ->call('save');

    $user->refresh();

    expect($user->name)->toBe('Renamed')
        ->and(Hash::check($oldPassword, $user->password))->toBeTrue();
});

test('view page infolist can open', function () {
    $this->actingAs(User::factory()->admin()->create());
    $user = User::factory()->instructor()->create();

    $this->get(UserResource::getUrl('view', ['record' => $user]))
        ->assertSuccessful()
        ->assertSee($user->name);
});

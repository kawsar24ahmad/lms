<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

test('guests see the login page and authenticated users are sent to the dashboard', function () {
    $this->get(route('login'))->assertSuccessful()->assertSee('Welcome back');

    $user = User::factory()->create();

    $this->actingAs($user)->get(route('login'))->assertRedirect(route('dashboard'));
});

test('a student can sign in with correct credentials and remembered', function () {
    $user = User::factory()->create([
        'email' => 'student@lms.test',
        'password' => Hash::make('password'),
    ]);

    $component = Livewire::test('pages::auth.login', [])
        ->set('email', 'student@lms.test')
        ->set('password', 'password')
        ->set('remember', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('wrong credentials show an error and do not authenticate', function () {
    User::factory()->create([
        'email' => 'student@lms.test',
        'password' => Hash::make('password'),
    ]);

    Livewire::test('pages::auth.login', [])
        ->set('email', 'student@lms.test')
        ->set('password', 'wrong-password')
        ->call('save')
        ->assertHasErrors(['email']);

    $this->assertGuest();
});

test('a student can register and is immediately signed in', function () {
    Livewire::test('pages::auth.register', [])
        ->set('name', 'New Student')
        ->set('email', 'new.student@lms.test')
        ->set('password', 'Sup3rSecret!123')
        ->set('password_confirmation', 'Sup3rSecret!123')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $user = User::query()->where('email', 'new.student@lms.test')->firstOrFail();

    $this->assertAuthenticatedAs($user);
    expect(Hash::check('Sup3rSecret!123', $user->password))->toBeTrue();
});

test('registering with an existing email or mismatched passwords fails', function () {
    User::factory()->create(['email' => 'taken@lms.test']);

    Livewire::test('pages::auth.register', [])
        ->set('name', 'Dup')
        ->set('email', 'taken@lms.test')
        ->set('password', 'Sup3rSecret!123')
        ->set('password_confirmation', 'Sup3rSecret!123')
        ->call('save')
        ->assertHasErrors(['email']);
    $this->assertGuest();
    $this->post(route('logout'));

    Livewire::test('pages::auth.register', [])
        ->set('name', 'Mismatch')
        ->set('email', 'fresh@lms.test')
        ->set('password', 'Sup3rSecret!123')
        ->set('password_confirmation', 'different')
        ->call('save')
        ->assertHasErrors(['password']);
    $this->assertGuest();
});

test('a reset link is emailed and a valid token resets the password', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'reset.me@lms.test',
        'password' => Hash::make('old-password'),
    ]);

    Livewire::test('pages::auth.forgot-password', [])
        ->set('email', 'reset.me@lms.test')
        ->call('sendResetLink')
        ->assertHasNoErrors();

    Notification::assertSentTo($user, ResetPassword::class);

    $token = null;

    Notification::assertSentTo(
        $user,
        ResetPassword::class,
        function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        },
    );

    expect($token)->not->toBeNull();

    Livewire::test('pages::auth.reset-password', ['token' => $token])
        ->set('email', 'reset.me@lms.test')
        ->set('password', 'Sup3rSecret!123')
        ->set('password_confirmation', 'Sup3rSecret!123')
        ->call('resetPassword')
        ->assertHasNoErrors()
        ->assertRedirect(route('login'));

    $user->refresh();
    expect(Hash::check('Sup3rSecret!123', $user->password))->toBeTrue();
    expect(Hash::check('old-password', $user->password))->toBeFalse();
});

test('an invalid reset token is rejected', function () {
    $user = User::factory()->create([
        'email' => 'reset.me@lms.test',
        'password' => Hash::make('old-password'),
    ]);

    Password::createToken($user);

    Livewire::test('pages::auth.reset-password', ['token' => 'not-a-valid-token'])
        ->set('email', 'reset.me@lms.test')
        ->set('password', 'Sup3rSecret!123')
        ->set('password_confirmation', 'Sup3rSecret!123')
        ->call('resetPassword')
        ->assertHasErrors(['email']);

    expect(Hash::check('old-password', $user->refresh()->password))->toBeTrue();
});

test('logging out destroys the session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'));

    $this->assertGuest();
});

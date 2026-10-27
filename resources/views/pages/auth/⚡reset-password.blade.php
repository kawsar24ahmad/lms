<?php

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Component;

new class extends Component {
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(?string $token = null): void
    {
        $this->token = $token ?? '';
        $this->email = (string) request()->query('email', '');
    }

    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', PasswordRule::defaults(), 'confirmed'],
        ], [
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            session()->flash('status', __($status));
            $this->redirectRoute('login');

            return;
        }

        $this->addError('email', __($status));
    }
};
?>

<div class="mx-auto max-w-md">
    <div class="rounded-xl bg-white p-8 ring-1 ring-gray-200">
        <h1 class="text-2xl font-bold text-gray-900">Choose a new password</h1>
        <p class="mt-1 mb-6 text-sm text-gray-500">Set a fresh password for your account.</p>

        <form wire:submit="resetPassword" class="space-y-4">
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Email address</label>
                <input
                    id="email"
                    type="email"
                    wire:model="email"
                    autocomplete="email"
                    class="w-full rounded-lg border-0 bg-white px-3 py-2 text-sm ring-1 ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-amber-500"
                />
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-gray-700">New password</label>
                <input
                    id="password"
                    type="password"
                    wire:model="password"
                    autocomplete="new-password"
                    class="w-full rounded-lg border-0 bg-white px-3 py-2 text-sm ring-1 ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-amber-500"
                />
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-gray-700">Confirm new password</label>
                <input
                    id="password_confirmation"
                    type="password"
                    wire:model="password_confirmation"
                    autocomplete="new-password"
                    class="w-full rounded-lg border-0 bg-white px-3 py-2 text-sm ring-1 ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-amber-500"
                />
            </div>

            <button type="submit" class="w-full rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-600" wire:loading.attr="disabled">
                Reset password
            </button>
        </form>
    </div>
</div>

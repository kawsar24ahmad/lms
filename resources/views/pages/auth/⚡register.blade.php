<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

new class extends Component {
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        if (auth()->check()) {
            $this->redirectIntended(route('dashboard'));
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email:filter', 'max:255', 'unique:'.User::class],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ], [
            'email.unique' => 'An account with this email already exists.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        $user = User::create($validated);

        Auth::login($user);

        session()->regenerate();

        $this->redirectRoute('dashboard');
    }
};
?>

<div class="animate-fade-up my-4 mx-auto max-w-5xl overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/80 shadow-(--shadow-card-hover) lg:grid lg:grid-cols-2">
    <x-ui.auth-shell />

    <div class="px-6 py-10 sm:px-10 lg:py-14">
        <div class="mx-auto max-w-sm">
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Create your account</h1>
            <p class="mt-1.5 mb-7 text-sm text-slate-500">Join the school and start learning today — it's free to get started.</p>

            <form wire:submit="save" class="space-y-5">
                <div>
                    <label for="name" class="label">Full name</label>
                    <input
                        id="name"
                        type="text"
                        wire:model="name"
                        autocomplete="name"
                        placeholder="Ada Lovelace"
                        class="input"
                    />
                    @error('name')
                        <p class="help-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="label">Email address</label>
                    <input
                        id="email"
                        type="email"
                        wire:model="email"
                        autocomplete="email"
                        placeholder="you@example.com"
                        class="input"
                    />
                    @error('email')
                        <p class="help-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="label">Password</label>
                    <input
                        id="password"
                        type="password"
                        wire:model="password"
                        autocomplete="new-password"
                        placeholder="••••••••"
                        class="input"
                    />
                    @error('password')
                        <p class="help-error">{{ $message }}</p>
                    @enderror
                    <p class="mt-1.5 text-xs text-slate-400">Keep it strong — at least 12 characters with mixed case, numbers and symbols.</p>
                </div>

                <div>
                    <label for="password_confirmation" class="label">Confirm password</label>
                    <input
                        id="password_confirmation"
                        type="password"
                        wire:model="password_confirmation"
                        autocomplete="new-password"
                        placeholder="••••••••"
                        class="input"
                    />
                    @error('password_confirmation')
                        <p class="help-error">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-full" wire:loading.attr="disabled" x-data>
                    <span wire:loading.remove wire:target="save">Create account</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <svg class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path d="M4 12a8 8 0 0 1 8-8" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        Creating account…
                    </span>
                </button>
            </form>

            <p class="mt-7 text-center text-sm text-slate-500">
                Already have an account?
                <a href="{{ route('login') }}" class="font-semibold text-brand-600 transition hover:text-brand-700">Sign in</a>
            </p>
        </div>
    </div>
</div>

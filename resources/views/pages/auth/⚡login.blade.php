<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component {
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function mount(): void
    {
        if (auth()->check()) {
            $this->redirectIntended(route('dashboard'));
        }
    }

    public function save(): void
    {
        $credentials = $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Please enter your email address.',
            'password.required' => 'Please enter your password.',
        ]);

        $throttleKey = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', trans('auth.throttle', ['seconds' => $seconds]));

            return;
        }

        if (! Auth::attempt(
            [
                'email' => $credentials['email'],
                'password' => $credentials['password'],
            ],
            $this->remember,
        )) {
            RateLimiter::hit($throttleKey);
            $this->addError('email', trans('auth.failed'));

            return;
        }

        RateLimiter::clear($throttleKey);

        session()->regenerate();

        $this->redirectIntended(route('dashboard'));
    }

    /**
     * Rate limit failed logins by email and client IP.
     */
    private function throttleKey(): string
    {
        return strtolower($this->email).'|'.request()->ip();
    }
};
?>

<div class="animate-fade-up my-4 mx-auto max-w-5xl overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/80 shadow-(--shadow-card-hover) lg:grid lg:grid-cols-2">
    <x-ui.auth-shell />

    <div class="px-6 py-10 sm:px-10 lg:py-14">
        <div class="mx-auto max-w-sm">
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Welcome back</h1>
            <p class="mt-1.5 mb-7 text-sm text-slate-500">Sign in to continue learning.</p>

            @if (session('status'))
                <div class="mb-5 flex items-center gap-2.5 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4.5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    {{ session('status') }}
                </div>
            @endif

            <form wire:submit="save" class="space-y-5">
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
                    <div class="flex items-center justify-between">
                        <label for="password" class="label">Password</label>
                        <a href="{{ route('password.request') }}" class="mb-1.5 text-[13px] font-medium text-brand-600 transition hover:text-brand-700">Forgot password?</a>
                    </div>
                    <input
                        id="password"
                        type="password"
                        wire:model="password"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="input"
                    />
                    @error('password')
                        <p class="help-error">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-2.5 text-sm font-medium text-slate-600">
                    <input type="checkbox" wire:model="remember" class="size-4 rounded accent-brand-600" />
                    Remember me for 30 days
                </label>

                <button type="submit" class="btn btn-primary btn-lg w-full" wire:loading.attr="disabled" x-data>
                    <span wire:loading.remove wire:target="save">Sign in</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <svg class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path d="M4 12a8 8 0 0 1 8-8" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        Signing in…
                    </span>
                </button>
            </form>

            <p class="mt-7 text-center text-sm text-slate-500">
                Don't have an account?
                <a href="{{ route('register') }}" class="font-semibold text-brand-600 transition hover:text-brand-700">Create one free</a>
            </p>
        </div>

        <p class="mt-8 border-t border-slate-100 pt-5 text-center text-xs text-slate-400">
            Instructor or admin?
            <a href="{{ route('filament.admin.auth.login') }}" class="underline transition hover:text-slate-600">Sign in here</a>
        </p>
    </div>
</div>

<?php

use Illuminate\Support\Facades\Password;
use Livewire\Component;

new class extends Component {
    public string $email = '';

    public string $status = '';

    public function sendResetLink(): void
    {
        $this->validate(['email' => ['required', 'string', 'email']]);

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $this->status = __($status);
            $this->resetValidation();
            $this->email = '';

            return;
        }

        $this->addError('email', __($status));
    }
};
?>

<div class="animate-fade-up my-4 mx-auto max-w-5xl overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/80 shadow-(--shadow-card-hover) lg:grid lg:grid-cols-2">
    <x-ui.auth-shell />

    <div class="px-6 py-10 sm:px-10 lg:py-14">
        <div class="mx-auto max-w-sm">
            <span class="grid size-11 place-items-center rounded-xl bg-brand-50 text-brand-600 ring-1 ring-brand-100">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" class="size-5.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.15m19.5 0A2.25 2.25 0 0 0 19.5 6.75h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
            </span>

            <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-slate-900">Forgot your password?</h1>
            <p class="mt-1.5 mb-7 text-sm text-slate-500">No problem — enter the email you registered with and we'll send you a reset link.</p>

            @if ($this->status !== '' && $errors->isEmpty())
                <div class="mb-5 flex items-start gap-2.5 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="mt-0.5 size-4.5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    {{ $this->status }}
                </div>
            @endif

            <form wire:submit="sendResetLink" class="space-y-5">
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

                <button type="submit" class="btn btn-primary btn-lg w-full" wire:loading.attr="disabled" x-data>
                    <span wire:loading.remove wire:target="sendResetLink">Send reset link</span>
                    <span wire:loading wire:target="sendResetLink" class="flex items-center gap-2">
                        <svg class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path d="M4 12a8 8 0 0 1 8-8" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        Sending…
                    </span>
                </button>
            </form>

            <p class="mt-7 text-center text-sm text-slate-500">
                <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 font-semibold text-brand-600 transition hover:text-brand-700">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                    Back to sign in
                </a>
            </p>
        </div>
    </div>
</div>

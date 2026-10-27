<?php

use Livewire\Component;

new class extends Component
{
};
?>

<div class="animate-fade-up py-6" x-data="{ loading: false, showSkeletons: false, dropdown: false }">
    {{-- Intro --}}
    <header class="card mb-8 flex flex-col gap-3 p-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="eyebrow">Design system</p>
            <h1 class="section-title mt-1.5">UI kit & states demo</h1>
            <p class="mt-1 text-sm text-slate-500">Static mockups of every interactive state. Everything here is frontend-only.</p>
        </div>
        <label class="flex shrink-0 items-center gap-2.5 text-sm font-medium text-slate-600">
            <input type="checkbox" x-model="showSkeletons" class="sr-only peer" />
            <span class="relative h-6 w-11 cursor-pointer rounded-full bg-slate-200 transition peer-checked:bg-brand-600">
                <span class="absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
            </span>
            Preview skeletons
        </label>
    </header>

    {{-- Buttons --}}
    <section class="card mb-6 p-6">
        <h2 class="text-base font-bold text-slate-900">Buttons</h2>
        <div class="mt-4 flex flex-wrap gap-2.5">
            <button type="button" class="btn btn-primary btn-md">Primary</button>
            <button type="button" class="btn btn-dark btn-md">Dark</button>
            <button type="button" class="btn btn-secondary btn-md">Secondary</button>
            <button type="button" class="btn btn-ghost btn-md">Ghost</button>
            <button type="button" class="btn btn-danger btn-md">Danger</button>
            <button type="button" class="btn btn-md cursor-not-allowed opacity-60 ring-1 ring-slate-200" disabled>Disabled</button>
        </div>

        <h3 class="mt-6 text-sm font-bold text-slate-900">Loading states</h3>
        <div class="mt-3 flex flex-wrap gap-2.5">
            <button type="button" class="btn btn-primary btn-md">
                <svg class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path d="M4 12a8 8 0 0 1 8-8" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                Saving…
            </button>
            <button type="button" class="btn btn-secondary btn-md" @click="loading = !loading">
                Toggle loading preview
            </button>
        </div>
    </section>

    {{-- Skeletons --}}
    <section class="card mb-6 p-6">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900">Loading skeletons</h2>
            <button type="button" class="btn btn-secondary btn-sm" @click="showSkeletons = !showSkeletons">Toggle</button>
        </div>

        <p class="mt-2 text-sm text-slate-500">Match the course-card layout so pages never jump while loading.</p>

        <div class="mt-4" x-show="showSkeletons" x-cloak>
            <div class="grid gap-5 sm:grid-cols-3">
                @foreach (range(1, 3) as $i)
                    <x-ui.skeleton-card :footer="false" :meta="true" />
                @endforeach
            </div>
        </div>
        <div class="mt-4" x-show="!showSkeletons">
            <div class="grid gap-5 sm:grid-cols-3">
                @foreach ([
                    ['Modern JavaScript, Zero to Deploy', 'Ian Fleming', 'Web Development', 4.8, 214, 49],
                    ['Design Systems with Figma', 'Mina Talha', 'Design', 4.9, 178, 69],
                    ['Python for Data Analysis', 'Yusuf Demir', 'Data Science', 4.7, 331, 94],
                ] as $demoCourse)
                    <x-ui.course-card
                        :title="$demoCourse[0]"
                        :instructor="'By '.$demoCourse[1]"
                        :category="$demoCourse[2]"
                        :rating="$demoCourse[3]"
                        :rating-count="$demoCourse[4]"
                        :price="$demoCourse[5]"
                        :meta="'6 sections · 24 lessons'"
                        :href="'#'"
                    />
                @endforeach
            </div>
        </div>
    </section>

    {{-- Empty & error & success --}}
    <div class="mb-6 grid gap-6 lg:grid-cols-3">
        <section class="card p-6">
            <h2 class="text-base font-bold text-slate-900">Empty state</h2>
            <div class="mt-4">
                <div class="card flex flex-col items-center px-4 py-10 text-center">
                    <span class="relative mb-4 grid size-14 place-items-center"><span class="absolute inset-0 rounded-full bg-brand-50"></span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="relative size-7 text-brand-500"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    </span>
                    <h3 class="text-sm font-bold text-slate-900">No results yet</h3>
                    <p class="mt-1 text-xs text-slate-500">Try another keyword or browse all categories.</p>
                    <button type="button" class="btn btn-secondary btn-sm mt-4">Clear filters</button>
                </div>
            </div>
        </section>

        <section class="card p-6">
            <h2 class="text-base font-bold text-slate-900">Error state</h2>
            <div class="mt-4 rounded-xl bg-rose-50 p-5 text-center ring-1 ring-rose-200">
                <span class="mx-auto grid size-12 place-items-center rounded-full bg-rose-100 text-rose-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.008v.008H12v-.008Z" /></svg>
                </span>
                <h3 class="mt-3 text-sm font-bold text-slate-900">Something went wrong</h3>
                <p class="mt-1 text-xs text-rose-700/80">We couldn't load this section. Check your connection and try again (demo).</p>
                <button type="button" class="btn btn-md mt-4 bg-rose-600 text-white hover:bg-rose-500">Try again</button>
            </div>
        </section>

        <section class="card p-6">
            <h2 class="text-base font-bold text-slate-900">Success & notifications</h2>
            <div class="mt-4 space-y-3">
                <div class="flex gap-2.5 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-4.5 shrink-0 text-emerald-500"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    Course enrolled successfully. Your streak just began!
                </div>
                <div class="flex gap-2.5 rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-700 ring-1 ring-brand-100">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4.5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                    Tip: enable reminders to keep your daily streak alive.
                </div>
                <div class="flex items-start justify-between gap-2.5 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200">
                    <span>Payment verification is in progress.</span>
                    <button type="button" class="shrink-0 text-amber-700 hover:text-amber-900" aria-label="Dismiss">×</button>
                </div>
            </div>
        </section>
    </div>

    {{-- Toasts + modal + dropdown + tooltip --}}
    <div class="mb-6 grid gap-6 lg:grid-cols-3">
        <section class="card p-6">
            <h2 class="text-base font-bold text-slate-900">Toast notifications</h2>
            <p class="mt-1.5 text-sm text-slate-500">Trigger them — a toast appears bottom-right.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary btn-md" @click="$dispatch('toast')">Show toast</button>
            </div>
            <div class="fixed right-5 bottom-6 z-50">
                <x-ui.toast tone="success">
                    <strong class="font-semibold">Nice!</strong> Your demo action worked.
                </x-ui.toast>
            </div>
        </section>

        <section class="card p-6">
            <h2 class="text-base font-bold text-slate-900">Confirmation modal</h2>
            <p class="mt-1.5 mb-4 text-sm text-slate-500">An esc/backdrop-closable dialog pattern.</p>
            <x-ui.modal
                label="Open modal"
                title="Cancel this order?"
                description="The order stays visible in your history, but your payment attempt will be released."
                confirm-label="Cancel order"
                tone="danger"
                trigger-class="btn btn-danger btn-md"
            />
        </section>

        <section class="card relative p-6">
            <h2 class="text-base font-bold text-slate-900">Dropdown & tooltip</h2>
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <div class="relative" @click.outside="dropdown = false">
                    <button type="button" class="btn btn-secondary btn-md" @click="dropdown = !dropdown">
                        Sort by
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                    </button>
                    <div x-show="dropdown" x-cloak x-transition.opacity.duration.120ms class="absolute top-full left-0 z-20 mt-1.5 w-48 rounded-xl bg-white p-1.5 ring-1 ring-slate-200 shadow-(--shadow-pop)">
                        @foreach (['Newest first', 'Highest rated', 'Price: low to high'] as $option)
                            <button type="button" class="{{ $option === 'Newest first' ? 'bg-brand-50 text-brand-700 font-semibold ' : '' }}w-full rounded-lg px-3 py-2 text-left text-sm transition hover:bg-slate-100">{{ $option }}</button>
                        @endforeach
                    </div>
                </div>

                <span class="group relative" tabindex="0">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5 cursor-help text-slate-400 hover:text-slate-600"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                    <span class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 -translate-x-1/2 whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs font-medium text-white opacity-0 shadow-lg transition group-hover:opacity-100 group-focus-within:opacity-100">
                        Prices are computed server-side
                    </span>
                </span>
            </div>
        </section>
    </div>

    {{-- Form controls --}}
    <section class="card mb-10 p-6">
        <h2 class="text-base font-bold text-slate-900">Form controls</h2>
        <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="label" for="demo-text">Text input</label>
                <input id="demo-text" class="input" placeholder="Placeholder text" />
            </div>
            <div>
                <label class="label" for="demo-text-error">Error input</label>
                <input id="demo-text-error" class="input ring-2 ring-rose-400" value="not-an-email" />
                <p class="help-error">Please enter a valid email.</p>
            </div>
            <div>
                <label class="label" for="demo-valid">Valid input</label>
                <input id="demo-valid" class="input ring-2 ring-emerald-400" value="ada@example.com" />
                <p class="mt-1.5 text-sm text-emerald-600">Looks good!</p>
            </div>
            <div>
                <label class="label" for="demo-select">Select</label>
                <div class="relative">
                    <select id="demo-select" class="input select pr-9">
                        <option>Beginner</option>
                        <option>Intermediate</option>
                        <option>Advanced</option>
                    </select>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-slate-400"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                </div>
            </div>
        </div>
    </section>
</div>

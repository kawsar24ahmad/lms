@props(['tone' => 'success'])

<div
    x-data="{
        show: false,
        open() {
            this.show = true;
            clearTimeout(this._t);
            this._t = window.setTimeout(() => this.show = false, 3500);
        },
    }"
    x-cloak
    @toast.window="open()"
    class="pointer-events-none"
>
    <div
        x-show="show"
        x-transition:enter="transition duration-300 ease-out"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="pointer-events-auto flex w-80 items-center gap-3 rounded-xl bg-slate-900 py-3 pr-4 pl-3.5 text-white shadow-(--shadow-pop)"
        role="status"
        aria-live="polite"
    >
        <span class="grid size-8 shrink-0 place-items-center rounded-lg {{ $tone === 'success' ? 'bg-emerald-500/20 text-emerald-300' : ($tone === 'error' ? 'bg-rose-500/20 text-rose-300' : 'bg-brand-500/20 text-brand-300') }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
        </span>

        <div class="min-w-0 flex-1 text-sm">{{ $slot }}</div>

        <button type="button" class="grid size-7 shrink-0 place-items-center rounded-lg text-slate-400 transition hover:bg-white/10 hover:text-white" @click="show = false" aria-label="Dismiss">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
        </button>
    </div>
</div>

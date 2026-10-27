@props(['courseId', 'inCart' => false])

<button type="button" wire:click="toggleCart({{ $courseId }})" wire:loading.attr="disabled"
    wire:target="toggleCart" aria-pressed="{{ $inCart ? 'true' : 'false' }}"
    class="flex w-full items-center justify-between gap-2 rounded-xl px-3 py-2 text-[13px] font-semibold transition-all duration-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 disabled:opacity-60 {{ $inCart ? 'bg-brand-600 text-white shadow-sm hover:bg-brand-700' : 'bg-slate-50 text-slate-700 ring-1 ring-slate-200 hover:bg-white hover:text-brand-700 hover:ring-brand-300' }}">
    <span class="flex items-center gap-2">
        @if ($inCart)
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2"
                stroke="currentColor" class="size-4" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
            In cart
        @else
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                stroke="currentColor" class="size-4" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add to cart
        @endif
    </span>

    <span class="text-[11px] font-medium {{ $inCart ? 'text-white/70' : 'text-slate-400' }}">
        {{ $inCart ? 'Remove' : 'Bundle' }}
    </span>
</button>

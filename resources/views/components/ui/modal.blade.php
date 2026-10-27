@props(['label' => 'Open modal', 'title' => 'Are you sure?', 'description' => null, 'confirmLabel' => 'Confirm', 'cancelLabel' => 'Cancel', 'tone' => 'primary', 'triggerClass' => 'btn btn-secondary btn-md'])

@php
    $confirmTone = $tone === 'danger' ? 'bg-rose-600 text-white hover:bg-rose-500' : 'btn-primary';
@endphp

<div
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
    x-cloak
>
    <button type="button" {{ $attributes->merge(['class' => $triggerClass]) }} @click="open = true">{{ $label }}</button>

    {{-- Overlay --}}
    <div class="fixed inset-0 z-60 grid place-items-center p-4" style="display: none" x-show="open" x-cloak>
        <div
            x-show="open"
            x-transition.opacity.duration.200ms
            @click="open = false"
            class="absolute inset-0 bg-slate-900/50 backdrop-blur-xs"
            aria-hidden="true"
        ></div>

        <div
            x-show="open"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="opacity-0 translate-y-3 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition duration-100 ease-in"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="card relative w-full max-w-sm p-6 shadow-(--shadow-pop)!"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex items-start gap-4">
                <span class="grid size-10 shrink-0 place-items-center rounded-full {{ $tone === 'danger' ? 'bg-rose-100 text-rose-600' : 'bg-brand-100 text-brand-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                </span>

                <div>
                    <h3 class="text-base font-bold text-slate-900">{{ $title }}</h3>
                    @if ($description !== null)
                        <p class="mt-1 text-sm leading-relaxed text-slate-500">{{ $description }}</p>
                    @endif
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="btn btn-secondary btn-md" @click="open = false">{{ $cancelLabel }}</button>
                <button type="button" class="btn btn-md {{ $confirmTone }} {{ $tone === 'danger' ? 'rounded-xl' : '' }}" @click="open = false">{{ $confirmLabel }}</button>
            </div>
        </div>
    </div>
</div>

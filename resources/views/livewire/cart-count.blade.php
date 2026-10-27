@php
    $isEmpty = $count === 0;
    $noun = $count === 1 ? 'course' : 'courses';
@endphp

<a href="{{ route('courses.index') }}" x-data="{ bump: false }"
    x-on:click.prevent="$dispatch('cart-open')"
    x-on:cart-updated.window="bump = true; setTimeout(() => bump = false, 280)"
    title="{{ $isEmpty ? 'Your cart is empty' : $count . ' ' . $noun . ' in cart' }}"
    aria-label="{{ $isEmpty ? 'Cart, empty' : 'Cart, ' . $count . ' ' . $noun }}"
    aria-haspopup="dialog" aria-controls="cart-drawer"
    class="relative hidden size-10 items-center justify-center rounded-full text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 sm:flex">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"
        class="size-6" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
    </svg>

    @if (! $isEmpty)
        <span
            class="absolute -top-0.5 -right-0.5 grid h-4.5 min-w-4.5 place-items-center rounded-full bg-brand-600 px-1 text-[10px] font-bold text-white ring-2 ring-white transition-transform duration-200"
            :class="bump ? 'scale-125' : 'scale-100'">
            {{ $count > 99 ? '99+' : $count }}
        </span>
    @endif
</a>

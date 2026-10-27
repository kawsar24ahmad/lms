<div
    x-data="{
        open: false,
        toasts: [],
        bump: false,
        toastSequence: 0,
        notify(message, tone = 'success') {
            const id = ++this.toastSequence;
            this.toasts.push({ id, message, tone });
            window.setTimeout(() => {
                this.toasts = this.toasts.filter((toast) => toast.id !== id);
            }, 3200);
        },
        bumpCounter() {
            this.bump = true;
            window.setTimeout(() => this.bump = false, 280);
        },
        openCart() {
            this.open = true;
            window.setTimeout(() => this.$refs.cartClose?.focus(), 60);
        },
        destroy() {
            document.body.classList.remove('overflow-hidden');
        },
    }"
    x-effect="document.body.classList.toggle('overflow-hidden', open)"
    x-on:keydown.escape.window="open = false"
    x-on:cart-open.window="openCart()"
    x-on:cart-added.window="notify('Added to cart: ' + $event.detail.title); bumpCounter()"
    x-on:cart-removed.window="notify('Removed from cart', 'removed')"
    x-on:cart-cleared.window="notify('Cart cleared', 'removed')">

    {{-- ============================ Toast notifications ============================ --}}
    <div class="pointer-events-none fixed inset-x-4 top-4 z-[80] flex flex-col items-center gap-2 sm:inset-x-auto sm:right-6 sm:top-6 sm:items-end"
        aria-live="polite" aria-atomic="true">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-transition:enter="transition duration-300 ease-out"
                x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0 translate-y-2"
                class="pointer-events-auto flex w-full max-w-sm items-center gap-3 rounded-xl bg-slate-900 py-3 pr-4 pl-3.5 text-white shadow-(--shadow-pop)"
                role="status">
                <span class="grid size-8 shrink-0 place-items-center rounded-lg"
                    :class="toast.tone === 'removed' ? 'bg-rose-500/20 text-rose-300' : 'bg-emerald-500/20 text-emerald-300'">
                    <svg x-show="toast.tone !== 'removed'" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4.5"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    <svg x-show="toast.tone === 'removed'" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4.5"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </span>

                <p class="min-w-0 flex-1 text-sm" x-text="toast.message"></p>

                <button type="button" x-on:click="toasts = toasts.filter((item) => item.id !== toast.id)"
                    class="grid size-7 shrink-0 place-items-center rounded-lg text-slate-400 transition hover:bg-white/10 hover:text-white"
                    aria-label="Dismiss notification">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" class="size-4" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </template>
    </div>

    {{-- ============================ Floating cart bar ============================ --}}
    @if ($this->cartCount > 0)
        <div data-cart-bar
            class="fixed inset-x-0 bottom-0 z-40 px-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] sm:bottom-5 sm:px-6 sm:pb-0">
            <div
                class="mx-auto flex max-w-3xl animate-fade-up items-center gap-2.5 rounded-2xl bg-slate-900/95 p-2.5 pl-3 text-white shadow-(--shadow-pop) ring-1 ring-white/10 backdrop-blur-md sm:gap-4 sm:p-3 sm:pl-4">
                <div class="hidden shrink-0 -space-x-2.5 sm:flex">
                    @foreach ($this->cartItems->take(3) as $item)
                        <div wire:key="bar-thumb-{{ $item->id }}"
                            class="relative inline-block size-9 overflow-hidden rounded-lg ring-2 ring-slate-900">
                            @if ($item->image_path)
                                <img src="{{ Storage::disk('public')->url($item->image_path) }}" alt=""
                                    class="size-full object-cover">
                            @else
                                <x-ui.thumb :seed="$item->title" class="size-9" />
                            @endif
                        </div>
                    @endforeach

                    @if ($this->cartCount > 3)
                        <div
                            class="grid size-9 place-items-center rounded-lg bg-slate-700 text-[10px] font-bold text-white ring-2 ring-slate-900">
                            +{{ $this->cartCount - 3 }}
                        </div>
                    @endif
                </div>

                <button type="button" x-on:click="openCart()" class="min-w-0 flex-1 text-left"
                    aria-label="View cart">
                    <span class="flex items-center gap-1.5 text-[11px] font-medium text-slate-400">
                        <span class="relative flex size-2 shrink-0">
                            <span
                                class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex size-2 rounded-full bg-emerald-400"></span>
                        </span>
                        <span
                            class="inline-flex min-w-5 items-center justify-center rounded-full bg-brand-600 px-1.5 py-0.5 text-[10px] font-bold text-white transition-transform duration-200"
                            :class="bump ? 'scale-125' : 'scale-100'">
                            {{ $this->cartCount }}
                        </span>
                        <span class="truncate">course{{ $this->cartCount === 1 ? '' : 's' }} in cart</span>
                    </span>
                    <span class="mt-0.5 block truncate text-sm font-bold text-white">
                        ${{ number_format($this->cartSubtotal, 2) }}
                        @if ($this->cartSavings > 0)
                            <span class="ml-1.5 text-[11px] font-medium text-emerald-400">Save
                                ${{ number_format($this->cartSavings, 2) }}</span>
                        @endif
                    </span>
                </button>

                <button type="button" x-on:click="openCart()"
                    class="btn btn-ghost btn-sm hidden text-slate-300 hover:bg-white/10 hover:text-white sm:inline-flex">
                    View cart
                </button>

                <button type="button" wire:click="startCheckout" wire:loading.attr="disabled"
                    wire:target="startCheckout" class="btn btn-primary btn-md shrink-0 shadow-sm">
                    <span wire:loading.remove wire:target="startCheckout" class="flex items-center gap-1.5">
                        Checkout
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="size-3.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </span>
                    <span wire:loading wire:target="startCheckout" class="flex items-center gap-1.5">
                        <svg class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                        </svg>
                        <span class="hidden sm:inline">Preparing...</span>
                    </span>
                </button>
            </div>
        </div>
    @endif

    {{-- ============================ Cart drawer ============================ --}}
    <div x-cloak>
        <div x-show="open" x-transition.opacity.duration.200ms x-on:click="open = false"
            class="fixed inset-0 z-[60] bg-slate-900/50 backdrop-blur-xs"></div>

        <aside id="cart-drawer" data-cart-drawer x-show="open" x-cloak x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-y-full sm:translate-y-0 sm:translate-x-full"
            x-transition:enter-end="translate-y-0 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0 sm:translate-x-0"
            x-transition:leave-end="translate-y-full sm:translate-y-0 sm:translate-x-full"
            class="fixed inset-x-0 bottom-0 z-[60] flex max-h-[88vh] flex-col overflow-hidden rounded-t-3xl bg-white shadow-(--shadow-pop) sm:inset-y-0 sm:right-0 sm:left-auto sm:h-full sm:max-h-none sm:w-full sm:max-w-md sm:rounded-none"
            role="dialog" aria-modal="true" aria-labelledby="cart-drawer-title">
            <div class="flex shrink-0 items-center justify-between gap-4 border-b border-slate-100 px-5 py-4">
                <div class="flex min-w-0 items-center gap-2.5">
                    <span
                        class="grid size-9 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600 ring-1 ring-brand-100">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                            stroke="currentColor" class="size-5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h2 id="cart-drawer-title" class="text-base font-bold text-slate-900">Your cart</h2>
                        <p class="text-xs text-slate-500">
                            {{ $this->cartCount }} course{{ $this->cartCount === 1 ? '' : 's' }} selected
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-1">
                    @if ($this->cartCount > 0)
                        <button type="button" wire:click="clearCart" wire:loading.attr="disabled"
                            wire:target="clearCart"
                            class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-500 transition hover:bg-rose-50 hover:text-rose-600 disabled:opacity-50">
                            Clear all
                        </button>
                    @endif

                    <button type="button" x-on:click="open = false" x-ref="cartClose"
                        class="grid size-9 place-items-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                        aria-label="Close cart">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="size-5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 py-4">
                @if ($this->cartItems->isEmpty())
                    <div class="flex h-full min-h-64 flex-col items-center justify-center text-center">
                        <span
                            class="grid size-14 place-items-center rounded-2xl bg-slate-50 text-slate-400 ring-1 ring-slate-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.6" stroke="currentColor" class="size-7" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                            </svg>
                        </span>
                        <p class="mt-4 text-sm font-bold text-slate-900">Your cart is empty</p>
                        <p class="mt-1 max-w-xs text-sm leading-relaxed text-slate-500">
                            Add courses from the catalog to build your learning bundle.
                        </p>
                        <a href="{{ route('courses.index') }}" class="btn btn-secondary btn-md mt-4"
                            x-on:click="open = false">
                            Browse courses
                        </a>
                    </div>
                @else
                    <ul class="space-y-3">
                        @foreach ($this->cartItems as $item)
                            @php
                                $itemEffective = $item->effectivePrice();
                                $itemFree = $itemEffective <= 0;
                            @endphp

                            <li wire:key="cart-item-{{ $item->id }}"
                                class="flex animate-fade-up gap-3 rounded-2xl bg-slate-50/80 p-3 ring-1 ring-slate-100 transition hover:bg-slate-50 hover:ring-slate-200">
                                <a href="{{ route('courses.show', $item) }}"
                                    class="relative block h-16 w-24 shrink-0 overflow-hidden rounded-xl"
                                    tabindex="-1">
                                    @if ($item->image_path)
                                        <img src="{{ Storage::disk('public')->url($item->image_path) }}"
                                            alt="{{ $item->title }}" class="size-full object-cover">
                                    @else
                                        <x-ui.thumb :seed="$item->title" :label="$item->title" class="size-full" />
                                    @endif
                                </a>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <a href="{{ route('courses.show', $item) }}"
                                            class="line-clamp-2 text-[13px] leading-snug font-semibold text-slate-900 transition hover:text-brand-700">
                                            {{ $item->title }}
                                        </a>
                                        <button type="button" wire:click="removeFromCart({{ $item->id }})"
                                            wire:loading.attr="disabled" wire:target="removeFromCart"
                                            class="grid size-7 shrink-0 place-items-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 disabled:opacity-50"
                                            aria-label="Remove {{ $item->title }} from cart">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                class="size-4" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 18 18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>

                                    <p class="mt-0.5 truncate text-xs text-slate-500">
                                        {{ $item->instructor?->name ?? 'EduLearn team' }}
                                    </p>

                                    <div class="mt-1.5 flex flex-wrap items-baseline gap-1.5">
                                        <span
                                            class="text-sm font-bold {{ $itemFree ? 'text-emerald-600' : 'text-slate-900' }}">
                                            {{ $itemFree ? 'Free' : '$' . number_format($itemEffective, 2) }}
                                        </span>
                                        @if ($item->isDiscounted() && (float) $item->price > 0)
                                            <span class="text-xs font-medium text-slate-400 line-through">
                                                ${{ number_format((float) $item->price, 2) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @if ($this->cartCount > 0)
                <div class="shrink-0 border-t border-slate-100 bg-white px-5 py-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
                    <dl class="space-y-1.5 text-sm">
                        <div class="flex items-center justify-between text-slate-500">
                            <dt>Subtotal</dt>
                            <dd
                                class="{{ $this->cartSavings > 0 ? 'text-slate-400 line-through' : 'font-medium text-slate-700' }}">
                                ${{ number_format($this->cartOriginalTotal, 2) }}
                            </dd>
                        </div>

                        @if ($this->cartSavings > 0)
                            <div class="flex items-center justify-between text-emerald-600">
                                <dt class="flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.8" stroke="currentColor" class="size-4" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M6 6h.008v.008H6V6Z" />
                                    </svg>
                                    Course discounts
                                </dt>
                                <dd class="font-semibold">−${{ number_format($this->cartSavings, 2) }}</dd>
                            </div>
                        @endif

                        <div
                            class="flex items-baseline justify-between border-t border-slate-100 pt-2.5 text-slate-900">
                            <dt class="text-sm font-semibold">Total</dt>
                            <dd class="text-xl font-extrabold tracking-tight">
                                ${{ number_format($this->cartSubtotal, 2) }}
                            </dd>
                        </div>
                    </dl>

                    <button type="button" wire:click="startCheckout" wire:loading.attr="disabled"
                        wire:target="startCheckout" class="btn btn-primary btn-lg mt-4 w-full shadow-(--shadow-pop)">
                        <span wire:loading.remove wire:target="startCheckout" class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.8" stroke="currentColor" class="size-4" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                            Proceed to Secure Checkout
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="2" stroke="currentColor" class="size-4" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                        </span>
                        <span wire:loading wire:target="startCheckout" class="flex items-center gap-2">
                            <svg class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                            </svg>
                            Preparing checkout...
                        </span>
                    </button>

                    <p class="mt-2.5 flex items-center justify-center gap-1.5 text-center text-[11px] text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                            stroke="currentColor" class="size-3.5 shrink-0" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        30-day money-back guarantee · Instant lifetime access
                    </p>
                </div>
            @endif
        </aside>
    </div>
</div>

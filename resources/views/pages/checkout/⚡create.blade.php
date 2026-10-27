<?php

use App\Models\Course;
use App\Services\CourseCart;
use App\Services\OrderService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    /**
     * The slugs of the courses to purchase, passed from the catalog.
     */
    #[Url]
    public array $courses = [];

    /**
     * The placed order, when the checkout has been submitted.
     */
    public ?array $placedOrder = null;

    public function mount()
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $this->courses = array_values(array_filter($this->courses, fn ($slug) => is_string($slug) && $slug !== ''));
    }

    /**
     * The selectable courses behind the current selection.
     */
    #[Computed]
    public function selectedCourses(): Collection
    {
        return Course::query()
            ->published()
            ->whereIn('slug', $this->courses)
            ->orderBy('title')
            ->get();
    }

    /**
     * Place the order. Pricing is computed server-side from the course
     * records; free orders grant access immediately, paid orders stay
     * pending until payment is verified by the administration.
     */
    public function place(OrderService $orders, CourseCart $cart): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        try {
            $order = $orders->placeOrder(
                auth()->user(),
                $this->selectedCourses->modelKeys(),
            );
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?? 'Unable to place the order.';

            $this->addError('checkout', $message);

            return;
        }

        // Clear the shared cart once the order exists so purchased courses do
        // not stay selected, and tell the header badge and global drawer.
        $cart->clear();

        $this->dispatch('cart-updated', cart: [], count: 0);

        if (round((float) $order->final_amount, 2) <= 0.0) {
            $course = $this->selectedCourses->first();

            if ($course !== null) {
                session()->flash('success', 'You are enrolled in '.($course->title).'!');

                $this->redirectRoute('courses.show', ['course' => $course->slug]);

                return;
            }
        }

        $this->placedOrder = [
            'number' => $order->number,
            'due' => $order->dueAmount(),
        ];
    }
};
?>

<div class="animate-fade-up">
    <a href="{{ route('courses.index') }}" class="mt-2 mb-5 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-brand-600">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
        Back to courses
    </a>

    @if ($placedOrder !== null)
        <div class="card mx-auto flex max-w-lg flex-col items-center px-6 py-14 text-center">
            <span class="relative grid size-16 place-items-center">
                <span class="absolute inset-0 rounded-full bg-emerald-50"></span>
                <span class="relative grid size-12 place-items-center rounded-full bg-emerald-100 text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                </span>
            </span>

            <h1 class="mt-5 text-xl font-extrabold text-slate-900">Order {{ $placedOrder['number'] }} placed</h1>
            <p class="mt-2 max-w-sm text-sm leading-relaxed text-slate-500">
                Your order is awaiting payment. As soon as your payment of
                <span class="font-semibold text-slate-900">${{ number_format($placedOrder['due'], 2) }}</span>
                is received and verified you will get full access to the courses.
            </p>
            <div class="mt-6 flex gap-2">
                <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-md">My learning</a>
                <a href="{{ route('courses.index') }}" class="btn btn-primary btn-md">Continue browsing</a>
            </div>
        </div>
    @else
        <div class="grid gap-8 py-2 lg:grid-cols-[1fr_360px] lg:items-start">
            <div class="min-w-0">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Checkout</h1>
                <p class="mt-1.5 text-sm text-slate-500">Review your selection below — you'll get access to every course in one order.</p>

                @if ($this->selectedCourses->isEmpty())
                    <x-ui.empty-state
                        title="No purchasable courses selected"
                        description="Nothing is selected yet. Head to the catalog and add the courses you'd like to take."
                        icon="courses">
                        <x-slot name="actions">
                            <a href="{{ route('courses.index') }}" class="btn btn-primary btn-md">Browse courses</a>
                        </x-slot>
                    </x-ui.empty-state>
                @else
                    <div class="card mt-6 overflow-hidden">
                        <ul class="divide-y divide-slate-100">
                            @php $subtotal = 0; $total = 0; @endphp
                            @foreach ($this->selectedCourses as $course)
                                @php
                                    $original = (float) $course->price;
                                    $final = $course->effectivePrice();
                                    $subtotal += $original;
                                    $total += $final;
                                @endphp

                                <li class="flex items-center gap-4 p-4 sm:p-5" wire:key="course-{{ $course->id }}">
                                    <x-ui.thumb :seed="$course->title" class="h-14 w-24 shrink-0 rounded-xl" :label="$course->title" />

                                    <div class="min-w-0 grow">
                                        <p class="truncate font-semibold text-slate-900">{{ $course->title }}</p>
                                        <p class="truncate text-xs text-slate-500">by {{ $course->instructor?->name }}</p>
                                    </div>

                                    <div class="shrink-0 text-right text-sm">
                                        @if ($course->isDiscounted() && $original > 0)
                                            <span class="mr-1 text-xs text-slate-400 line-through">${{ number_format($original, 2) }}</span>
                                        @endif
                                        <span class="{{ $final == 0 ? 'font-semibold text-emerald-600' : 'font-bold text-slate-900' }}">
                                            {{ $final == 0 ? 'Free' : '$'.number_format($final, 2) }}
                                        </span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            {{-- Order summary --}}
            <aside class="card p-6 lg:sticky lg:top-24">
                <h2 class="text-base font-bold text-slate-900">Order summary</h2>

                @if ($this->selectedCourses->isNotEmpty())
                    <dl class="mt-5 space-y-2.5 text-sm">
                        <div class="flex justify-between text-slate-600">
                            <dt>Original price</dt>
                            <dd>${{ number_format($subtotal, 2) }}</dd>
                        </div>
                        <div class="flex justify-between font-medium text-emerald-600">
                            <dt>Course discounts</dt>
                            <dd>−${{ number_format($subtotal - $total, 2) }}</dd>
                        </div>
                        <div class="border-t border-slate-100 pt-3">
                            <div class="flex items-baseline justify-between text-base font-extrabold text-slate-900">
                                <dt>Total due today</dt>
                                <dd>${{ number_format($total, 2) }}</dd>
                            </div>
                        </div>
                    </dl>

                    <button
                        type="button"
                        wire:click="place"
                        wire:loading.attr="disabled"
                        class="btn btn-primary btn-lg mt-6 w-full"
                        x-data
                    >
                        <span wire:loading.remove wire:target="place">
                            {{ $total > 0 ? 'Place order — $'.number_format($total, 2) : 'Enroll for free' }}
                        </span>
                        <span wire:loading wire:target="place" class="flex items-center gap-2">
                            <svg class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path d="M4 12a8 8 0 0 1 8-8" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                            Placing order…
                        </span>
                    </button>

                    <p class="mt-3.5 flex justify-center text-center text-xs leading-relaxed text-slate-400">
                        Free orders grant access instantly. For paid orders access unlocks as soon as your payment is verified.
                    </p>
                @else
                    <p class="mt-4 text-sm text-slate-500">Your summary will appear here once you select courses.</p>
                @endif

                <div class="mt-5 space-y-2 border-t border-slate-100 pt-5 text-xs text-slate-500">
                    <p class="flex items-center gap-2"><span class="text-emerald-500">✓</span> 30-day satisfaction guarantee</p>
                    <p class="flex items-center gap-2"><span class="text-emerald-500">✓</span> Full lifetime access</p>
                    <p class="flex items-center gap-2"><span class="text-emerald-500">✓</span> Certificates for completed courses</p>
                </div>
            </aside>
        </div>

        @error('checkout')
            <div class="mt-6 max-w-md rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 ring-1 ring-rose-200">{{ $message }}</div>
        @enderror
    @endif
</div>

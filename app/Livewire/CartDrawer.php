<?php

namespace App\Livewire;

use App\Models\Course;
use App\Services\CourseCart;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The site-wide cart: a floating summary bar and a slide-over drawer that are
 * present on every page. It owns the cart UI, while the shared CourseCart
 * service owns the session data so any page can add or remove courses.
 */
class CartDrawer extends Component
{
    /**
     * A local mirror of the shared session cart, refreshed through events.
     *
     * @var array<int, bool>
     */
    public array $cart = [];

    /**
     * Load the persisted selection and discard stale courses.
     */
    public function mount(CourseCart $cart): void
    {
        $this->cart = $cart->prune();
    }

    /**
     * Keep the drawer in sync when any page changes the selection.
     *
     * @param  array<int, bool>  $cart
     */
    #[On('cart-updated')]
    public function syncCart(array $cart = [], ?int $count = null): void
    {
        $this->cart = $cart !== [] ? $cart : app(CourseCart::class)->all();
    }

    /**
     * Remove a course from the cart from inside the drawer.
     */
    public function removeFromCart(int $courseId, CourseCart $cart): void
    {
        $cart->remove($courseId);

        $this->cart = $cart->all();
        $count = $this->cartCount;

        $this->dispatch('cart-removed', count: $count);
        $this->dispatch('cart-updated', cart: $this->cart, count: $count);
    }

    /**
     * Empty the whole cart from inside the drawer.
     */
    public function clearCart(CourseCart $cart): void
    {
        $cart->clear();

        $this->cart = [];

        $this->dispatch('cart-cleared');
        $this->dispatch('cart-updated', cart: [], count: 0);
    }

    /**
     * Send the student to checkout with every selected published course.
     */
    public function startCheckout(): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $slugs = $this->cartItems->pluck('slug')->all();

        if ($slugs === []) {
            return;
        }

        $this->redirectRoute('checkout.create', ['courses' => $slugs]);
    }

    /**
     * The selected published courses in the order they were added.
     *
     * @return Collection<int, Course>
     */
    #[Computed]
    public function cartItems(): Collection
    {
        $courseIds = array_keys(array_filter($this->cart));

        if ($courseIds === []) {
            return collect();
        }

        $positions = array_flip($courseIds);

        return Course::query()
            ->published()
            ->with('instructor')
            ->whereIn('id', $courseIds)
            ->get()
            ->sortBy(fn (Course $course): int => $positions[$course->id] ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * How many courses are currently selected for checkout.
     */
    #[Computed]
    public function cartCount(): int
    {
        return $this->cartItems->count();
    }

    /**
     * What the student pays today for the selected courses.
     */
    #[Computed]
    public function cartSubtotal(): float
    {
        return round((float) $this->cartItems->sum(
            fn (Course $course): float => $course->effectivePrice(),
        ), 2);
    }

    /**
     * The undiscounted list total, used to show the savings.
     */
    #[Computed]
    public function cartOriginalTotal(): float
    {
        return round((float) $this->cartItems->sum(
            fn (Course $course): float => $course->isDiscounted()
                ? (float) $course->price
                : $course->effectivePrice(),
        ), 2);
    }

    /**
     * The total amount saved through the courses' own discounts.
     */
    #[Computed]
    public function cartSavings(): float
    {
        return round(max(0, $this->cartOriginalTotal - $this->cartSubtotal), 2);
    }

    public function render(): View
    {
        return view('livewire.cart-drawer');
    }
}

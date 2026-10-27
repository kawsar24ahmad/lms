<?php

namespace App\Livewire;

use App\Services\CourseCart;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CartCount extends Component
{
    /**
     * The number of published courses currently selected in the shared cart.
     */
    public int $count = 0;

    public function mount(CourseCart $cart): void
    {
        $this->count = $cart->count();
    }

    /**
     * Refresh the header badge whenever any page or the drawer changes the cart.
     *
     * @param  array<int, bool>  $cart
     */
    #[On('cart-updated')]
    public function syncCount(array $cart = [], ?int $count = null): void
    {
        $this->count = $count ?? app(CourseCart::class)->count();
    }

    public function render(): View
    {
        return view('livewire.cart-count');
    }
}

<?php

use App\Models\Category;
use App\Models\Course;
use App\Services\CourseCart;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    /**
     * The selected parent category id, or null for all categories.
     */
    #[Url(history: true)]
    public ?int $category = null;

    #[Url(history: true)]
    public string $search = '';

    /**
     * Presentation-only course ordering.
     */
    #[Url(history: true)]
    public string $sortBy = 'newest';

    /**
     * A read-only mirror of the session-backed cart, refreshed whenever the
     * global drawer or another page changes the selection.
     *
     * @var array<int, bool>
     */
    public array $cart = [];

    /**
     * Load the shared cart and drop stale or unpublished selections.
     */
    public function mount(CourseCart $cart): void
    {
        $this->cart = $cart->prune();
    }

    /**
     * Reset pagination whenever a filter changes.
     */
    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSortBy(): void
    {
        $this->resetPage();
    }

    /**
     * Add a published course to the cart, or remove it when already selected.
     */
    public function toggleCart(int $courseId, CourseCart $cart): void
    {
        $course = Course::query()->published()->find($courseId);

        if ($course === null) {
            return;
        }

        $added = $cart->toggle($course);

        $this->cart = $cart->all();
        $count = $cart->count();

        if ($added) {
            $this->dispatch('cart-added', title: $course->title, count: $count);
        } else {
            $this->dispatch('cart-removed', count: $count);
        }

        $this->dispatch('cart-updated', cart: $this->cart, count: $count);
    }

    /**
     * Keep the card states in sync when the global drawer changes the cart.
     *
     * @param  array<int, bool>  $cart
     */
    #[On('cart-updated')]
    public function syncCart(array $cart = [], ?int $count = null): void
    {
        $this->cart = $cart !== [] ? $cart : app(CourseCart::class)->all();
    }

    #[Computed]
    public function categories()
    {
        return Category::query()
            ->active()
            ->whereNull('parent_id')
            ->with('children')
            ->get();
    }

    #[Computed]
    public function courses()
    {
        return Course::query()
            ->published()
            ->with(['category', 'instructor'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->when($this->category, function (Builder $query) {
                $query->whereIn('category_id', [
                    $this->category,
                    ...Category::query()
                        ->where('parent_id', $this->category)
                        ->pluck('id'),
                ]);
            })
            ->when($this->search !== '', function (Builder $query) {
                $query->where(function (Builder $query) {
                    $term = '%' . $this->search . '%';

                    $query->where('title', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->when($this->sortBy === 'highest-rated', fn(Builder $query) => $query->orderByDesc('reviews_avg_rating'))
            ->when($this->sortBy === 'price-low', fn(Builder $query) => $query->orderByRaw(
                'ROUND(CASE WHEN discount_type = ? AND discount_amount > 0 THEN CASE WHEN price * (1 - discount_amount / 100) < 0 THEN 0 ELSE price * (1 - discount_amount / 100) END WHEN discount_type = ? AND discount_amount > 0 THEN CASE WHEN price - discount_amount < 0 THEN 0 ELSE price - discount_amount END ELSE price END, 2) ASC',
                [Course::DISCOUNT_TYPE_PERCENT, Course::DISCOUNT_TYPE_FIXED],
            ))
            ->when($this->sortBy === 'popular', fn(Builder $query) => $query->orderByDesc('reviews_count'))
            ->latest('published_at')
            ->orderByDesc('id')
            ->paginate(9);
    }
};
?>

<div class="animate-fade-in">

    {{-- ============================ Page head ============================ --}}
    <div class="pb-8 pt-4">
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="transition hover:text-brand-600">Home</a>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                stroke="currentColor" class="size-3">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
            <span class="text-slate-600">Courses</span>
        </nav>

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">All courses</h1>
                <p class="mt-1.5 text-sm text-slate-500">
                    {{ $this->courses->total() }} course{{ $this->courses->total() === 1 ? '' : 's' }}
                    @if ($this->search !== '')
                        matching “<span class="font-semibold text-slate-700">{{ $this->search }}</span>”
                    @endif
                    — filter, sort and start learning today.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <div class="relative">
                    <select wire:model.live="sortBy" class="input select pr-9 text-sm" aria-label="Sort courses">
                        <option value="newest">Newest</option>
                        <option value="popular">Most popular</option>
                        <option value="highest-rated">Highest rated</option>
                        <option value="price-low">Price · low to high</option>
                    </select>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor"
                        class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-slate-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================ Content ============================ --}}
    <div class="grid gap-8 pb-4 lg:grid-cols-[240px_1fr]">
        {{-- Filters sidebar --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="card p-4 sm:p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-900">Category</h2>
                    @if ($category !== null)
                        <button type="button" wire:click="$set('category', null)"
                            class="text-xs font-semibold text-brand-600 transition hover:text-brand-700">
                            Clear
                        </button>
                    @endif
                </div>

                <div class="mt-3 space-y-1">
                    <button type="button" wire:click="$set('category', null)"
                        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm transition {{ $category === null ? 'bg-brand-50 font-semibold text-brand-700' : 'text-slate-600 hover:bg-slate-50' }}">
                        All categories
                    </button>

                    @foreach ($this->categories as $rootCategory)
                        <button type="button" wire:click="$set('category', {{ $rootCategory->id }})"
                            class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm transition {{ $category === $rootCategory->id ? 'bg-brand-50 font-semibold text-brand-700' : 'text-slate-600 hover:bg-slate-50' }}">
                            {{ $rootCategory->name }}
                            @if ($category !== $rootCategory->id)
                                <span class="text-xs text-slate-400">{{ $rootCategory->children->count() }} topics</span>
                            @endif
                        </button>
                    @endforeach
                </div>

                <div class="mt-5 border-t border-slate-100 pt-5">
                    <h2 class="mb-3 text-sm font-bold text-slate-900">Search</h2>
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor"
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input type="search" placeholder="Search courses..." wire:model.live.debounce.300ms="search"
                            class="input pl-9 text-sm" />
                    </div>
                </div>

                <div class="mt-5 rounded-xl bg-brand-50 p-4 ring-1 ring-brand-100/80">
                    <p class="flex items-center gap-1.5 text-[13px] font-bold text-brand-800">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                            stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                        Bundle & save
                    </p>
                    <p class="mt-1 text-xs leading-relaxed text-brand-700/80">
                        Add courses below and check out in one order — pick as many as you like.
                    </p>
                </div>
            </div>
        </aside>

        {{-- Course grid --}}
        <div>
            {{-- Loading skeletons --}}
            <div class="w-full" wire:loading wire:target="search, category, sortBy, courses">
                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach (range(1, 9) as $skel)
                        <x-ui.skeleton-card />
                    @endforeach
                </div>
            </div>

            <div wire:loading.remove wire:target="search, category, sortBy, courses">
                @if ($this->courses->isEmpty())
                    <x-ui.empty-state title="No courses found"
                        description="Nothing matches the current filters. Try a different keyword or browse all categories."
                        icon="search">
                        <x-slot name="actions">
                            <button type="button" wire:click="$set('search', '')" class="btn btn-secondary btn-md">Clear
                                search</button>
                            <button type="button" wire:click="$set('category', null)" class="btn btn-primary btn-md">Browse
                                everything</button>
                        </x-slot>
                    </x-ui.empty-state>
                @else
                    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($this->courses as $course)
                            @php
        $effective = $course->isDiscounted() ? $course->effectivePrice() : $course->price;
        $free = $effective == 0;
        $rating = $course->reviews_avg_rating !== null ? (float) $course->reviews_avg_rating : null;
        $inCart = !empty($cart[$course->id]);
                            @endphp

                            <div wire:key="course-{{ $course->id }}"
                                class="relative rounded-2xl transition-all duration-300 {{ $inCart ? 'ring-2 ring-brand-600/60 ring-offset-2 ring-offset-white' : '' }}">
                                @if ($inCart)
                                    <span
                                        class="badge pointer-events-none absolute top-3 right-3 z-10 animate-fade-in bg-white/95 text-brand-700 shadow-sm ring-1 ring-brand-200 backdrop-blur">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                                            stroke="currentColor" class="size-3" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                        In Cart
                                    </span>
                                @endif

                                <x-ui.course-card :title="$course->title" :goal="str($course->description ?? '')->limit(90)"
                                    :instructor="'By ' . ($course->instructor?->name ?? 'EduLearn team')"
                                    :category="$course->category?->name ?? 'General'" :rating="$rating"
                                    :rating-count="$course->reviews_count" :price="$free ? 'Free' : number_format((float) $effective, 2)"
                                    :original-price="($course->isDiscounted() && $course->price > 0) ? number_format((float) $course->price, 2) : null"
                                    :href="route('courses.show', $course)">
                                    <x-slot name="footer">
                                        <x-ui.cart-button :course-id="$course->id" :in-cart="$inCart" />
                                    </x-slot>
                                </x-ui.course-card>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-10">
                        {{ $this->courses->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

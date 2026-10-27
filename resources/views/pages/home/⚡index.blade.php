<?php

use App\Models\Course;
use App\Models\User;
use App\Services\CourseCart;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
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
     * The published courses shown in the featured and trending sections.
     *
     * @return Collection<int, Course>
     */
    #[Computed]
    public function courses(): Collection
    {
        return Course::query()
            ->published()
            ->with(['category', 'instructor'])
            ->get();
    }

    /**
     * The instructors shown in the showcase section.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function instructors(): Collection
    {
        return User::query()
            ->whereHas('instructor')
            ->with('instructor')
            ->get();
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
     * Keep the home page cards in sync when the global drawer changes the cart.
     *
     * @param  array<int, bool>  $cart
     */
    #[On('cart-updated')]
    public function syncCart(array $cart = [], ?int $count = null): void
    {
        $this->cart = $cart !== [] ? $cart : app(CourseCart::class)->all();
    }
};
?>

<div class="animate-fade-up -mx-4 sm:-mx-6 lg:-mx-8">
    {{-- ============================ Hero ============================ --}}
@php
$hero = \App\Models\HeroSection::with('featuredCourse.instructor')->first();
@endphp

<section class="relative overflow-hidden bg-slate-950">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div
            class="absolute -top-32 left-1/2 h-[36rem] w-[36rem] -translate-x-1/2 rounded-full bg-brand-600/20 blur-[110px]">
        </div>
        <div class="absolute top-24 right-[-8rem] h-80 w-80 rounded-full bg-sky-500/10 blur-[90px]"></div>
        <svg class="absolute inset-0 size-full opacity-[0.06]" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="hero-grid" width="34" height="34" patternUnits="userSpaceOnUse">
                    <path d="M34 0H0v34" fill="none" stroke="white" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#hero-grid)" />
        </svg>
    </div>

    <div
        class="relative mx-auto grid max-w-7xl items-center gap-14 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:gap-8 lg:px-8 lg:py-24">
        <div class="max-w-xl">
            @if($hero?->badge_is_active && $hero?->badge_text)
                <span class="badge bg-white/10 text-brand-200 ring-1 ring-white/15">
                    <span class="size-1.5 rounded-full bg-{{ $hero->badge_status_color ?? 'emerald-400' }}"></span>
                    {{ $hero->badge_text }}
                </span>
            @endif

            <h1
                class="mt-5 text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-[3.4rem] lg:leading-[1.1]">
                {{ $hero?->title_heading ?? 'Learn skills that' }}
                <span class="bg-gradient-to-r from-brand-300 via-sky-300 to-emerald-300 bg-clip-text text-transparent">
                    {{ $hero?->title_highlight ?? 'actually matter' }}
                </span>
                {{ $hero?->title_subheading ?? 'to your career.' }}
            </h1>

            <p class="mt-5 max-w-lg text-base leading-relaxed text-slate-300 sm:text-lg">
                {{ $hero?->description ?? 'Expert-led courses, hands-on projects and real certificates. Learn at your own pace and build a portfolio you can show off — starting today.' }}
            </p>

            <form action="{{ route('courses.index') }}" method="GET"
                class="mt-8 flex max-w-md rounded-2xl bg-white/10 p-1.5 ring-1 ring-white/20 backdrop-blur transition focus-within:ring-2 focus-within:ring-brand-400 items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor" class="pointer-events-none mx-3.5 size-5 shrink-0 text-slate-400">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input type="search" name="search" placeholder="What do you want to learn?"
                    class="w-full bg-transparent text-sm text-white placeholder:text-slate-400 focus:outline-none" />
                <button type="submit" class="btn btn-primary btn-md shrink-0">Search</button>
            </form>

            <div class="mt-6 flex flex-wrap items-center gap-x-8 gap-y-3 text-sm text-slate-300">
                <span class="flex items-center gap-2">
                    <span class="flex -space-x-2">
                        <x-ui.avatar name="Amina Yusuf" size="xs" class="ring-2 ring-slate-950" />
                        <x-ui.avatar name="David Park" size="xs" class="ring-2 ring-slate-950" />
                        <x-ui.avatar name="Lena Kovacs" size="xs" class="ring-2 ring-slate-950" />
                    </span>
                    <span><strong
                            class="font-bold text-white">{{ $hero?->formatted_learners_count ?? '42,000+' }}</strong>
                        {{ $hero?->learners_text ?? 'learners' }}</span>
                </span>
                <span>&starf; <strong
                        class="font-bold text-white">{{ number_format($hero?->rating_score ?? 4.8, 1) }}</strong>
                    {{ $hero?->rating_text ?? 'average course rating' }}</span>
            </div>
        </div>

        {{-- Illustrative course player mockup --}}
        <div class="relative mx-auto mt-10 block w-full max-w-md sm:mt-12 lg:mt-0" aria-hidden="true">
            <div
                class="absolute -inset-6 rounded-[2rem] bg-gradient-to-tr from-brand-500/30 via-transparent to-sky-400/20 blur-2xl">
            </div>

@php
    $featured = $hero?->featuredCourse;
    // যদি কোর্সের নিজস্ব image_path থাকে, অথবা যদি YouTube ভিডিওর URL থাকে
    $hasMedia = $featured && ($featured->image_path || $featured->intro_video_url);
@endphp

<div class="relative rounded-2xl bg-white/95 p-4 shadow-(--shadow-pop) ring-1 ring-white/20 backdrop-blur-xl">

    @if($hasMedia && $featured->image_path)
        {{-- ১. যদি কোর্সের নিজস্ব আপলোড করা ইমেজ থাকে --}}
        <img src="{{ asset('storage/' . $featured->image_path) }}" alt="{{ $featured->title }}"
            class="aspect-video w-full rounded-xl object-cover" />
    @elseif($hasMedia && $featured->intro_video_url)
        {{-- ২. যদি ইমেজ না থাকে কিন্তু ইউটিউব ভিডিও URL থাকে (যেমন আপনার ডেটায় আছে) --}}
        @php
            // YouTube URL থেকে Video ID বের করার ছোট্ট লজিক
            preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $featured->intro_video_url, $match);
            $youtubeId = $match[1] ?? null;
        @endphp

        @if($youtubeId)
            <div class="relative aspect-video w-full overflow-hidden rounded-xl bg-slate-900">
                <iframe src="https://www.youtube-nocookie.com/embed/{{ $youtubeId }}" class="size-full border-0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen></iframe>
            </div>
        @else
            {{-- ইউটিউব আইডি কাজ না করলে ডিফল্ট থাম্ব কম্পোনেন্ট --}}
            <x-ui.thumb :seed="$hero?->mockup_thumb_seed ?? 'figma-masterclass'" class="aspect-video w-full rounded-xl"
                icon="play" :label="$featured->title" />
        @endif
    @else
        {{-- ৩. কোনো মিডিয়া না থাকলে আপনার আগের ডিফল্ট Seed থাম্বনেইল কম্পোনেন্ট --}}
        <x-ui.thumb :seed="$hero?->mockup_thumb_seed ?? 'figma-masterclass'" class="aspect-video w-full rounded-xl"
            icon="play" :label="$featured?->title ?? 'Figma Masterclass'" />
    @endif

    <div class="px-2 pb-1 pt-4">
        <p class="flex items-center justify-between text-sm">
            <span class="font-bold text-slate-900">{{ $featured?->title ?? 'Design Systems Masterclass' }}</span>
            <span class="text-xs font-semibold text-amber-500">★
                {{ number_format($featured?->rating ?? 4.9, 1) }}</span>
        </p>
        <div class="mt-2 flex items-center gap-2">
            <x-ui.avatar :name="$featured?->instructor?->name ?? 'Mina Talha'" size="xs" />
            <span class="text-xs text-slate-500">{{ $featured?->instructor?->name ?? 'Mina Talha' }} ·
                {{ $featured?->instructor?->headline ?? 'Lead Product Designer' }}</span>
        </div>

        <div class="mt-4">
            <div class="mb-1.5 flex justify-between text-[11px] font-medium text-slate-500">
                <span>Course progress</span><span>64%</span>
            </div>
            <div class="progress-track h-2">
                <div class="progress-fill" style="width: 64%"></div>
            </div>
        </div>

        @if($hero?->mockup_next_lesson_title)
            <div class="mt-4 rounded-xl bg-slate-50 p-3">
                <p class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase">Up next</p>
                <p class="mt-1 text-sm font-semibold text-slate-800">{{ $hero->mockup_next_lesson_title }} →</p>
            </div>
        @endif
    </div>
</div>

            <div class="card absolute -bottom-8 -right-10 flex animate-fade-up items-center gap-2.5 px-3.5 py-2.5"
                style="animation-delay: 0.15s">
                <span class="grid size-8 place-items-center rounded-lg bg-amber-100 text-sm">🔥</span>
                <div>
                    <p class="text-xs font-bold text-slate-900">{{ $hero?->streak_title ?? '12-day streak' }}</p>
                    <p class="text-[11px] text-slate-500">{{ $hero?->streak_subtitle ?? 'Keep it going!' }}</p>
                </div>
            </div>

            <div class="card absolute right-0 bottom-14 flex animate-fade-up items-center gap-2.5 px-3.5 py-2.5"
                style="animation-delay: 0.3s">
                <span class="grid size-8 place-items-center rounded-lg bg-emerald-100 text-emerald-700">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </span>
                <div>
                    <p class="text-xs font-bold text-slate-900">{{ $hero?->badge_earned_title ?? 'Certificate earned' }}
                    </p>
                    <p class="text-[11px] text-slate-500">{{ $hero?->badge_earned_subtitle ?? 'UX Foundations' }}</p>
                </div>
            </div>
        </div>
    </div>
</section>

    <div class="mx-auto max-w-7xl sm:px-0">
        {{-- ============================ Categories ============================ --}}
        <section class="px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
            <div class="mb-8 text-center">
                <p class="eyebrow">Popular categories</p>
                <h2 class="section-title mt-2">Explore by what moves you</h2>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($categories as $category)
                      @if ($category->courses->isNotEmpty())
                        <a href="{{ route('courses.index', ['category' => $category->id]) }}" class="card card-hover group flex items-center gap-3.5 px-4 py-4 sm:px-5">
                                <span class="grid size-11 shrink-0 place-items-center rounded-xl ring-1 transition group-hover:scale-105 bg-brand-50 text-brand-600 ring-brand-100/70">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="m17.25 6.75 5.25 5.25-5.25 5.25m-10.5 0L1.75 12l5.25-5.25m7.5-3-4.5 14.25" /></svg>
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-bold text-slate-900">{{ $category->name }}</span>
                                    <span class="block text-xs text-slate-500">{{ $category->courses->count() }} courses</span>
                                </span>
                            </a>

                    @endif
                @endforeach
            </div>

            {{-- ============================ Featured courses ============================ --}}
            <section id="featured" class="pt-20 sm:pt-24">
                <div class="mb-8 flex items-end justify-between">
                    <div>
                        <p class="eyebrow">Featured</p>
                        <h2 class="section-title mt-2">Hand-picked by our editors</h2>
                        <p class="mt-2 max-w-xl text-sm text-slate-500">Deep dives into the skills employers are hiring for right now.</p>
                    </div>
                    <a href="{{ route('courses.index') }}" class="btn btn-ghost btn-sm hidden shrink-0 sm:inline-flex">
                        View all
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                    </a>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($this->courses as $course)
                                            @php
    $originalPrice = (float) $course->price;
    $discountValue = (float) ($course->discount_amount ?? 0);

    $hasDiscount = $discountValue > 0
        && in_array($course->discount_type, ['fixed', 'percentage', 'percent']);

    $finalPrice = match ($course->discount_type) {
        'fixed' => max(0, $originalPrice - $discountValue),

        'percentage', 'percent' => max(
            0,
            $originalPrice * (1 - min(100, max(0, $discountValue)) / 100)
        ),

        default => $originalPrice,
    };

    $badge = null;

    if ($hasDiscount && $course->discount_type === 'fixed') {
        $badge = number_format($discountValue, 2) . ' Discount';

    } elseif ($hasDiscount && in_array($course->discount_type, ['percentage', 'percent'])) {
        $badge = rtrim(rtrim(number_format($discountValue, 2), '0'), '.') . '% OFF';

    } elseif ($course->created_at?->greaterThan(now()->subDays(7))) {
        $badge = 'New';
    }
                                            @endphp

                        <x-ui.course-card
                            :title="$course->title"
                            :goal="$course->description"
                            :image="$course->image_path ?? null"
                            :instructor="'By ' . $course->instructor->name"
                            :category="$course->category->name"
                            :rating="$course[4]"
                            :rating-count="$course[5]"
                            :price="$finalPrice"
                            :original-price="$hasDiscount ? $originalPrice : null"
                            :badge="$badge"
                            :meta="'6 sections · 24 lessons'"
                            :href="route('courses.show', $course->slug)"
                        >
                            <x-slot name="footer">
                                <x-ui.cart-button :course-id="$course->id" :in-cart="! empty($cart[$course->id])" />
                            </x-slot>
                        </x-ui.course-card>
                    @endforeach
                </div>
            </section>

            {{-- ============================ Learning benefits ============================ --}}
            <section id="benefits" class="pt-20 sm:pt-24">
                <div class="grid items-center gap-12 px-0 lg:grid-cols-[1fr_1.1fr]">
                    <div>
                        <p class="eyebrow">Why {{ $siteSettings->displayName() }}</p>
                        <h2 class="section-title mt-2">Learning that respects your time</h2>
                        <p class="mt-3 text-sm leading-relaxed text-slate-500">
                            Every course is cut into focused lessons, so a busy week never sets you back. Learn on the train, on lunch breaks, whenever it fits.
                        </p>

                        <ul class="mt-8 space-y-5">
                            @foreach ([
    ['Self-paced lessons', 'Short, focused videos with progress tracking and gentle reminders to keep you moving.', '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />'],
    ['Learn by building', 'Real projects in every course — no passive watching, no detached theory.', '<path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />'],
    ['Certificates that count', 'Finish all lessons and pass the quizzes to earn a verifiable, shareable certificate.', '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />'],
] as $benefit)
                                <li class="flex gap-4">
                                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600 ring-1 ring-brand-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-5">{!! $benefit[2] !!}</svg>
                                    </span>
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-900">{{ $benefit[0] }}</h3>
                                        <p class="mt-1 text-sm leading-relaxed text-slate-500">{{ $benefit[1] }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Stats panel --}}
                    <div class="grid grid-cols-2 gap-4 bg-brand-950/0 sm:px-6 lg:px-0">
                        @foreach ([
    ['42,000+', 'Active learners', 'from-brand-500 to-brand-700'],
    ['350+', 'Expert-led courses', 'from-sky-500 to-blue-700'],
    ['96%', 'Completion satisfaction', 'from-emerald-500 to-teal-700'],
    ['4.8★', 'Average course rating', 'from-amber-500 to-orange-600'],
] as [$value, $label, $gradient])
                            <div class="card p-6">
                                <span class="grid size-10 place-items-center rounded-xl bg-gradient-to-br {{ $gradient }} text-white shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6 3 12l9 6 9-6-9-6Z" /></svg>
                                </span>
                                <p class="mt-4 text-2xl font-extrabold tracking-tight text-slate-900">{{ $value }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $label }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- ============================ Trending ============================ --}}
            <section id="trending" class="pt-20 sm:pt-24">
                <div class="mb-8">
                    <p class="eyebrow">Trending now</p>
                    <h2 class="section-title mt-2">What everyone is learning</h2>
                </div>

                <div class="no-scrollbar -mx-4 flex snap-x snap-mandatory gap-5 overflow-x-auto px-4 pb-2 sm:px-0">
                    @foreach ($this->courses as $course)
                    @php
    $originalPrice = (float) $course->price;
    $discountValue = (float) ($course->discount_amount ?? 0);

    $hasDiscount = $discountValue > 0
        && in_array($course->discount_type, ['fixed', 'percentage', 'percent']);

    $finalPrice = match ($course->discount_type) {
        'fixed' => max(0, $originalPrice - $discountValue),

        'percentage', 'percent' => max(
            0,
            $originalPrice * (1 - min(100, max(0, $discountValue)) / 100)
        ),

        default => $originalPrice,
    };

    $badge = null;

    if ($hasDiscount && $course->discount_type === 'fixed') {
        $badge = number_format($discountValue, 2) . ' Discount';

    } elseif ($hasDiscount && in_array($course->discount_type, ['percentage', 'percent'])) {
        $badge = rtrim(rtrim(number_format($discountValue, 2), '0'), '.') . '% OFF';

    } elseif ($course->created_at?->greaterThan(now()->subDays(7))) {
        $badge = 'New';
    }
                                            @endphp


                        <div class="w-72 shrink-0 snap-start sm:w-80">
                            <x-ui.course-card
                                :title="$course->title"
                                :goal="$course->description"
                                :image="$course->image_path ?? null"
                                :instructor="'By ' . $course->instructor->name"
                                :category="$course->category->name"
                                :rating="$course[4]"
                                :rating-count="$course[5]"
                                :price="$finalPrice"
                                :original-price="$hasDiscount ? $originalPrice : null"
                                :badge="$badge"
                                :meta="'6 sections · 24 lessons'"
                                :href="route('courses.show', $course->slug)"
                            >
                                <x-slot name="footer">
                                    <x-ui.cart-button :course-id="$course->id" :in-cart="! empty($cart[$course->id])" />
                                </x-slot>
                            </x-ui.course-card>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ============================ Instructor showcase ============================ --}}
            <section class="pt-20 sm:pt-24">
                <div class="mb-8 text-center">
                    <p class="eyebrow">Meet the instructors</p>
                    <h2 class="section-title mt-2">Learn from people who ship</h2>
                    <p class="mx-auto mt-2 max-w-xl text-sm text-slate-500">Every instructor is vetted for real-world experience — no slide-readers allowed.</p>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    {{-- ['Mina Talha', 'Lead Product Designer', 'Design Systems with Figma', '4.9', '18,400'], --}}
                    @foreach ($this->instructors as $instructor)
                                                <a href="#" class="card card-hover group p-5 text-center">
                                                    @if($instructor->instructor->profile_image)

                                                        <img src="{{ asset('storage/' . $instructor->instructor->profile_image) }}" alt="{{ $instructor->name }}" class="w-24 h-24 rounded-full mx-auto ring-4 ring-slate-100 transition group-hover:ring-brand-100 object-cover">
                                                    @else
                                                        <x-ui.avatar :name="$instructor->name" size="xl" class="mx-auto ring-4 ring-slate-100 transition group-hover:ring-brand-100" />
                                                    @endif
                                                    <h3 class="mt-4 text-sm font-bold text-slate-900">{{ $instructor->name }}</h3>
                                                    <p class="mt-0.5 text-xs text-slate-500">{{ $instructor->instructor->degignation}}</p>
                                                    <p class="mt-3 inline-flex items-center gap-1 rounded-full bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">
                                                        {{ \Str::words($instructor->instructor->bio, 10, '..') }}
                                                    </p>
                                                    <p class="mt-3 text-xs text-slate-500">
                                                        <span class="font-bold text-amber-500">★ 4.9</span>
                                                        rating · 18,400 students
                                                    </p>
                                                </a>
                    @endforeach
                </div>

                <div class="card mt-10 flex flex-col items-center justify-between gap-6 px-8 py-8 text-center sm:flex-row sm:text-left">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Got something to teach?</h3>
                        <p class="mt-1 max-w-xl text-sm text-slate-500">Share what you know with thousands of motivated students and build your audience on {{ $siteSettings->displayName() }}.</p>
                    </div>
                    <a href="#" class="btn btn-dark btn-md shrink-0">Become an instructor</a>
                </div>
            </section>

            {{-- ============================ Testimonials ============================ --}}
            <section class="pt-20 sm:pt-24">
                <div class="mb-8 text-center">
                    <p class="eyebrow">Student stories</p>
                    <h2 class="section-title mt-2">Real progress, in their words</h2>
                </div>

                <div class="grid gap-5 md:grid-cols-3">
                    @foreach ([
    ['Sarah Chen', 'Marketing → UX Designer', 'The project-based structure nudged me to actually build instead of just watch. I had three portfolio pieces before finishing my second course.'],
    ['David Park', 'Backend Engineer @ Nova', 'The materials are precise — no fluff, no filler. I doubled my speed in reviewing PHP codebases within a month.'],
    ['Lena Kovacs', 'Bootcamp grad → Junior Dev', 'Short lessons meant I finally stopped skimming. The certificate even helped me get past the CV filter for my first interview.'],
] as $testimonial)
                        <figure class="card relative p-6">
                            <svg class="absolute top-6 right-6 size-8 text-slate-100" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8.6 5.7 6.7 12h3.3v6.3H4V9.8l2.3-5.8 2.3 1.7Zm9.3 0-1.8 6.3h3.2v6.3h-6V9.8l2.3-5.8 2.3 1.7Z"/></svg>
                            <blockquote class="text-sm leading-relaxed text-slate-600">
                                “{{ $testimonial[2] }}”
                            </blockquote>
                            <figcaption class="mt-5 flex items-center gap-3 border-t border-slate-100 pt-4">
                                <x-ui.avatar :name="$testimonial[0]" size="sm" />
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-900">{{ $testimonial[0] }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $testimonial[1] }}</p>
                                </div>
                                <span class="ml-auto shrink-0 text-xs font-semibold text-amber-500">★★★★★</span>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </section>

            {{-- ============================ Newsletter ============================ --}}
            <section class="px-4 pb-20 pt-20 sm:px-0 sm:pb-24 sm:pt-24">
                <div class="card relative isolate overflow-hidden px-6 py-10 text-center sm:px-12 sm:py-14" style="background: linear-gradient(135deg, #312e81, #1e1b4b);">
                    <div class="pointer-events-none absolute inset-0 opacity-40" aria-hidden="true">
                        <div class="absolute top-0 left-1/4 h-64 w-64 rounded-full bg-brand-500/30 blur-[90px]"></div>
                        <div class="absolute right-0 bottom-0 h-40 w-72 rounded-full bg-sky-400/20 blur-[80px]"></div>
                    </div>
                    <div class="relative mx-auto max-w-xl">
                        <h2 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">Get the best new courses — in your inbox</h2>
                        <p class="mt-3 text-sm text-brand-200/90">One email a week. The top picks, free lessons and deals. No spam, unsubscribe anytime.</p>

                        <form x-data="{ sent: false }" @submit.prevent="sent = true" class="mx-auto mt-6 flex max-w-md flex-col gap-2 sm:flex-row">
                            <input
                                type="email"
                                required
                                placeholder="you@example.com"
                                class="w-full rounded-xl border-0 bg-white/10 px-4 py-3 text-sm text-white placeholder:text-slate-400 ring-1 ring-white/20 transition focus:bg-white/15 focus:ring-2 focus:ring-brand-400 focus:outline-none"
                            />
                            <button type="submit" x-show="!sent" class="shrink-0 cursor-pointer rounded-xl bg-white px-6 py-3 text-sm font-bold text-brand-800 shadow-sm transition hover:bg-slate-100 disabled:opacity-60">
                                Subscribe
                            </button>
                            <button type="button" x-show="sent" x-cloak disabled class="shrink-0 cursor-not-allowed rounded-xl bg-emerald-500/90 px-6 py-3 text-sm font-bold text-white sm:w-40">
                                Subscribed
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

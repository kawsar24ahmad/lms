<?php

use App\Models\Course;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public Course $course;

    public function mount(?Course $course = null): void
    {
        if ($course !== null) {
            $this->course = $course;
        }

        $this->course->loadMissing('category', 'instructor', 'sections.lessons.assignments', 'reviews.student', 'quizzes');
    }

    /**
     * The current user's enrollment in the course, if any.
     */
    #[Computed]
    public function enrollment()
    {
        return auth()->check()
            ? $this->course->enrollments()->where('student_id', auth()->id())->first()
            : null;
    }

    /**
     * Whether the current user can watch a given lesson.
     */
    #[Computed]
    public function canWatchLesson(App\Models\Lesson $lesson): bool
    {
        return $lesson->is_preview || $this->enrollment !== null;
    }

    /**
     * How many lessons the current user completed, and the total count.
     */
    #[Computed]
    public function progress(): array
    {
        if ($this->enrollment === null) {
            return ['completed' => 0, 'total' => 0];
        }

        $lessons = $this->course->sections->flatMap->lessons;

        return [
            'completed' => $lessons->filter(fn(App\Models\Lesson $lesson) => $lesson->isCompletedFor(auth()->user()))->count(),
            'total' => $lessons->count(),
        ];
    }

    /**
     * The average course rating, or null when nobody has reviewed it.
     */
    #[Computed]
    public function averageRating(): ?string
    {
        $average = $this->course->reviews->avg('rating');

        return $average ? number_format($average, 1) : null;
    }

    /**
     * Enroll the current user in a free course, or start the checkout for a
     * paid course, then send them to the first lesson.
     */
    public function enroll(): void
    {
        if (!auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        if ($this->course->effectivePrice() > 0) {
            $this->redirectRoute('checkout.create', ['courses' => [$this->course->slug]]);

            return;
        }

        $this->course->enroll(auth()->user());

        $lesson = $this->course->resumeLessonFor(auth()->user());

        if ($lesson !== null) {
            $this->redirectRoute('lessons.show', ['course' => $this->course, 'lesson' => $lesson]);
        }
    }
};
?>

<div class="animate-fade-up">
    {{-- ============================ Hero banner ============================ --}}
    <section class="relative overflow-hidden bg-slate-950 -mx-4 px-4 py-10 text-white sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="absolute -top-24 left-1/3 h-72 w-[42rem] rounded-full bg-brand-600/25 blur-[100px]"></div>
        </div>

        <div class="relative mx-auto max-w-7xl px-0">
            <nav class="mb-4 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="transition hover:text-white">Home</a>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-3"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                <a href="{{ route('courses.index') }}" class="transition hover:text-white">Courses</a>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-3"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                <span class="text-slate-300">{{ $course->category?->name ?? 'General' }}</span>
            </nav>

            <div class="grid gap-10 lg:grid-cols-[1fr_340px] lg:items-start">
                <div class="max-w-2xl">
                    <span class="badge bg-white/10 text-brand-200 ring-1 ring-white/15">{{ $course->category?->name ?? 'General' }}</span>

                    <h1 class="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl">
                        {{ $course->title }}
                    </h1>

                    @if ($course->description)
                        <p class="mt-4 line-clamp-3 text-sm leading-relaxed text-slate-300 sm:text-base" title="{{ $course->description }}">
                            {{ str($course->description)->limit(220) }}
                        </p>
                    @endif

                    <div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-slate-300">
                        <span class="flex items-center gap-2">
                            <x-ui.avatar :name="$course->instructor?->name ?? 'EduLearn'" size="xs" />
                            <span>
                                by <a href="{{ route('courses.index') }}" class="font-semibold text-sky-300 underline-offset-2 hover:underline">{{ $course->instructor?->name ?? 'EduLearn team' }}</a>
                            </span>
                        </span>
                        @if ($this->averageRating !== null)
                            <span class="flex items-center gap-1.5">
                                <span class="font-semibold text-amber-300">★ {{ $this->averageRating }}</span>
                                <span class="text-slate-500">({{ $course->reviews->count() }} review{{ $course->reviews->count() === 1 ? '' : 's' }})</span>
                            </span>
                        @endif
                        <span class="hidden items-center gap-1.5 sm:flex">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4 text-slate-400"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9 21.75 12.75 13.5H3.75Z" /></svg>
                            {{ $course->sections->count() }} sections · {{ $course->sections->flatMap->lessons->count() }} lessons
                        </span>
                        <span class="flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4 text-slate-400"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.004 7.004 0 0 1-.878-3.425V12a6.375 6.375 0 0 0-12.75 0v.75c0 1.6.213 2.908.625 3.915m0 0A6.003 6.003 0 0 0 12 21a6.003 6.003 0 0 0 5.13-3.335" /></svg>
                            Certificate of completion
                        </span>
                    </div>
                </div>

                {{-- Enroll card — preview, price, CTA --}}
                <aside class="card relative overflow-hidden p-0 text-slate-900 md:mx-auto md:w-88 lg:w-full">
                    @if ($course->introVideoEmbedUrl())
                        <div class="aspect-video w-full overflow-hidden bg-black rounded-lg">
                            @if ($course->hasDirectIntroVideo())
                                <video src="{{ $course->introVideoEmbedUrl() }}" class="size-full object-cover" controls preload="metadata"></video>
                            @else
                                <iframe src="{{ $course->introVideoEmbedUrl() }}" class="size-full border-0" title="{{ $course->title }}"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen"
                                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen>
                                </iframe>
                            @endif
                        </div>


                    @elseif ($course->image_path)
                        <div class="aspect-video w-full overflow-hidden bg-slate-100">
                            <img src="{{ $course->image_path }}" alt="Course preview" class="size-full object-cover" />
                        </div>
                    @else
                        <x-ui.thumb :seed="$course->title" class="aspect-video w-full" :label="$course->title" icon='<svg xmlns="http://www.w3.org/2000/svg" fill="#fff" viewBox="0 0 24 24" class="size-6"><path d="M8 5.14v14l11-7-11-7Z"/></svg>' />
                    @endif

                    <div class="p-5">
                        <div class="flex items-baseline gap-2.5">
                            <span class="text-3xl font-extrabold tracking-tight {{ ($course->isDiscounted() ? $course->effectivePrice() : $course->price) == 0 ? 'text-emerald-600' : 'text-slate-900' }}">
                                @if ($course->isDiscounted() && $course->price > 0)
                                    <span class="mr-1.5 align-middle text-base font-medium text-slate-400 line-through">${{ number_format($course->price, 2) }}</span>
                                @endif
                                {{ ($course->isDiscounted() ? $course->effectivePrice() : $course->price) == 0 ? 'Free' : '$' . number_format($course->effectivePrice(), 2) }}
                            </span>
                            @if ($course->isDiscounted() && $course->price > 0 && $course->effectivePrice() > 0)
                                @php $off = (int) round((1 - $course->effectivePrice() / $course->price) * 100); @endphp
                                @if ($off > 0)
                                    <span class="badge bg-rose-50 text-rose-600 ring-1 ring-rose-200">{{ $off }}% off</span>
                                @endif
                            @endif
                        </div>

                        @if ($course->isDiscounted() && $course->price > 0)
                            <p class="mt-1 text-xs text-slate-500">🎉 Sale ends soon — get it before the price goes back up.</p>
                        @endif

                        @if ($this->enrollment === null)
                            <button
                                type="button"
                                wire:click="enroll"
                                wire:loading.attr="disabled"
                                class="btn btn-primary btn-lg mt-4 w-full"
                            >
                                <span wire:loading.remove wire:target="enroll">{{ ($course->isDiscounted() ? $course->effectivePrice() : $course->price) == 0 ? 'Enroll for free' : 'Enroll now' }}</span>
                                <span wire:loading wire:target="enroll" class="flex items-center gap-2">
                                    <svg class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path d="M4 12a8 8 0 0 1 8-8" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                                    Enrolling…
                                </span>
                            </button>
                        @elseif ($this->enrollment->status === 'completed')
                            <span class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-200">
                                Certificate earned 🎉
                            </span>
                        @else
                            <span class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-brand-50 px-4 py-3 text-sm font-semibold text-brand-700 ring-1 ring-brand-100">
                                You're enrolled — keep going!
                            </span>
                        @endif

                        <p class="mt-3 text-center text-xs text-slate-500">30-day satisfaction guarantee · Full lifetime access</p>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    {{-- ============================ Body ============================ --}}
    <div class="grid gap-10 py-10 lg:grid-cols-[1fr_340px] lg:items-start">
        <div class="min-w-0 space-y-10">
            {{-- What you will learn --}}
            <section class="card p-6">
                <h2 class="section-title">What you'll learn</h2>

                <div class="mt-5 grid gap-x-8 gap-y-4 sm:grid-cols-2">
                    @foreach ([
    ['Apply the core concepts confidently', 'Move past theory — every section ends with practical takeaways you can use immediately.'],
    ['Build real, portfolio-worthy projects', 'Work on guided projects that mirror what professionals do day to day.'],
    ['Industry best practices', 'Write, think and plan with the standards used on professional teams.'],
    ['Prepare for interviews and assessments', 'Practice exactly the material teams test on in hiring rounds.'],
] as $objective)
                        <div class="flex gap-3">
                            <span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-600">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-3"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ $objective[0] }}</p>
                                <p class="mt-0.5 text-[13px] leading-relaxed text-slate-500">{{ $objective[1] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Description --}}
            @if ($course->description)
                <section class="card p-6">
                    <h2 class="section-title">About this course</h2>
                    <p class="prose-lesson mt-4">{{ $course->description }}</p>
                </section>
            @endif

            {{-- Curriculum --}}
            <section id="curriculum" class="card p-6">
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <h2 class="section-title">Curriculum</h2>
                    <p class="text-sm text-slate-500">
                        {{ $course->sections->count() }} sections · {{ $course->sections->flatMap->lessons->count() }} lessons

                        @if ($this->progress['total'] > 0)
                            @php $progressPercent = (int) round($this->progress['completed'] / $this->progress['total'] * 100); @endphp
                            · <span class="font-semibold text-brand-600">{{ $this->progress['completed'] }} of {{ $this->progress['total'] }} done</span>
                        @endif
                    </p>
                </div>

                @if ($this->progress['total'] > 0)
                    <div class="mt-4 progress-track"><div class="progress-fill" style="width: {{ $progressPercent }}%"></div></div>
                @endif

                <div class="mt-6 space-y-3">
                    @foreach ($course->sections as $section)
                        <div class="overflow-hidden rounded-xl ring-1 ring-slate-200 transition hover:ring-slate-300" wire:key="section-{{ $section->id }}">
                            <details class="group" open>
                                <summary class="flex cursor-pointer items-center justify-between gap-4 bg-slate-50/70 px-5 py-4 transition marker:content-none hover:bg-slate-100/70">
                                    <span class="flex min-w-0 items-center gap-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4 shrink-0 text-slate-400 transition-transform group-open:rotate-90"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                                        <span class="truncate text-sm font-bold text-slate-900">{{ $section->title }}</span>
                                    </span>
                                    <span class="shrink-0 text-xs text-slate-500">
                                        {{ $section->lessons->count() }} lesson{{ $section->lessons->count() === 1 ? '' : 's' }}
                                        @if ($section->lessons->sum('duration_minutes'))
                                            · {{ $section->lessons->sum('duration_minutes') }} min
                                        @endif
                                    </span>
                                </summary>

                                <ul class="divide-y divide-slate-100 border-t border-slate-100">
                                    @foreach ($section->lessons as $lesson)
                                        <li class="flex items-center justify-between gap-4 px-5 py-3" wire:key="lesson-{{ $lesson->id }}">
                                            <div class="flex min-w-0 items-center gap-3">
                                                @if ($this->enrollment !== null && $lesson->isCompletedFor(auth()->user()))
                                                    <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-3"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                                    </span>
                                                @else
                                                    <span class="grid size-5 shrink-0 place-items-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-500">{{ $lesson->position + 1 }}</span>
                                                @endif

                                                <span class="truncate text-sm {{ $this->canWatchLesson($lesson) ? 'text-slate-800' : 'text-slate-400' }}">{{ $lesson->title }}</span>
                                            </div>

                                            <div class="flex shrink-0 items-center gap-3">
                                                @if ($lesson->is_preview && $this->enrollment === null)
                                                    <span class="badge bg-brand-50 text-brand-600 ring-1 ring-brand-100">Preview</span>
                                                @endif

                                                @if ($lesson->assignments->isNotEmpty())
                                                    <span class="badge bg-amber-50 text-amber-700 ring-1 ring-amber-200">
                                                        {{ $lesson->assignments->count() }}
                                                        {{ $lesson->assignments->count() === 1 ? 'assignment' : 'assignments' }}
                                                    </span>
                                                @endif

                                                @if ($lesson->duration_minutes)
                                                    <span class="text-xs text-slate-400">{{ $lesson->duration_minutes }} min</span>
                                                @endif

                                                @if ($this->canWatchLesson($lesson))
                                                    <a href="{{ route('lessons.show', ['course' => $course, 'lesson' => $lesson]) }}" class="text-[13px] font-semibold text-brand-600 transition hover:text-brand-700">
                                                        {{ $this->enrollment !== null && $lesson->isCompletedFor(auth()->user()) ? 'Review' : 'Watch' }}
                                                    </a>
                                                @else
                                                    <span class="text-sm text-slate-300" title="Enroll to unlock">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                                    </span>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Quizzes --}}
            @if ($course->quizzes->isNotEmpty() && $this->enrollment !== null)
                <section class="card p-6">
                    <h2 class="section-title">Test your knowledge</h2>

                    <div class="mt-4 space-y-3">
                        @foreach ($course->quizzes as $quiz)
                            <div class="flex items-center justify-between rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200/70" wire:key="quiz-{{ $quiz->id }}">
                                <div class="flex items-center gap-3.5">
                                    <span class="grid size-10 place-items-center rounded-xl bg-white text-brand-600 ring-1 ring-slate-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3-.284m9-11.141a4 4 0 0 1-3.2 3.922M6.75 15a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5Zm8.25 0a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5Zm-8.25-2.25V9.75m-1.5-.002V11A2.25 2.25 0 0 0 6.75 13.25h1.5M6.75 8.25h7.5" /></svg>
                                    </span>
                                    <div>
                                        <h3 class="font-semibold text-slate-900">{{ $quiz->title }}</h3>
                                        <span class="text-xs text-slate-500">Pass score: {{ $quiz->pass_score }}%</span>
                                    </div>
                                </div>

                                <a href="{{ route('quizzes.show', ['course' => $course, 'quiz' => $quiz]) }}" class="btn btn-secondary btn-sm shrink-0">
                                    Take quiz
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Reviews --}}
            <section class="card p-6">
                <h2 class="section-title">Student feedback</h2>

                @if ($course->reviews->isEmpty())
                    <p class="mt-4 rounded-xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                        No reviews yet — this course is brand new. ;)
                    </p>
                @else
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        @foreach ($course->reviews as $review)
                            <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200/70" wire:key="review-{{ $review->id }}">
                                <div class="mb-2 flex items-center gap-3">
                                    <x-ui.avatar :name="$review->student?->name ?? 'Student'" size="sm" />
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-900">{{ $review->student?->name ?? 'Student' }}</p>
                                        <div class="flex items-center gap-0.5 pt-0.5">
                                            @for ($i = 0; $i < 5; $i++)
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="{{ $i < $review->rating ? 'text-amber-400' : 'text-slate-200' }} size-3.5"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006Z" /></svg>
                                            @endfor
                                        </div>
                                    </div>
                                </div>
                                @if ($review->comment)
                                    <p class="text-sm leading-relaxed text-slate-600">{{ $review->comment }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Related courses (mock design content) --}}
            <section>
                <div class="mb-5 flex items-end justify-between">
                    <h2 class="section-title">Students also viewed</h2>
                    <a href="{{ route('courses.index') }}" class="btn btn-ghost btn-sm shrink-0">Browse all</a>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach ([
    ['Advanced Clean Code Patterns', 'Tomas Ruiz', '4.8', '128', '39.00', '59.00', true],
    ['Responsive Design from scratch', 'Amira Bello', '4.9', '211', '49.00', null, false],
    ['APIs & Integration Essentials', 'Grace Wong', '4.7', '96', '29.00', '44.00', true],
] as $related)
                        <x-ui.course-card
                            :title="$related[0]"
                            :instructor="'By ' . $related[1]"
                            :category="'Web Development'"
                            :rating="$related[2]"
                            :rating-count="$related[3]"
                            :price="$related[6] ? $related[4] : $related[5]"
                            :original-price="$related[6] ? $related[5] : null"
                            :meta="'4 sections · 20 lessons'"
                            :href="route('courses.index')"
                        />
                    @endforeach
                </div>
            </section>
        </div>

        {{-- Sticky secondary sidebar (desktop) --}}
        <aside class="hidden lg:block lg:sticky lg:top-24">
            <div class="card p-5">
                <h3 class="text-sm font-bold text-slate-900">This course includes</h3>
                <ul class="mt-4 space-y-3 text-sm text-slate-600">
                    @foreach ([
    [$course->sections->flatMap->lessons->count() . ' on-demand lessons', '<path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />'],
    [$course->sections->count() . ' structured sections', '<path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 7.5 3m0 0L12 7.5M7.5 3v13.5m13.5-3.75L16.5 21m0 0L12 16.5m4.5 4.5V7.5" />'],
    ['Quizzes for knowledge checks', '<path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3-.284" />'],
    ['Certificate of completion', '<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497" />'],
    ['Access on mobile and desktop', '<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />'],
] as $include)
                        <li class="flex items-start gap-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="mt-0.5 size-4.5 shrink-0 text-brand-500">{!! $include[1] !!}</svg>
                            {{ $include[0] }}
                        </li>
                    @endforeach
                </ul>

                @if ($course->introVideoEmbedUrl())
                    <a href="#curriculum" class="btn btn-secondary btn-md mt-5 w-full">
                        Preview curriculum
                    </a>
                @endif
            </div>
        </aside>
    </div>
</div>

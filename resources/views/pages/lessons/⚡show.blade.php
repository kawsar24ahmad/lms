<?php

use App\Models\Course;
use App\Models\Lesson;
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Models\LessonProgress;

new class extends Component {
    public Course $course;

    public Lesson $lesson;

    public function mount(?Course $course = null, ?Lesson $lesson = null): void
    {
        if ($course !== null) {
            $this->course = $course;
        }

        if ($lesson !== null) {
            $this->lesson = $lesson;
        }

        $this->course->loadMissing('sections.lessons');
    }

    /**
     * Guard every render: only enrolled users or free previews may load
     * lesson content.
     */
    public function boot(): void
    {
        $canWatch = $this->lesson->is_preview
            || (auth()->check() && $this->course->hasEnrolled(auth()->user()));

        if (!$canWatch) {
            abort(403);
        }
    }

    /**
     * Whether the user has completed this lesson.
     */
    #[Computed]
    public function isCompleted(): bool
    {
        return auth()->check() && $this->lesson->isCompletedFor(auth()->user());
    }

    /**
     * Whether completing the whole course earned a certificate.
     */
    #[Computed]
    public function courseCompleted(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $enrollment = $this->course->enrollments()
            ->where('student_id', auth()->id())
            ->first();

        return $enrollment !== null && $enrollment->status === 'completed';
    }

    /**
     * The assignments attached to this lesson, with the current student's
     * submission loaded.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Assignment>
     */
    #[Computed]
    public function lessonAssignments(): \Illuminate\Database\Eloquent\Collection
    {
        if (!auth()->check() || !$this->course->hasEnrolled(auth()->user())) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        return $this->lesson->assignments()
            ->with(['submissions' => fn($query) => $query->where('user_id', auth()->id())])
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->get();
    }

    /**
     * The lesson before this one in the course order, or null.
     */
    #[Computed]
    public function previousLesson(): ?Lesson
    {
        return $this->orderedLessons()->get($this->currentIndex() - 1);
    }

    /**
     * The lesson after this one in the course order, or null.
     */
    #[Computed]
    public function nextLesson(): ?Lesson
    {
        return $this->orderedLessons()->get($this->currentIndex() + 1);
    }

    /**
     * Mark the lesson complete for the current user, which may complete
     * the course and issue a certificate.
     */
    public function markComplete(): void
    {
        if (!auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        if (!$this->course->hasEnrolled(auth()->user()) && !$this->lesson->is_preview) {
            abort(403);
        }

        $this->lesson->completeFor(auth()->user());

        unset($this->isCompleted, $this->courseCompleted, $this->progressRecord, $this->lessonProgressByLesson);
    }

    private function orderedLessons()
    {
        return $this->course->loadMissing('sections.lessons')->sections
            ->flatMap->lessons
            ->values()
            ->keyBy(fn(Lesson $l, int $i) => $i);
    }

    private function currentIndex(): int
    {
        return $this->orderedLessons()->search(fn(Lesson $l) => $l->is($this->lesson));
    }


    #[Computed]
    public function progressRecord(): ?LessonProgress
    {
        if (!auth()->check()) {
            return null;
        }

        $progress = LessonProgress::query()
            ->where('user_id', auth()->id())
            ->where('lesson_id', $this->lesson->id)
            ->first();

        $progress?->setRelation('lesson', $this->lesson);

        return $progress;
    }

    /**
     * Watch percentages for every lesson in the course, keyed by lesson id.
     *
     * @return array<int, int>
     */
    #[Computed]
    public function lessonProgressByLesson(): array
    {
        if (!auth()->check()) {
            return [];
        }

        return LessonProgress::query()
            ->with('lesson')
            ->where('user_id', auth()->id())
            ->whereIn('lesson_id', $this->orderedLessons()->pluck('id')->all())
            ->get()
            ->mapWithKeys(fn (LessonProgress $progress): array => [
                $progress->lesson_id => $progress->percentage(),
            ])
            ->all();
    }

};
?>

<div class="animate-fade-up" x-data="{ open: true }">
    <a href="{{ route('courses.show', $course) }}"
        class="mt-2 mb-5 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-brand-600">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
        Back to course
    </a>

    @if ($this->isCompleted)
        <div class="mb-5 flex items-center gap-3 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
            <span class="grid size-6 shrink-0 place-items-center rounded-full bg-emerald-100">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
            </span>
            Lesson completed
        </div>
    @endif

    @if ($this->courseCompleted && $this->isCompleted)
        <div class="mb-5 flex items-center gap-3 rounded-xl bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 ring-1 ring-amber-200">
            <span class="grid size-6 shrink-0 place-items-center rounded-full bg-amber-100 text-amber-700">🎉</span>
            <span><strong>Well done!</strong> You finished every lesson of this course — your certificate has been issued.</span>
        </div>
    @endif

    {{-- Revealed by the player when a video crosses the completion threshold. --}}
    <div data-client-completed-banner
        class="mb-5 hidden items-center gap-3 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
        <span class="grid size-6 shrink-0 place-items-center rounded-full bg-emerald-100">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
        </span>
        Lesson completed — nice work!
    </div>

    <div class="grid gap-6 pb-4 lg:grid-cols-[1fr_320px] lg:items-start">
        {{-- ============================ Player column ============================ --}}
        <div class="min-w-0">
            {{-- Video frame --}}
    @if ($lesson->video_url)
        @php
            $videoProgress = $this->progressRecord;
            $watchPercentage = $videoProgress?->percentage() ?? 0;
            $resumePosition = (int) ($videoProgress?->last_position ?? 0);
            $furthestPosition = (int) ($videoProgress?->furthest_position ?? 0);
            $secondsWatched = (int) ($videoProgress?->seconds_watched ?? 0);
            $durationSeconds = (int) ($videoProgress?->duration_seconds ?? 0);
            $videoCompleted = $this->isCompleted || (bool) ($videoProgress?->is_completed ?? false);
            $resumeLabel = $resumePosition > 0
                ? sprintf('%d:%02d', intdiv($resumePosition, 60), $resumePosition % 60)
                : null;
            $watchedLabel = $secondsWatched >= 3600
                ? sprintf('%d:%02d:%02d', intdiv($secondsWatched, 3600), intdiv($secondsWatched % 3600, 60), $secondsWatched % 60)
                : sprintf('%d:%02d', intdiv($secondsWatched, 60), $secondsWatched % 60);
        @endphp

        <div wire:ignore class="overflow-hidden rounded-2xl bg-slate-950 ring-1 ring-slate-900/20 shadow-(--shadow-card-hover)"
            data-video-tracker
            data-progress-url="{{ route('lessons.progress.update', $lesson) }}"
            data-csrf="{{ csrf_token() }}"
            data-can-track="{{ auth()->check() ? '1' : '0' }}"
            data-resume-position="{{ $resumePosition }}"
            data-furthest-position="{{ $furthestPosition }}"
            data-seconds-watched="{{ $secondsWatched }}"
            data-duration-seconds="{{ $durationSeconds }}"
            data-completed="{{ $videoCompleted ? '1' : '0' }}">
            <div class="relative aspect-video w-full">
                @if ($lesson->isDirectVideo())
                    <video id="lesson-video" src="{{ $lesson->embedUrl() }}" class="size-full" controls preload="metadata" playsinline></video>
                @elseif ($lesson->embedUrl() !== '')
                    <iframe id="lesson-video" src="{{ $lesson->embedUrl(enablePlayerApi: true) }}" class="absolute inset-0 size-full"
                        title="{{ $lesson->title }}" allow="autoplay; encrypted-media; fullscreen; picture-in-picture"
                        referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                @endif
            </div>
        </div>

        <div data-video-progress-panel data-percentage="{{ $watchPercentage }}" class="card mt-3 px-5 py-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="inline-flex items-center gap-2 text-[11px] font-bold tracking-widest text-slate-400 uppercase">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                    Video progress
                </span>

                <span class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500">
                    <span data-progress-time>{{ $watchedLabel }} watched</span>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-700 tabular-nums">
                        <span data-progress-percent>{{ $watchPercentage }}</span>%
                    </span>
                </span>
            </div>

            <div class="progress-track mt-2.5">
                <div data-progress-bar class="progress-fill" style="width: {{ $watchPercentage }}%"></div>
            </div>

            <p class="mt-2 text-xs text-slate-500" data-progress-hint>
                @if ($videoCompleted)
                    You have completed this video.
                @elseif (! auth()->check())
                    <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:underline">Sign in</a>
                    to save your progress and resume later.
                @elseif ($resumeLabel)
                    Resumes at {{ $resumeLabel }}. Progress saves automatically while you watch.
                @else
                    Progress saves automatically while you watch.
                @endif
            </p>
        </div>
    @endif



            {{-- Lesson meta --}}
            <article class="card mt-5 p-6">
                <h1 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">
                    {{ $lesson->title }}
                </h1>

                @if ($lesson->content)
                    <div class="prose-lesson mt-4">{{ $lesson->content }}</div>
                @endif

                {{-- Actions --}}
                <div class="mt-7 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5">
                    <div class="flex gap-2">
                        @if ($this->previousLesson)
                            <a href="{{ route('lessons.show', ['course' => $course, 'lesson' => $this->previousLesson]) }}"
                                class="btn btn-secondary btn-md">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                                <span class="max-w-36 truncate">{{ $this->previousLesson->title }}</span>
                            </a>
                        @else
                            <span class="btn btn-md cursor-not-allowed opacity-50 ring-1 ring-slate-200">Start of course</span>
                        @endif

                        @if ($this->nextLesson)
                            <a href="{{ route('lessons.show', ['course' => $course, 'lesson' => $this->nextLesson]) }}"
                                class="btn btn-secondary btn-md">
                                <span class="max-w-36 truncate">{{ $this->nextLesson->title }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                            </a>
                        @else
                            <span class="btn btn-md cursor-not-allowed opacity-50 ring-1 ring-slate-200">End of course</span>
                        @endif
                    </div>

                    <button type="button" wire:click="markComplete" wire:loading.attr="disabled"
                        class="btn btn-md {{ $this->isCompleted ? 'bg-emerald-600 text-white hover:bg-emerald-500' : 'btn-primary' }}">
                        <span wire:loading.remove wire:target="markComplete">
                            {{ $this->isCompleted ? 'Completed ✓' : 'Mark as complete' }}
                        </span>
                        <span wire:loading wire:target="markComplete" class="flex items-center gap-2">
                            <svg class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path d="M4 12a8 8 0 0 1 8-8" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                            Saving…
                        </span>
                    </button>
                </div>
            </article>

            {{-- Assignments attached to this lesson --}}
            @if ($this->lessonAssignments->isNotEmpty())
                <section class="card mt-5 p-6">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-base font-bold text-slate-900">
                            Assignments
                            <span class="badge ml-1 bg-brand-50 text-brand-700 ring-1 ring-brand-200">
                                {{ $this->lessonAssignments->count() }}
                            </span>
                        </h2>

                        <a href="{{ route('assignments.index') }}"
                            class="text-[13px] font-semibold text-brand-600 transition hover:text-brand-700">
                            View all assignments
                        </a>
                    </div>

                    <ul class="mt-4 space-y-3">
                        @foreach ($this->lessonAssignments as $assignment)
                            @php
        $submission = $assignment->submissions->first();
        $status = $submission === null
            ? ($assignment->isPastDue() ? 'overdue' : 'pending')
            : ($submission->isGraded() ? 'graded' : 'submitted');
                            @endphp

                            <li class="flex flex-col gap-3 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200/70 sm:flex-row sm:items-center"
                                wire:key="lesson-assignment-{{ $assignment->id }}">
                                <span
                                    class="grid size-10 shrink-0 place-items-center rounded-xl bg-white text-brand-600 ring-1 ring-slate-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.7" stroke="currentColor" class="size-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                                    </svg>
                                </span>

                                <div class="min-w-0 grow">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $assignment->title }}
                                        </p>

                                        @if ($status === 'graded')
                                            <span
                                                class="badge bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">Graded</span>
                                        @elseif ($status === 'submitted')
                                            <span
                                                class="badge bg-brand-50 text-brand-700 ring-1 ring-brand-200">Submitted</span>
                                        @elseif ($status === 'overdue')
                                            <span class="badge bg-rose-50 text-rose-700 ring-1 ring-rose-200">Overdue</span>
                                        @else
                                            <span class="badge bg-amber-50 text-amber-700 ring-1 ring-amber-200">Pending</span>
                                        @endif
                                    </div>

                                    <p class="mt-0.5 text-xs text-slate-500">
                                        Due {{ $assignment->due_date?->format('M j, Y · g:i A') ?? 'any time' }}
                                        · {{ $assignment->max_score }} points
                                        @if ($submission?->score !== null)
                                            · Scored {{ $submission->score }}
                                        @endif
                                    </p>
                                </div>

                                <a href="{{ route('assignments.show', $assignment) }}"
                                    class="btn {{ $status === 'pending' || $status === 'overdue' ? 'btn-primary' : 'btn-secondary' }} btn-sm shrink-0">
                                    {{ $status === 'graded' ? 'View feedback' : ($status === 'submitted' ? 'View submission' : 'Open') }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
            {{-- Speaker notes / resources tabs (demo interactions) --}}
            <section class="card mt-5 p-2.5" x-data="{ tab: 'overview' }">
                <div class="flex gap-1 rounded-xl bg-slate-100 p-1">
                    @foreach (['overview' => 'Overview', 'resources' => 'Resources', 'notes' => 'My notes'] as $key => $tab)
                        <button type="button" @click="tab = '{{ $key }}'"
                            class="flex-1 rounded-lg px-3 py-2 text-[13px] font-semibold transition"
                            :class="tab === '{{ $key }}' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'">
                            {{ $tab }}
                        </button>
                    @endforeach
                </div>

                {{-- Overview --}}
                <div x-show="tab === 'overview'" x-cloak class="px-3.5 py-4" style="display: none">
                    <p class="text-sm leading-relaxed text-slate-500">
                        {{ $lesson->content }}
                    </p>
                </div>

                {{-- Resources --}}
                <div x-show="tab === 'resources'" x-cloak class="px-3.5 py-4" style="display: none">
                    <ul class="space-y-2">
                        @foreach ($lesson->resources as $resource)
                            <li class="flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200/70">
                                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-white text-brand-600 ring-1 ring-slate-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-800">{{ $resource->title }}</p>
                                    <p class="text-xs text-slate-500">{{ $resource[1] }}</p>
                                </div>
                            <a href="{{ Storage::url($resource->file_path) }}" download
                                class="ml-auto shrink-0 text-xs font-semibold text-brand-600 hover:text-brand-700 hover:underline cursor-pointer">
                                Download
                            </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Notes --}}
                {{-- Notes Tab --}}
                <div x-show="tab === 'notes'" x-cloak class="px-3.5 py-4 space-y-6" style="display: none">

                    @livewire('lesson-notes', ['lesson' => $lesson])
                </div>
            </section>
        </div>
        <aside class="lg:sticky lg:top-24">
            <div class="card overflow-hidden">
                <button type="button" @click="open = !open"
                    class="flex w-full items-center justify-between gap-3 bg-slate-50/70 px-5 py-4 text-left transition hover:bg-slate-100/70"
                    aria-hidden="{{ $lesson->title }}">
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-bold text-slate-900">{{ $course->title }}</span>
                        <span class="text-xs text-slate-500">{{ $this->orderedLessons()->search(fn(Lesson $l) => $l->is($this->lesson)) + 1 }} of {{ $this->orderedLessons()->count() }} lessons</span>
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4 shrink-0 text-slate-400 transition-transform" :class="open && 'rotate-180'"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                </button>

                {{-- Progress --}}
                <div class="progress-track mt-4 mb-1 rounded-none" x-show="open" x-cloak>
                    @php
$playerTotal = $this->orderedLessons()->count();
$playerDone = auth()->check() ? $this->orderedLessons()->filter(fn(Lesson $l) => $l->isCompletedFor(auth()->user()))->count() : 0;
                    @endphp
                    <div class="progress-fill" style="width: {{ $playerTotal > 0 ? (int) round($playerDone / $playerTotal * 100) : 0 }}%"></div>
                </div>

                <nav class="max-h-[65vh] overflow-y-auto pb-3" x-show="open" x-cloak>
                    @foreach ($course->sections as $section)
                        <details class="group border-t border-slate-100 first:border-t-0" @if ($section->lessons->contains(fn($l) => $l->is($this->lesson))) open @endif>
                            <summary class="flex cursor-pointer items-center justify-between gap-2 px-5 py-3 transition marker:content-none hover:bg-slate-50">
                                <span class="truncate text-xs font-bold tracking-wide text-slate-500 uppercase">{{ $section->title }}</span>
                                <span class="shrink-0 text-[11px] text-slate-400">{{ $section->lessons->count() }}</span>
                            </summary>

                            <ul class="px-2.5 pb-2">
                                @foreach ($section->lessons as $lessonItem)
                                    @php
                                        $active = $lessonItem->is($lesson);
                                        $itemCompleted = auth()->check() && $lessonItem->isCompletedFor(auth()->user());
                                        $itemWatch = (int) ($this->lessonProgressByLesson[$lessonItem->id] ?? 0);
                                    @endphp
                                    <li wire:key="player-lesson-{{ $lessonItem->id }}">
                                        <a href="{{ route('lessons.show', ['course' => $course, 'lesson' => $lessonItem]) }}"
                                            class="flex items-center gap-2.5 rounded-xl px-2.5 py-2 transition {{ $active ? 'bg-brand-50 ring-1 ring-brand-200' : 'hover:bg-slate-50' }}">
                                            @if ($itemCompleted)
                                                <span class="grid size-5 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-3"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                                </span>
                                            @else
                                                <span class="grid size-5 shrink-0 place-items-center rounded-full {{ $active ? 'bg-brand-600 text-[10px] font-bold text-white' : 'bg-slate-100 text-[10px] font-bold text-slate-500' }}">
                                                    {{ $lessonItem->position + 1 }}
                                                </span>
                                            @endif

                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-[13px] {{ $active ? 'font-semibold text-brand-800' : 'text-slate-600' }}">
                                                    {{ $lessonItem->title }}
                                                </span>

                                                @if ($itemWatch > 0 && ! $itemCompleted)
                                                    <span class="mt-1 flex items-center gap-1.5">
                                                        <span class="h-1 flex-1 overflow-hidden rounded-full bg-slate-200/80">
                                                            <span class="block h-full rounded-full bg-brand-500" style="width: {{ $itemWatch }}%"></span>
                                                        </span>
                                                        <span class="text-[10px] font-semibold text-slate-400 tabular-nums">{{ $itemWatch }}%</span>
                                                    </span>
                                                @endif
                                            </span>

                                            @if ($lessonItem->duration_minutes)
                                                <span class="shrink-0 text-[11px] text-slate-400">{{ $lessonItem->duration_minutes }}m</span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endforeach
                </nav>
            </div>
        </aside>
    </div>
</div>
@script
<script>
    function loadYouTubeApi(callback) {
        if (window.YT && window.YT.Player) {
            callback();

            return;
        }

        window.__lessonYouTubeCallbacks = window.__lessonYouTubeCallbacks || [];
        window.__lessonYouTubeCallbacks.push(callback);

        if (! window.__lessonYouTubeApiHooked) {
            window.__lessonYouTubeApiHooked = true;

            const previous = window.onYouTubeIframeAPIReady;

            window.onYouTubeIframeAPIReady = () => {
                if (typeof previous === 'function') {
                    previous();
                }

                const callbacks = window.__lessonYouTubeCallbacks || [];
                window.__lessonYouTubeCallbacks = [];

                callbacks.forEach((resolve) => resolve());
            };
        }

        if (! document.querySelector('script[src="https://www.youtube.com/iframe_api"]')) {
            const script = document.createElement('script');
            script.src = 'https://www.youtube.com/iframe_api';
            document.head.appendChild(script);
        }
    }

    (() => {
        const root = $wire.$el;
        const container = root.querySelector('[data-video-tracker]');

        if (! container || container.dataset.trackerReady === '1') {
            return;
        }

        container.dataset.trackerReady = '1';

        const playerEl = container.querySelector('#lesson-video');

        if (! playerEl) {
            return;
        }

        const panel = root.querySelector('[data-video-progress-panel]');
        const percentLabel = panel?.querySelector('[data-progress-percent]');
        const progressBar = panel?.querySelector('[data-progress-bar]');
        const timeLabel = panel?.querySelector('[data-progress-time]');
        const hint = panel?.querySelector('[data-progress-hint]');
        const completedBanner = root.querySelector('[data-client-completed-banner]');

        const canTrack = container.dataset.canTrack === '1';
        const progressUrl = container.dataset.progressUrl;
        const csrfToken = container.dataset.csrf;

        let resumePosition = Number(container.dataset.resumePosition || 0);
        let lastPosition = resumePosition;
        let furthestPosition = Number(container.dataset.furthestPosition || 0);
        let secondsWatched = Number(container.dataset.secondsWatched || 0);
        let durationSeconds = Number(container.dataset.durationSeconds || 0);
        let completed = container.dataset.completed === '1';

        let isPlaying = false;
        let lastSamplePosition = null;
        let saveTimer = null;
        let saveInFlight = false;
        let saveQueued = false;

        const isDirect = playerEl.tagName === 'VIDEO';
        const playerSrc = isDirect
            ? (playerEl.currentSrc || playerEl.src || '')
            : (playerEl.src || '');
        const isYouTube = ! isDirect && /(?:youtube\.com|youtube-nocookie\.com)\/embed\//i.test(playerSrc);
        const isVimeo = ! isDirect && /player\.vimeo\.com/i.test(playerSrc);
        const trackable = canTrack && (isDirect || isYouTube || isVimeo);

        const clamp = (value, min, max) => Math.max(min, Math.min(max, value));

        function formatTime(seconds) {
            const total = Math.max(0, Math.floor(seconds));
            const hours = Math.floor(total / 3600);
            const minutes = Math.floor((total % 3600) / 60);
            const remaining = total % 60;

            return hours > 0
                ? `${hours}:${String(minutes).padStart(2, '0')}:${String(remaining).padStart(2, '0')}`
                : `${minutes}:${String(remaining).padStart(2, '0')}`;
        }

        function watchPercentage() {
            if (completed) {
                return 100;
            }

            if (durationSeconds <= 0) {
                return 0;
            }

            return clamp(
                Math.round((Math.max(furthestPosition, lastPosition) / durationSeconds) * 100),
                0,
                100,
            );
        }

        function paint() {
            const percentage = watchPercentage();

            if (percentLabel) {
                percentLabel.textContent = String(percentage);
            }

            if (progressBar) {
                progressBar.style.width = `${percentage}%`;
            }

            if (timeLabel) {
                timeLabel.textContent = `${formatTime(secondsWatched)} watched`;
            }

            if (panel) {
                panel.dataset.percentage = String(percentage);
            }
        }

        function markCompleted() {
            if (completed) {
                return;
            }

            completed = true;
            furthestPosition = Math.max(furthestPosition, lastPosition, durationSeconds);
            paint();

            if (completedBanner) {
                completedBanner.classList.remove('hidden');
                completedBanner.classList.add('flex');
            }

            // The server has already completed the lesson; re-render so the
            // banner, button and curriculum tick reflect it. The video frame is
            // wrapped in wire:ignore, so playback is not interrupted.
            $wire.$refresh();
        }

        function sample(position) {
            if (! Number.isFinite(position)) {
                return;
            }

            if (isPlaying && lastSamplePosition !== null) {
                const delta = position - lastSamplePosition;

                // A jump over 2.5s is a seek, not continuous playback.
                if (delta > 0 && delta <= 2.5) {
                    secondsWatched += delta;
                }
            }

            lastSamplePosition = position;
            lastPosition = position;
            furthestPosition = Math.max(furthestPosition, position);
            paint();
        }

        async function saveProgress({ keepalive = false } = {}) {
            if (! trackable) {
                return;
            }

            if (saveInFlight) {
                saveQueued = true;

                return;
            }

            saveInFlight = true;

            try {
                const response = await fetch(progressUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    keepalive,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        last_position: Math.floor(lastPosition),
                        furthest_position: Math.floor(furthestPosition),
                        seconds_watched: Math.floor(secondsWatched),
                        duration_seconds: Math.floor(durationSeconds),
                    }),
                });

                if (! response.ok) {
                    console.error('Lesson progress save failed:', response.status);

                    return;
                }

                const data = await response.json();

                durationSeconds = Math.max(durationSeconds, Number(data.duration_seconds || 0));
                lastPosition = Number(data.last_position ?? lastPosition);
                furthestPosition = Number(data.furthest_position ?? furthestPosition);
                secondsWatched = Number(data.seconds_watched ?? secondsWatched);

                paint();

                if (data.completed) {
                    markCompleted();
                }
            } catch (error) {
                console.error('Lesson progress save error:', error);
            } finally {
                saveInFlight = false;

                if (saveQueued) {
                    saveQueued = false;
                    saveProgress();
                }
            }
        }

        function startTracking() {
            if (! trackable || saveTimer) {
                return;
            }

            saveTimer = setInterval(() => saveProgress(), 10000);
        }

        function stopTracking() {
            isPlaying = false;
            lastSamplePosition = null;

            if (saveTimer) {
                clearInterval(saveTimer);
                saveTimer = null;
            }

            saveProgress();
        }

        if (! trackable) {
            if (hint) {
                hint.textContent = canTrack
                    ? 'Progress tracking is not available for this video provider.'
                    : 'Sign in to save your progress and resume later.';
            }

            return;
        }

        if (isDirect) {
            const seekToSavedPosition = () => {
                if (resumePosition <= 0) {
                    return;
                }

                const duration = Number.isFinite(playerEl.duration) ? playerEl.duration : 0;
                const target = duration > 0
                    ? Math.min(resumePosition, Math.max(0, duration - 0.5))
                    : resumePosition;

                playerEl.currentTime = target;
                lastPosition = target;
                furthestPosition = Math.max(furthestPosition, target);
                resumePosition = target;
            };

            if (playerEl.readyState >= 1) {
                seekToSavedPosition();
            } else {
                playerEl.addEventListener('loadedmetadata', seekToSavedPosition, { once: true });
            }

            playerEl.addEventListener('loadedmetadata', () => {
                if (Number.isFinite(playerEl.duration) && playerEl.duration > 0) {
                    durationSeconds = Math.max(durationSeconds, Math.round(playerEl.duration));
                }

                paint();
            });

            playerEl.addEventListener('play', () => {
                isPlaying = true;
                lastSamplePosition = playerEl.currentTime || 0;
                startTracking();
            });

            playerEl.addEventListener('pause', () => {
                sample(playerEl.currentTime || 0);
                stopTracking();
            });

            playerEl.addEventListener('timeupdate', () => {
                sample(playerEl.currentTime || 0);
            });

            playerEl.addEventListener('seeking', () => {
                lastSamplePosition = playerEl.currentTime || 0;
            });

            playerEl.addEventListener('ended', () => {
                const total = Number.isFinite(playerEl.duration)
                    ? playerEl.duration
                    : lastPosition;

                durationSeconds = Math.max(durationSeconds, Math.round(total));
                lastPosition = total;
                furthestPosition = Math.max(furthestPosition, total);
                paint();
                stopTracking();
            });
        } else if (isYouTube) {
            loadYouTubeApi(() => {
                const player = new window.YT.Player(playerEl, {
                    events: {
                        onReady: (event) => {
                            const duration = Number(event.target.getDuration?.() || 0);

                            if (duration > 0) {
                                durationSeconds = Math.max(durationSeconds, Math.round(duration));
                            }

                            if (resumePosition > 0) {
                                event.target.seekTo(resumePosition, true);
                            }

                            paint();
                        },

                        onStateChange: (event) => {
                            const currentTime = Number(event.target.getCurrentTime?.() || 0);

                            if (event.data === window.YT.PlayerState.PLAYING) {
                                isPlaying = true;
                                lastSamplePosition = currentTime;
                                startTracking();

                                return;
                            }

                            if (event.data === window.YT.PlayerState.PAUSED) {
                                sample(currentTime);
                                stopTracking();

                                return;
                            }

                            if (event.data === window.YT.PlayerState.ENDED) {
                                const total = Number(event.target.getDuration?.() || currentTime);

                                durationSeconds = Math.max(durationSeconds, Math.round(total));
                                lastPosition = total;
                                furthestPosition = Math.max(furthestPosition, total);
                                paint();
                                stopTracking();
                            }
                        },
                    },
                });

                setInterval(() => {
                    if (isPlaying && typeof player.getCurrentTime === 'function') {
                        sample(Number(player.getCurrentTime() || lastPosition));
                    }
                }, 1000);
            });
        } else if (isVimeo) {
            const post = (method, value) => {
                playerEl.contentWindow?.postMessage(
                    JSON.stringify({ method, value }),
                    '*',
                );
            };

            playerEl.addEventListener('load', () => {
                post('addEventListener', 'play');
                post('addEventListener', 'pause');
                post('addEventListener', 'timeupdate');
                post('addEventListener', 'seeked');
                post('addEventListener', 'ended');
                post('getDuration');

                if (resumePosition > 0) {
                    post('setCurrentTime', resumePosition);
                }
            });

            window.addEventListener('message', (event) => {
                if (event.source !== playerEl.contentWindow) {
                    return;
                }

                let data = event.data;

                if (typeof data === 'string') {
                    try {
                        data = JSON.parse(data);
                    } catch {
                        return;
                    }
                }

                if (! data || typeof data !== 'object') {
                    return;
                }

                if (data.event === 'play') {
                    isPlaying = true;
                    lastSamplePosition = lastPosition;
                    startTracking();

                    return;
                }

                if (data.event === 'pause') {
                    stopTracking();

                    return;
                }

                if (data.event === 'seeked') {
                    lastSamplePosition = Number(data.data?.seconds || lastPosition);

                    return;
                }

                if (data.event === 'timeupdate' && data.data) {
                    const duration = Number(data.data.duration || 0);

                    if (duration > 0) {
                        durationSeconds = Math.max(durationSeconds, Math.round(duration));
                    }

                    sample(Number(data.data.seconds || 0));

                    return;
                }

                if (data.event === 'ended') {
                    const total = Number(data.data?.duration || lastPosition);

                    durationSeconds = Math.max(durationSeconds, Math.round(total));
                    lastPosition = total;
                    furthestPosition = Math.max(furthestPosition, total);
                    paint();
                    stopTracking();
                }
            });
        }

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') {
                saveProgress({ keepalive: true });
            }
        });

        window.addEventListener('pagehide', () => {
            saveProgress({ keepalive: true });
        }, { once: true });

        paint();
    })();
</script>
@endscript

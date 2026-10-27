<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public function mount()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
    }

    /**
     * The user's enrollments with the course curriculum eagerly loaded.
     */
    #[Computed]
    public function enrollments(): \Illuminate\Database\Eloquent\Collection
    {
        return auth()->user()?->enrollments()
            ->with('course.category', 'course.instructor', 'course.sections.lessons')
            ->latest('created_at')
            ->get() ?? new \Illuminate\Database\Eloquent\Collection();
    }

    /**
     * The certificates the user has earned.
     */
    #[Computed]
    public function certificates()
    {
        return auth()->user()?->certificates()
            ->with('course')
            ->latest('issued_at')
            ->get() ?? collect();
    }

    /**
     * How many lessons of the course the student has completed.
     */
    public function completedLessonsFor(Enrollment $enrollment): int
    {
        return $this->progressCounts()[$enrollment->course_id] ?? 0;
    }

    /**
     * The total number of lessons in the course.
     */
    public function totalLessonsFor(Enrollment $enrollment): int
    {
        return $enrollment->course->sections->flatMap->lessons->count();
    }

    /**
     * Completed lesson counts per course, computed with a single query.
     *
     * @return array<int, int>
     */
    #[Computed]
    private function progressCounts(): array
    {
        $lessonIdsByCourse = $this->enrollments()
            ->map(fn(Enrollment $enrollment) => $enrollment->course)
            ->mapWithKeys(fn(Course $course) => [
                $course->getKey() => $course->sections->flatMap->lessons->pluck('id')->all(),
            ])
            ->filter(fn(array $lessonIds) => $lessonIds !== []);

        if ($lessonIdsByCourse->isEmpty()) {
            return [];
        }

        $counts = LessonProgress::query()
            ->where('user_id', auth()->id())
            ->completed()
            ->whereIn('lesson_id', $lessonIdsByCourse->flatten()->unique()->all())
            ->get()
            ->countBy('lesson_id');

        return $lessonIdsByCourse
            ->map(fn(array $lessonIds) => collect($lessonIds)
                ->sum(fn(int $lessonId) => $counts[$lessonId] ?? 0))
            ->all();
    }

    /**
     * Whether the student finished every lesson of the course.
     */
    public function isCourseComplete(Enrollment $enrollment): bool
    {
        $total = $this->totalLessonsFor($enrollment);

        return $total > 0 && $this->completedLessonsFor($enrollment) >= $total;
    }

    /**
     * The lesson the student should continue with, or null once
     * everything is done.
     */
    public function resumeLessonFor(Enrollment $enrollment): ?Lesson
    {
        return $enrollment->course->resumeLessonFor(auth()->user());
    }
};
?>

<div class="animate-fade-up">
    {{-- ============================ Welcome ============================ --}}
    <section
        class="card mt-4 flex flex-col gap-5 overflow-hidden px-6 py-7 sm:flex-row sm:items-center sm:justify-between sm:px-8"
        style="background: linear-gradient(120deg, #1e1b4b, #312e81); border: 0;">
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="absolute -top-20 right-10 h-56 w-72 rounded-full bg-brand-500/30 blur-[80px]"></div>
        </div>

        <div class="relative">
            <p class="text-xs font-semibold tracking-widest text-brand-300 uppercase">Welcome back</p>
            <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">
                Hi, {{ str(auth()->user()->name)->before(' ') }} 👋
            </h1>
            <p class="mt-1.5 text-sm text-brand-200/80">Pick up where you left off, or review a course you already
                finished.</p>

            <div class="mt-5 flex flex-wrap gap-6">
                @foreach ([['12', 'day streak'], [$this->enrollments->count(), 'active course' . ($this->enrollments->count() === 1 ? '' : 's')], [$this->certificates->count(), 'certificate' . ($this->certificates->count() === 1 ? '' : 's')]] as $stat)
                    <div>
                        <p class="text-lg font-extrabold text-white">{{ $stat[0] }}</p>
                        <p class="text-xs text-brand-200/70">{{ $stat[1] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <x-ui.avatar :name="auth()->user()->name" size="xl"
            class="relative shrink-0 ring-4 ring-white/10 max-sm:hidden" />
    </section>

    {{-- ============================ Layout ============================ --}}
    <div class="grid gap-8 py-8 lg:grid-cols-[240px_1fr]">
        {{-- Sidebar --}}
        <x-ui.student-sidebar
            active="overview"
            :enrollments-count="$this->enrollments->count()"
            :lessons-done="$this->enrollments->sum(fn (Enrollment $enrollment) => $this->completedLessonsFor($enrollment))"
        />

        {{-- Main column --}}
        <div class="min-w-0">
            {{-- Continue learning --}}
            @php
                $continueEnrollment = $this->enrollments->first(fn(Enrollment $e) => !$this->isCourseComplete($e));
            @endphp

            @if ($continueEnrollment !== null)
                @php
                    $continueCourse = $continueEnrollment->course;
                    $continueTotal = $this->totalLessonsFor($continueEnrollment);
                    $continueDone = $this->completedLessonsFor($continueEnrollment);
                    $continuePercent = $continueTotal > 0 ? (int) round($continueDone / $continueTotal * 100) : 0;
                    $resume = $this->resumeLessonFor($continueEnrollment);
                @endphp

                <section class="card relative isolate mb-8 overflow-hidden p-6">
                    <div class="pointer-events-none absolute inset-0 opacity-[0.05]" aria-hidden="true">
                        <svg class="size-full" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <pattern id="dash-dots" width="20" height="20" patternUnits="userSpaceOnUse">
                                    <circle cx="1.5" cy="1.5" r="1.2" fill="#4f46e5" />
                                </pattern>
                            </defs>
                            <rect width="100%" height="100%" fill="url(#dash-dots)" />
                        </svg>
                    </div>

                    <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center">
                        <x-ui.thumb :seed="$continueCourse->title" class="h-20 w-32 shrink-0 rounded-xl"
                            :label="$continueCourse->title" />

                        <div class="min-w-0 grow">
                            <p class="text-xs font-semibold tracking-widest text-brand-600 uppercase">Continue learning</p>
                            <h2 class="mt-1.5 truncate text-lg font-bold text-slate-900">{{ $continueCourse->title }}</h2>
                            <p class="mt-0.5 text-sm text-slate-500">Up next: <span
                                    class="font-medium text-slate-700">{{ $resume?->title ?? 'Review the course' }}</span>
                            </p>

                            <div class="mt-3 max-w-sm">
                                <div class="mb-1 flex justify-between text-xs font-medium text-slate-500">
                                    <span>{{ $continueDone }} / {{ $continueTotal }} lessons</span>
                                    <span>{{ $continuePercent }}%</span>
                                </div>
                                <div class="progress-track">
                                    <div class="progress-fill" style="width: {{ $continuePercent }}%"></div>
                                </div>
                            </div>
                        </div>

                        @if ($resume !== null)
                            <a href="{{ route('lessons.show', ['course' => $continueCourse, 'lesson' => $resume]) }}"
                                class="btn btn-primary btn-lg shrink-0">
                                Continue
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="size-4 text-white">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        @endif
                    </div>
                </section>
            @endif

            {{-- Enrolled courses --}}
            <section id="courses" class="mb-8">
                <h2 class="section-title mb-4">My enrolled course{{ $this->enrollments->count() === 1 ? '' : 's' }}</h2>

                @if ($this->enrollments->isEmpty())
                    <x-ui.empty-state title="You're not enrolled in any course yet"
                        description="Pick your first course and start building momentum — most students finish a lesson on day one."
                        icon="courses">
                        <x-slot name="actions">
                            <a href="{{ route('courses.index') }}" class="btn btn-primary btn-md">Browse courses</a>
                        </x-slot>
                    </x-ui.empty-state>
                @else
                    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($this->enrollments as $enrollment)
                            @php
                                $course = $enrollment->course;
                                $total = $this->totalLessonsFor($enrollment);
                                $completed = $this->completedLessonsFor($enrollment);
                                $percent = $total > 0 ? (int) round($completed / $total * 100) : 0;
                                $resume = $this->resumeLessonFor($enrollment);
                            @endphp

                            <article class="card card-hover flex flex-col overflow-hidden"
                                wire:key="enrollment-{{ $enrollment->id }}">
                                <x-ui.thumb :seed="$course->title" class="aspect-video w-full" :label="$course->title"
                                    icon='<svg xmlns="http://www.w3.org/2000/svg" fill="#fff" viewBox="0 0 24 24" class="size-5"><path d="M8 5.14v14l11-7-11-7Z"/></svg>' />

                                <div class="flex grow flex-col gap-2 p-4 sm:p-5">
                                    <div class="flex items-center justify-between gap-2">
                                        <span
                                            class="truncate text-[11px] font-semibold tracking-widest text-brand-600 uppercase">
                                            {{ $course->category?->name ?? 'General' }}
                                        </span>
                                        @if ($this->isCourseComplete($enrollment) || $enrollment->status === 'completed')
                                            <span
                                                class="badge bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">Completed</span>
                                        @else
                                            <span class="badge bg-amber-50 text-amber-700 ring-1 ring-amber-200">In progress</span>
                                        @endif
                                    </div>

                                    <h3 class="text-[15px] leading-snug font-bold text-slate-900">{{ $course->title }}</h3>
                                    <p class="text-[13px] text-slate-500">by {{ $course->instructor?->name }}</p>

                                    <div class="mt-auto pt-3">
                                        <div class="mb-1 flex justify-between text-xs font-medium text-slate-500">
                                            <span>{{ $completed }} / {{ $total }} lessons</span>
                                            <span>{{ $percent }}%</span>
                                        </div>
                                        <div class="progress-track">
                                            <div class="progress-fill" style="width: {{ $percent }}%"></div>
                                        </div>

                                        @if ($resume !== null)
                                            <a href="{{ route('lessons.show', ['course' => $course, 'lesson' => $resume]) }}"
                                                class="btn btn-primary btn-md mt-4 w-full">
                                                {{ $completed > 0 ? 'Continue' : 'Start course' }}
                                            </a>
                                        @else
                                            <a href="{{ route('courses.show', $course) }}" class="btn btn-dark btn-md mt-4 w-full">
                                                Review course
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Assignments & notifications (mock) --}}
            {{-- <div class="mb-8 grid gap-6 lg:grid-cols-[1.4fr_1fr]">
                <section class="card p-6">
                    <h2 class="text-base font-bold text-slate-900">Upcoming assignments <span
                            class="badge ml-1 bg-amber-50 text-amber-700 ring-1 ring-amber-200">2 due</span></h2>

                    <ul class="mt-4 space-y-3">
                        @foreach ([
                        ['Portfolio: Landing page build', 'Design Systems with Figma', 'Due in 2 days', 'bg-amber-50
                        text-amber-700 ring-amber-200'],
                        ['Quiz: Nailing SQL joins', 'SQL for Everyone', 'Due in 5 days', 'bg-brand-50 text-brand-700
                        ring-brand-200'],
                        ['Refactor challenge submission', 'Modern JavaScript', 'Due next week', 'bg-emerald-50
                        text-emerald-700 ring-emerald-200'],
                        ] as $assignment)
                        <li class="flex items-center gap-4 rounded-xl bg-slate-50 p-3.5 ring-1 ring-slate-200/70">
                            <span
                                class="grid size-10 shrink-0 place-items-center rounded-xl bg-white text-brand-600 ring-1 ring-slate-200">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.6" stroke="currentColor" class="size-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $assignment[0] }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $assignment[1] }}</p>
                            </div>
                            <span class="badge ml-auto shrink-0 ring-1 {{ $assignment[3] }}">{{ $assignment[2] }}</span>
                        </li>
                        @endforeach
                    </ul>
                </section>

                <section class="card p-6">
                    <h2 class="text-base font-bold text-slate-900">Notifications</h2>

                    <ul class="mt-4 space-y-4">
                        @foreach ([
                        ['certificate', 'Your certificate is ready', 'UX Foundations · download any time.'],
                        ['reminder', 'Keep the streak alive', 'You just need one lesson today.'],
                        ['comment', 'New reply from an instructor', 'Ian answered your question on async/await.'],
                        ] as $notification)
                        <li class="flex gap-3">
                            @if ($notification[0] === 'certificate')
                            <span
                                class="grid size-9 shrink-0 place-items-center rounded-full bg-amber-100 text-amber-700">🏆</span>
                            @elseif ($notification[0] === 'reminder')
                            <span
                                class="grid size-9 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.8" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </span>
                            @else
                            <span
                                class="grid size-9 shrink-0 place-items-center rounded-full bg-brand-100 text-brand-700">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.8" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                                </svg>
                            </span>
                            @endif
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $notification[1] }}</p>
                                <p class="text-xs leading-relaxed text-slate-500">{{ $notification[2] }}</p>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </section>
            </div> --}}

            {{-- Certificates --}}
            @if ($this->certificates->isNotEmpty())
                <section id="certificates">
                    <h2 class="section-title mb-4">Your certificates</h2>

                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($this->certificates as $certificate)
                            <a href="{{ route('certificates.show', $certificate) }}"
                                class="card card-hover group relative overflow-hidden border-l-4 border-l-amber-400 p-5"
                                wire:key="certificate-{{ $certificate->id }}">
                                <div class="flex items-center justify-between">
                                    <span
                                        class="grid size-10 place-items-center rounded-xl bg-amber-100 text-amber-700">🏆</span>
                                    <span
                                        class="text-xs font-semibold text-amber-600 opacity-0 transition group-hover:opacity-100">View / print →</span>
                                </div>

                                <p class="mt-3 font-bold text-slate-900">{{ $certificate->course?->title }}</p>
                                <p class="mt-1 font-mono text-xs text-slate-500">{{ $certificate->certificate_number }}</p>
                                <p class="mt-1.5 text-xs text-slate-500">
                                    Issued {{ $certificate->issued_at?->format('M j, Y') }}
                                    @if ($certificate->final_score !== null)
                                        · Score {{ number_format($certificate->final_score, 0) }}%
                                    @endif
                                </p>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</div>
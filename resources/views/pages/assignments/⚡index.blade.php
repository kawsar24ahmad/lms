<?php

use App\Models\Assignment;
use App\Models\LessonProgress;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    /**
     * The active status filter.
     */
    #[Url]
    public string $status = 'all';

    public function mount(): void
    {
        if (!auth()->check()) {
            $this->redirectRoute('login');
        }
    }

    /**
     * Assignments from every course the student is enrolled in.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Assignment>
     */
    #[Computed]
    public function allAssignments(): \Illuminate\Database\Eloquent\Collection
    {
        $user = auth()->user();

        if ($user === null) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        return Assignment::query()
            ->forStudent($user)
            ->with([
                'lesson.section.course',
                'submissions' => fn($query) => $query->where('user_id', $user->getKey()),
            ])
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->get();
    }

    /**
     * The assignments matching the active filter.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Assignment>
     */
    #[Computed]
    public function assignments(): \Illuminate\Database\Eloquent\Collection
    {
        if ($this->status === 'all') {
            return $this->allAssignments;
        }

        return $this->allAssignments
            ->filter(fn(Assignment $assignment): bool => $this->statusFor($assignment) === $this->status)
            ->values();
    }

    /**
     * Counts per filter tab.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function counts(): array
    {
        $counts = [
            'all' => $this->allAssignments->count(),
            'pending' => 0,
            'submitted' => 0,
            'graded' => 0,
            'overdue' => 0,
        ];

        foreach ($this->allAssignments as $assignment) {
            $counts[$this->statusFor($assignment)]++;
        }

        return $counts;
    }

    /**
     * The number of courses the student is enrolled in.
     */
    #[Computed]
    public function enrollmentsCount(): int
    {
        return auth()->user()?->enrollments()->count() ?? 0;
    }

    /**
     * The number of lessons the student has completed.
     */
    #[Computed]
    public function lessonsCompleted(): int
    {
        $user = auth()->user();

        if ($user === null) {
            return 0;
        }

        return LessonProgress::query()
            ->forUser($user)
            ->completed()
            ->count();
    }

    /**
     * The student-facing status of an assignment.
     */
    public function statusFor(Assignment $assignment): string
    {
        $submission = $assignment->submissions->first();

        if ($submission === null) {
            return $assignment->isPastDue() ? 'overdue' : 'pending';
        }

        return $submission->isGraded() ? 'graded' : 'submitted';
    }
};
?>

<div class="animate-fade-up">
    {{-- Page heading --}}
    <section class="mt-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="eyebrow">Learning</p>
            <h1 class="section-title mt-1">Assignments</h1>
            <p class="mt-1.5 text-sm text-slate-500">
                Submit links to your Google Docs, GitHub repositories or videos — no file uploads needed.
            </p>
        </div>

        <a href="{{ route('courses.index') }}" class="btn btn-secondary btn-md shrink-0">Browse courses</a>
    </section>

    <div class="grid gap-8 py-8 lg:grid-cols-[240px_1fr]">
        <x-ui.student-sidebar
            active="assignments"
            :enrollments-count="$this->enrollmentsCount"
            :lessons-done="$this->lessonsCompleted"
        />

        <div class="min-w-0">
            {{-- Status filters --}}
            <div class="mb-5 flex flex-wrap gap-2">
                @foreach ([
        'all' => 'All',
        'pending' => 'Pending',
        'submitted' => 'Submitted',
        'graded' => 'Graded',
        'overdue' => 'Overdue',
    ] as $key => $label)
                    <button type="button" wire:click="$set('status', '{{ $key }}')"
                        class="chip {{ $status === $key ? 'chip-active' : '' }}">
                        {{ $label }}
                        <span class="text-xs opacity-70">{{ $this->counts[$key] }}</span>
                    </button>
                @endforeach
            </div>

            @if ($this->assignments->isEmpty())
                <x-ui.empty-state title="No assignments here"
                    description="When an instructor adds an assignment to one of your lessons, it will show up here."
                    icon="inbox">
                    <x-slot name="actions">
                        <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-md">Back to dashboard</a>
                    </x-slot>
                </x-ui.empty-state>
            @else
                <div class="space-y-3">
                    @foreach ($this->assignments as $assignment)
                        @php
                            $submission = $assignment->submissions->first();
                            $status = $this->statusFor($assignment);
                            $dueDate = $assignment->due_date;
                            $course = $assignment->lesson?->section?->course;
                        @endphp

                        <article class="card card-hover p-5" wire:key="assignment-{{ $assignment->id }}">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                                <span
                                    class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600 ring-1 ring-brand-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.7" stroke="currentColor" class="size-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                                    </svg>
                                </span>

                                <div class="min-w-0 grow">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="text-[15px] font-bold text-slate-900">{{ $assignment->title }}</h2>

                                        @if ($status === 'graded')
                                            <span class="badge bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">Graded</span>
                                        @elseif ($status === 'submitted')
                                            <span class="badge bg-brand-50 text-brand-700 ring-1 ring-brand-200">Submitted</span>
                                        @elseif ($status === 'overdue')
                                            <span class="badge bg-rose-50 text-rose-700 ring-1 ring-rose-200">Overdue</span>
                                        @else
                                            <span class="badge bg-amber-50 text-amber-700 ring-1 ring-amber-200">Pending</span>
                                        @endif
                                    </div>

                                    <p class="mt-0.5 truncate text-xs text-slate-500">
                                        {{ $course?->title }}
                                        @if ($assignment->lesson)
                                            · {{ $assignment->lesson->title }}
                                        @endif
                                    </p>

                                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                                        <span>
                                            Due
                                            <strong class="{{ $dueDate !== null && $dueDate->isPast() ? 'text-rose-600' : 'text-slate-700' }}">
                                                {{ $dueDate?->format('M j, Y · g:i A') ?? 'No due date' }}
                                            </strong>
                                        </span>
                                        <span>Max score: <strong class="text-slate-700">{{ $assignment->max_score }}</strong></span>

                                        @if ($submission?->score !== null)
                                            <span>Your score: <strong class="text-emerald-700">{{ $submission->score }}</strong></span>
                                        @endif
                                    </div>
                                </div>

                                <a href="{{ route('assignments.show', $assignment) }}"
                                    class="btn {{ $status === 'pending' || $status === 'overdue' ? 'btn-primary' : 'btn-secondary' }} btn-md shrink-0">
                                    {{ $status === 'graded' ? 'View feedback' : ($status === 'submitted' ? 'View submission' : 'Open assignment') }}
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                        stroke="currentColor" class="size-3.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                    </svg>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

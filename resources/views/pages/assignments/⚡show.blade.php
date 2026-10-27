<?php

use App\Models\Assignment;
use App\Models\Submission;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public Assignment $assignment;

    /**
     * The submission link or note being edited.
     */
    public string $content = '';

    public function mount(?Assignment $assignment = null): void
    {
        if (!auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        if ($assignment !== null) {
            $this->assignment = $assignment;
        }

        $this->assignment->loadMissing('lesson.section.course');
        $this->content = $this->submission?->content ?? '';
    }

    /**
     * Guard every render: only students enrolled in the course may see
     * or submit the assignment.
     */
    public function boot(): void
    {
        if (!auth()->check()) {
            return;
        }

        $this->assignment->loadMissing('lesson.section.course');

        abort_unless(
            $this->assignment->lesson?->section?->course?->hasEnrolled(auth()->user()),
            403,
        );
    }

    /**
     * The current user's submission for this assignment.
     */
    #[Computed]
    public function submission(): ?Submission
    {
        return auth()->check()
            ? $this->assignment->submissionFor(auth()->user())
            : null;
    }

    /**
     * Whether the submission form should accept changes.
     */
    #[Computed]
    public function canSubmit(): bool
    {
        if ($this->assignment->isPastDue()) {
            return false;
        }

        return $this->submission === null || ! $this->submission->isGraded();
    }

    /**
     * Persist the pasted link or note as the student's submission.
     */
    public function submit(): void
    {
        if (!auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $this->validate([
            'content' => ['required', 'string', 'max:5000'],
        ], [], [
            'content' => 'submission',
        ]);

        $this->assignment->loadMissing('lesson.section.course');

        abort_unless(
            $this->assignment->lesson?->section?->course?->hasEnrolled(auth()->user()),
            403,
        );

        abort_if($this->assignment->isPastDue(), 403);

        $submission = $this->submission;

        if ($submission?->isGraded()) {
            $this->addError('content', 'This submission has already been graded and can no longer be changed.');

            return;
        }

        Submission::query()->updateOrCreate(
            [
                'assignment_id' => $this->assignment->getKey(),
                'user_id' => auth()->id(),
            ],
            [
                'content' => trim($this->content),
                'status' => Submission::STATUS_PENDING,
                'submitted_at' => now(),
            ],
        );

        $this->content = trim($this->content);

        unset($this->submission);

        session()->flash('status', $submission === null
            ? 'Assignment submitted successfully.'
            : 'Your submission has been updated.');
    }

    /**
     * Escape the submission and turn bare URLs into clickable links.
     */
    public function linkedContent(): string
    {
        $content = e($this->submission?->content ?? '');

        return preg_replace(
            '~(https?://[^\s<]+)~i',
            '<a href="$1" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-600 underline hover:text-brand-700">$1</a>',
            $content,
        ) ?? $content;
    }

    /**
     * The student-facing status of this assignment.
     */
    #[Computed]
    public function status(): string
    {
        if ($this->submission === null) {
            return $this->assignment->isPastDue() ? 'overdue' : 'pending';
        }

        return $this->submission->isGraded() ? 'graded' : 'submitted';
    }
};
?>

<div class="animate-fade-up">
    <div class="mt-2 mb-5 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
        <a href="{{ route('assignments.index') }}"
            class="inline-flex items-center gap-1.5 font-medium text-slate-500 transition hover:text-brand-600">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                class="size-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            All assignments
        </a>

        <a href="{{ route('lessons.show', ['course' => $assignment->lesson->section->course, 'lesson' => $assignment->lesson]) }}"
            class="inline-flex items-center gap-1.5 font-medium text-slate-500 transition hover:text-brand-600">
            Back to lesson
        </a>
    </div>

    <div class="grid gap-8 pb-8 lg:grid-cols-[240px_1fr]">
        <x-ui.student-sidebar
            active="assignments"
            :enrollments-count="auth()->user()->enrollments()->count()"
            :lessons-done="\App\Models\LessonProgress::query()->forUser(auth()->user())->completed()->count()"
        />

        <div class="min-w-0 space-y-5">
            {{-- Assignment brief --}}
            <section class="card overflow-hidden">
                <div class="border-b border-slate-100 bg-slate-50/60 px-6 py-5">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="eyebrow">{{ $assignment->lesson->section->course->title }}</span>

                        @if ($this->status === 'graded')
                            <span class="badge bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">Graded</span>
                        @elseif ($this->status === 'submitted')
                            <span class="badge bg-brand-50 text-brand-700 ring-1 ring-brand-200">Submitted</span>
                        @elseif ($this->status === 'overdue')
                            <span class="badge bg-rose-50 text-rose-700 ring-1 ring-rose-200">Overdue</span>
                        @else
                            <span class="badge bg-amber-50 text-amber-700 ring-1 ring-amber-200">Pending</span>
                        @endif
                    </div>

                    <h1 class="mt-2 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">
                        {{ $assignment->title }}
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        Lesson: <span class="font-medium text-slate-700">{{ $assignment->lesson->title }}</span>
                    </p>
                </div>

                <div class="p-6">
                    @if ($assignment->description)
                        <div class="prose-lesson">{{ $assignment->description }}</div>
                    @else
                        <p class="text-sm text-slate-500">Your instructor has not added extra instructions for this
                            assignment.</p>
                    @endif

                    <dl class="mt-6 grid gap-3 border-t border-slate-100 pt-5 text-sm sm:grid-cols-3">
                        <div class="rounded-xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200/70">
                            <dt class="text-xs font-medium text-slate-500">Due date</dt>
                            <dd
                                class="mt-0.5 font-semibold {{ $assignment->isPastDue() ? 'text-rose-600' : 'text-slate-900' }}">
                                {{ $assignment->due_date?->format('M j, Y · g:i A') ?? 'No due date' }}
                            </dd>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200/70">
                            <dt class="text-xs font-medium text-slate-500">Max score</dt>
                            <dd class="mt-0.5 font-semibold text-slate-900">{{ $assignment->max_score }} points</dd>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200/70">
                            <dt class="text-xs font-medium text-slate-500">Your submission</dt>
                            <dd class="mt-0.5 font-semibold text-slate-900">
                                {{ $this->submission?->submitted_at?->format('M j, Y · g:i A') ?? 'Not submitted yet' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </section>

            {{-- Grade & feedback --}}
            @if ($this->submission?->isGraded())
                <section class="card border-l-4 border-l-emerald-500 p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-base font-bold text-slate-900">Instructor feedback</h2>
                        <span class="badge bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                            {{ $this->submission->score ?? 0 }} / {{ $assignment->max_score }}
                        </span>
                    </div>

                    @if ($this->submission->feedback)
                        <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-600">
                            {{ $this->submission->feedback }}</p>
                    @else
                        <p class="mt-3 text-sm text-slate-500">Your work was graded without written feedback.</p>
                    @endif

                    <div class="mt-4 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200/70">
                        <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Your submission</p>
                        <p class="mt-2 text-sm leading-relaxed break-words whitespace-pre-line text-slate-700">
                            {!! $this->linkedContent() !!}</p>
                    </div>
                </section>
            @endif

            {{-- Submission form / submitted state --}}
            @if (!$this->submission?->isGraded())
                <section class="card p-6">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-bold text-slate-900">
                        {{ $this->submission === null ? 'Submit your work' : 'Your submission' }}
                    </h2>

                    @if ($this->submission !== null)
                        <span class="badge bg-brand-50 text-brand-700 ring-1 ring-brand-200">Submitted</span>
                    @endif
                </div>

                @if (session('status'))
                    <div
                        class="mt-4 flex items-center gap-2 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                            stroke="currentColor" class="size-4 shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        {{ session('status') }}
                    </div>
                @endif

                @if ($this->submission !== null && !$this->submission->isGraded())
                    <div class="mt-4 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200/70">
                        <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">What you submitted</p>
                        <p class="mt-2 text-sm leading-relaxed break-words whitespace-pre-line text-slate-700">
                            {!! $this->linkedContent() !!}</p>
                    </div>
                @endif

                @if ($this->canSubmit)
                    <form wire:submit="submit" class="mt-5">
                        <label for="submission-content" class="label">
                            {{ $this->submission === null ? 'Your link or note' : 'Update your link or note' }}
                        </label>

                        <textarea id="submission-content" wire:model="content" rows="5"
                            class="input resize-y"
                            placeholder="Paste a Google Docs, GitHub, video or other link — add a short note if you like."></textarea>

                        <p class="mt-1.5 text-xs text-slate-500">
                            File uploads are not required. Paste one link (or a few) so your instructor can review your
                            work.
                        </p>

                        @error('content')
                            <p class="help-error">{{ $message }}</p>
                        @enderror

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                            <p class="text-xs text-slate-500">
                                @if ($assignment->due_date)
                                    Due {{ $assignment->due_date->diffForHumans() }}.
                                @endif
                            </p>

                            <button type="submit" class="btn btn-primary btn-md" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="submit">
                                    {{ $this->submission === null ? 'Submit assignment' : 'Update submission' }}
                                </span>
                                <span wire:loading wire:target="submit" class="flex items-center gap-2">
                                    <svg class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="3" />
                                        <path d="M4 12a8 8 0 0 1 8-8" stroke="currentColor" stroke-width="3"
                                            stroke-linecap="round" />
                                    </svg>
                                    Saving…
                                </span>
                            </button>
                        </div>
                    </form>
                @elseif ($this->submission === null && $assignment->isPastDue())
                    <div
                        class="mt-5 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 ring-1 ring-rose-200">
                        The due date has passed, so this assignment can no longer be submitted.
                    </div>
                @elseif ($this->submission !== null && $assignment->isPastDue())
                    <div
                        class="mt-5 rounded-xl bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 ring-1 ring-amber-200">
                        The due date has passed. Your last submission is locked in and awaiting grading.
                    </div>
                @endif
                </section>
            @endif
        </div>
    </div>
</div>

<?php

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Livewire\Component;

new class extends Component {
    public Course $course;

    public Quiz $quiz;

    /**
     * Selected option ids, indexed by question id.
     */
    public array $answers = [];

    public function mount(?Course $course = null, ?Quiz $quiz = null): void
    {
        if ($course !== null) {
            $this->course = $course;
        }

        if ($quiz !== null) {
            $this->quiz = $quiz;
        }

        $this->quiz->loadMissing('questions.options');

        if (auth()->check()) {
            $this->answers = $this->attempt()->answers ?? [];
        }
    }

    /**
     * Guard every render: only enrolled users may take the quiz.
     */
    public function boot(): void
    {
        abort_unless(
            auth()->check() && $this->course->hasEnrolled(auth()->user()),
            403,
        );
    }

    /**
     * The current user's attempt for this quiz (one per user per quiz).
     */
    public function attempt(): QuizAttempt
    {
        return QuizAttempt::query()->firstOrCreate(
            [
                'quiz_id' => $this->quiz->getKey(),
                'user_id' => auth()->id(),
            ],
            [
                'answers' => [],
                'status' => QuizAttempt::STATUS_IN_PROGRESS,
            ],
        );
    }

    /**
     * Submit the selected answers for scoring.
     */
    public function submit(): void
    {
        abort_if($this->attempt()->isSubmitted(), 400);

        foreach ($this->quiz->questions as $question) {
            if (blank($this->answers[$question->getKey()] ?? null)) {
                $this->addError('submit', 'Please answer every question before submitting.');

                return;
            }
        }

        $this->attempt()->submit($this->answers);
    }

    /**
     * Clear the attempt so the student can take the quiz again.
     */
    public function retake(): void
    {
        $this->attempt()->retake();

        $this->answers = [];
    }

    /**
     * Whether a submitted attempt answered a question correctly, for the
     * per-question review.
     */
    public function isCorrectAnswer(int $questionId): bool
    {
        $question = $this->quiz->questions->firstWhere('id', $questionId);

        if ($question === null) {
            return false;
        }

        return $question->acceptsAnswer($this->attempt()->answers[$questionId] ?? null);
    }
};
?>

<div>
    <a href="{{ route('courses.show', $course) }}"
        class="mb-4 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-900">
        ← Back to course
    </a>

    <div class="rounded-xl bg-white ring-1 ring-gray-200">
        <div class="border-b border-gray-100 p-6 sm:p-8">
            <span class="text-xs font-medium uppercase tracking-wide text-amber-600">Knowledge check</span>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $quiz->title }}</h1>
            @if ($quiz->description)
                <p class="mt-2 max-w-2xl text-sm text-gray-500">{{ $quiz->description }}</p>
            @endif
            <p class="mt-2 text-sm font-medium text-gray-700">
                Pass score: {{ $quiz->pass_score }}%
            </p>
        </div>

        <div class="p-6 sm:p-8">
            @if ($this->attempt()->isSubmitted())
                @php $attempt = $this->attempt(); @endphp

                <div class="mb-8 flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                    <div
                        class="flex size-28 shrink-0 items-center justify-center rounded-full text-3xl font-bold text-white {{ $attempt->hasPassed() ? 'bg-emerald-500' : 'bg-red-400' }}">
                        {{ $attempt->score }}%
                    </div>

                    <div class="text-center sm:text-left">
                        <p class="text-lg font-bold {{ $attempt->hasPassed() ? 'text-emerald-700' : 'text-red-600' }}">
                            {{ $attempt->hasPassed() ? 'Yay — you passed!' : 'Not quite this time!' }}
                        </p>
                        <p class="text-sm text-gray-500">
                            You scored {{ $attempt->score }}%
                            (pass score: {{ $quiz->pass_score }}%).
                            {{ $attempt->hasPassed() ? '' : 'Review the material and try again.' }}
                        </p>
                    </div>
                </div>

                <ul class="mb-8 divide-y divide-gray-100 rounded-xl ring-1 ring-gray-200">
                    @foreach ($quiz->questions as $question)
                        @php $answeredCorrectly = $this->isCorrectAnswer($question->id); @endphp
                        <li class="flex items-start gap-3 px-5 py-3" wire:key="review-question-{{ $question->id }}">
                            <span
                                class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full text-xs text-white {{ $answeredCorrectly ? 'bg-emerald-500' : 'bg-red-400' }}">
                                {{ $answeredCorrectly ? '✓' : '✗' }}
                            </span>
                            <span class="text-sm text-gray-700">{{ $question->question_text }}</span>
                        </li>
                    @endforeach
                </ul>

                <button type="button" wire:click="retake"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-gray-200 transition hover:bg-gray-50">
                    Retake quiz
                </button>
            @else
                <form wire:submit="submit">
                    <div class="space-y-8">
                        @foreach ($quiz->questions as $index => $question)
                            <fieldset class="rounded-xl bg-gray-50 p-5 ring-1 ring-gray-200"
                                wire:key="question-{{ $question->id }}">
                                <legend class="mb-3 text-sm font-semibold text-gray-900">
                                    {{ $index + 1 }}. {{ $question->question_text }}
                                    <span class="ml-1 text-xs font-normal text-gray-400">({{ $question->points }}
                                        {{ $question->points === 1 ? 'pt' : 'pts' }})</span>
                                </legend>
                                @if ($question->type === 'true_false' && filled($question->correct_answer))
                                    <div class="space-y-2">
                                        @foreach (['true' => 'True', 'false' => 'False'] as $value => $label)
                                            <label for="answer-{{ $question->id }}-{{ $value }}"
                                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-transparent bg-white px-4 py-2.5 text-sm text-gray-700 ring-1 ring-gray-200 transition hover:ring-2 hover:ring-amber-300 has-[:checked]:ring-2 has-[:checked]:ring-amber-500"
                                                wire:key="true-false-{{ $question->id }}-{{ $value }}">
                                                <input id="answer-{{ $question->id }}-{{ $value }}" type="radio" value="{{ $value }}"
                                                    wire:model="answers.{{ $question->id }}"
                                                    class="size-4 text-amber-500 accent-amber-500" />
                                                {{ $label }}
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="space-y-2">
                                        @forelse ($question->options as $option)
                                            <label for="option-{{ $option->id }}"
                                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-transparent bg-white px-4 py-2.5 text-sm text-gray-700 ring-1 ring-gray-200 transition hover:ring-2 hover:ring-amber-300 has-[:checked]:ring-2 has-[:checked]:ring-amber-500"
                                                wire:key="option-{{ $option->id }}">
                                                <input id="option-{{ $option->id }}" type="radio" value="{{ $option->id }}"
                                                    wire:model="answers.{{ $question->id }}"
                                                    class="size-4 text-amber-500 accent-amber-500" />
                                                {{ $option->option_text }}
                                            </label>
                                        @empty
                                            <p class="text-sm text-red-600">This question has no answer options yet.</p>
                                        @endforelse
                                    </div>
                                @endif
                            </fieldset>
                        @endforeach
                    </div>

                    @error('submit')
                        <p class="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $message }}</p>
                    @enderror

                    <div class="mt-8 flex justify-end">
                        <button type="submit"
                            class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-600"
                            wire:loading.attr="disabled">
                            Submit answers
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
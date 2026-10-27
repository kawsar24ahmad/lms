<?php

namespace App\Models;

use Database\Factories\QuizAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['quiz_id', 'user_id', 'answers', 'score', 'status', 'submitted_at'])]
class QuizAttempt extends Model
{
    /** @use HasFactory<QuizAttemptFactory> */
    use HasFactory;

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_PASSED = 'passed';

    public const STATUS_FAILED = 'failed';

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * The quiz being attempted.
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * The student taking the quiz.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Determine whether the attempt has been submitted.
     */
    public function isSubmitted(): bool
    {
        return $this->status !== self::STATUS_IN_PROGRESS;
    }

    /**
     * Determine whether the attempt passed the quiz.
     */
    public function hasPassed(): bool
    {
        return $this->status === self::STATUS_PASSED;
    }

    /**
     * Calculate the percentage score for the given answers, weighting each
     * question by its points.
     */
    public function scoreFor(array $answers): int
    {
        $questions = $this->quiz->questions()->with('options')->get()->keyBy('id');
        $totalPoints = max($questions->sum('points'), 1);

        $earnedPoints = $questions
            ->filter(fn (Question $question) => array_key_exists($question->getKey(), $answers))
            ->filter(fn (Question $question) => $question->acceptsAnswer($answers[$question->getKey()]))
            ->sum('points');

        return min((int) round($earnedPoints * 100 / $totalPoints), 100);
    }

    /**
     * Submit the answers: score them, mark the attempt passed or failed by
     * the quiz pass score, and record the score on an issued certificate
     * whose final score is still missing.
     */
    public function submit(array $answers): void
    {
        $score = $this->scoreFor($answers);

        $this->fill([
            'answers' => $answers,
            'score' => $score,
            'status' => $score >= $this->quiz->pass_score
                ? self::STATUS_PASSED
                : self::STATUS_FAILED,
            'submitted_at' => now(),
        ])->save();

        if ($this->hasPassed()) {
            $this->recordFinalScore();
        }
    }

    /**
     * Reset the attempt so the student can take the quiz again.
     */
    public function retake(): void
    {
        $this->fill([
            'answers' => [],
            'score' => null,
            'status' => self::STATUS_IN_PROGRESS,
            'submitted_at' => null,
        ])->save();
    }

    /**
     * Store the quiz score on a previously issued certificate for the same
     * course, when the certificate has no final score yet.
     */
    protected function recordFinalScore(): void
    {
        Certificate::query()
            ->where('course_id', $this->quiz->course_id)
            ->where('user_id', $this->user_id)
            ->whereNull('final_score')
            ->update(['final_score' => $this->score]);
    }
}

<?php

namespace App\Models;

use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['quiz_id', 'question_text', 'type', 'points', 'correct_answer'])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    /**
     * The quiz the question belongs to.
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * The answer options of the question.
     */
    public function options(): HasMany
    {
        return $this->hasMany(Option::class);
    }

    /**
     * Determine whether the given answer is correct for this question.
     */
    public function acceptsAnswer(mixed $answer): bool
    {
        if ($this->type === 'open_answer') {
            return $this->matchesTextAnswer($answer);
        }

        if ($this->type === 'true_false' && filled($this->correct_answer)) {
            return $this->matchesTextAnswer($answer);
        }

        return $this->options->contains(
            fn (Option $option): bool => $option->is_correct && (int) $answer === $option->getKey(),
        );
    }

    /**
     * Compare a free-text answer with the stored correct answer.
     */
    private function matchesTextAnswer(mixed $answer): bool
    {
        if (! is_string($answer) || blank($this->correct_answer)) {
            return false;
        }

        return Str::lower(trim($answer)) === Str::lower(trim($this->correct_answer));
    }

    /**
     * Limit the query to questions of the given type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
}

<?php

namespace App\Models;

use Database\Factories\AssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['lesson_id', 'title', 'description', 'max_score', 'due_date'])]
class Assignment extends Model
{
    /** @use HasFactory<AssignmentFactory> */
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
        ];
    }

    /**
     * The lesson the assignment is attached to.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * The student submissions for the assignment.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Limit the query to assignments inside courses the student is
     * enrolled in.
     */
    public function scopeForStudent(Builder $query, User $student): Builder
    {
        return $query->whereHas(
            'lesson.section.course.enrollments',
            fn (Builder $query) => $query->where('student_id', $student->getKey()),
        );
    }

    /**
     * The student's submission for this assignment, if they made one.
     */
    public function submissionFor(User $student): ?Submission
    {
        return $this->submissions()
            ->where('user_id', $student->getKey())
            ->first();
    }

    /**
     * Whether the due date has already passed.
     */
    public function isPastDue(): bool
    {
        return $this->due_date !== null && $this->due_date->isPast();
    }
}

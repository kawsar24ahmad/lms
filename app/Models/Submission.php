<?php

namespace App\Models;

use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['assignment_id', 'user_id', 'content', 'file_path', 'score', 'feedback', 'status', 'submitted_at'])]
class Submission extends Model
{
    /** @use HasFactory<SubmissionFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_GRADED = 'graded';

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * The assignment the submission is for.
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /**
     * The student who submitted.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Limit the query to graded submissions.
     */
    public function scopeGraded(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_GRADED);
    }

    /**
     * Limit the query to submissions awaiting grading.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Whether an instructor has graded the submission.
     */
    public function isGraded(): bool
    {
        return $this->status === self::STATUS_GRADED;
    }
}

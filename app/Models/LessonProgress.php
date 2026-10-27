<?php

namespace App\Models;

use Database\Factories\LessonProgressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'lesson_id', 'seconds_watched', 'last_position', 'furthest_position', 'duration_seconds', 'is_completed', 'completed_at'])]
class LessonProgress extends Model
{
    /** @use HasFactory<LessonProgressFactory> */
    use HasFactory;

    /**
     * Percentage of the video a student must reach for the lesson to
     * complete automatically.
     */
    public const COMPLETION_THRESHOLD = 90;

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * The student tracking progress.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The lesson being tracked.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Limit the query to completed lessons.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('is_completed', true);
    }

    /**
     * Limit the query to progress records of the given user.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->getKey());
    }

    /**
     * The percentage of the video the student has reached, capped at 100.
     *
     * Completed records always report 100%, while records saved before the
     * video duration was known fall back to the lesson's duration in minutes.
     */
    public function percentage(): int
    {
        if ($this->is_completed) {
            return 100;
        }

        $duration = (int) $this->duration_seconds;

        if ($duration <= 0) {
            $duration = (int) (($this->lesson?->duration_minutes ?? 0) * 60);
        }

        if ($duration <= 0) {
            return 0;
        }

        $watched = max((int) $this->furthest_position, (int) $this->last_position);

        return (int) max(0, min(100, (int) round($watched / $duration * 100)));
    }

    /**
     * Store a video position reported by the player, keeping the furthest
     * point and total watch time monotonic so seeking backwards never loses
     * progress. Lessons auto-complete once the completion threshold is met.
     */
    public static function recordWatch(
        User $user,
        Lesson $lesson,
        int $lastPosition,
        int $furthestPosition,
        int $secondsWatched,
        int $durationSeconds,
    ): self {
        $progress = static::query()->firstOrNew([
            'user_id' => $user->getKey(),
            'lesson_id' => $lesson->getKey(),
        ]);

        $lastPosition = max(0, min($lastPosition, 86400));
        $furthestPosition = max(0, min($furthestPosition, 86400));
        $durationSeconds = max(0, min($durationSeconds, 86400));
        $secondsWatched = max(0, min($secondsWatched, 65535));

        $progress->last_position = $lastPosition;
        $progress->furthest_position = max(
            (int) ($progress->furthest_position ?? 0),
            $lastPosition,
            $furthestPosition,
        );
        $progress->duration_seconds = max(
            (int) ($progress->duration_seconds ?? 0),
            $durationSeconds,
        );
        $progress->seconds_watched = max(
            (int) ($progress->seconds_watched ?? 0),
            $secondsWatched,
        );
        $progress->setRelation('lesson', $lesson);
        $progress->save();

        if (! $progress->is_completed && $progress->percentage() >= self::COMPLETION_THRESHOLD) {
            return $lesson->completeFor($user);
        }

        return $progress;
    }
}

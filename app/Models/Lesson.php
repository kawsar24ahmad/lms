<?php

namespace App\Models;

use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['section_id', 'title', 'content', 'video_url', 'duration_minutes', 'position', 'is_preview', 'type'])]
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_preview' => 'boolean',
        ];
    }

    /**
     * The section the lesson belongs to.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * The assignments attached to the lesson.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    /**
     * The progress records for this lesson.
     */
    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /**
     * The progress record of a specific user watching this lesson.
     */
    public function progressForUser(User $user): ?LessonProgress
    {
        return $this->progress()->whereBelongsTo($user)->first();
    }

    /**
     * Determine whether the user has completed this lesson.
     */
    public function isCompletedFor(User $user): bool
    {
        return $this->progress()
            ->where('user_id', $user->getKey())
            ->where('is_completed', true)
            ->exists();
    }

    /**
     * The URL to use for embedding the lesson video: rewires YouTube and
     * Vimeo share links to their player endpoints, passes the rest through.
     */
    public function embedUrl(bool $enablePlayerApi = false): string
    {
        $url = $this->video_url;

        if ($url === null) {
            return '';
        }

        if (preg_match('#(?:youtube\.com/(?:watch\?.*v=|shorts/|embed/)|youtu\.be/)([\w-]+)#i', $url, $matches)) {
            $embedUrl = 'https://www.youtube.com/embed/'.$matches[1];

            if ($enablePlayerApi) {
                $embedUrl .= '?'.http_build_query([
                    'enablejsapi' => 1,
                    'playsinline' => 1,
                ]);
            }

            return $embedUrl;
        }

        if (preg_match('#vimeo\.com/(?:video/)?(\d+)#i', $url, $matches)) {
            return 'https://player.vimeo.com/video/'.$matches[1];
        }

        return $url;
    }

    /**
     * Whether the video URL points at a playable media file rather than a
     * page that must be framed with an iframe.
     */
    public function isDirectVideo(): bool
    {
        return (bool) preg_match('#\.(mp4|webm|ogg|ogv|m4v|mov)(?:\?.*)?$#i', $this->video_url ?? '');
    }

    /**
     * Mark the lesson as completed for the user, tracking watched seconds,
     * and complete the parent course plus issue its certificate when the
     * user has finished every lesson.
     */
    public function completeFor(User $user): LessonProgress
    {
        $progress = $this->progress()->firstOrCreate(
            ['user_id' => $user->getKey()],
            ['is_completed' => false],
        );

        $progress->fill([
            'is_completed' => true,
            'completed_at' => now(),
            'seconds_watched' => max(
                $progress->seconds_watched,
                ($this->duration_minutes ?? 0) * 60,
            ),
        ])->save();

        $this->maybeCompleteCourseFor($user);

        return $progress;
    }

    /**
     * Complete the user's enrollment in the parent course and issue the
     * certificate once every course lesson has been completed.
     */
    protected function maybeCompleteCourseFor(User $user): void
    {
        $course = $this->section()->first()->course;
        $course->loadMissing('sections.lessons');

        $enrollment = $course->enrollments()
            ->where('student_id', $user->getKey())
            ->first();

        if ($enrollment === null || $enrollment->status === 'completed') {
            return;
        }

        $allLessonsCompleted = $course->sections
            ->flatMap->lessons
            ->every(fn (Lesson $lesson) => $lesson->isCompletedFor($user));

        if ($allLessonsCompleted) {
            $enrollment->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $course->certificates()->firstOrCreate(
                ['user_id' => $user->getKey()],
                [
                    'certificate_number' => 'CERT-'.Str::uuid(),
                    'final_score' => null,
                    'issued_at' => now(),
                ],
            );
        }
    }

    public function getEmbedUrlAttribute(): ?string
    {
        $url = $this->video_url;

        if (! $url) {
            return null;
        }

        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $query = [];

        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);

        $videoId = null;

        if (in_array($host, [
            'youtube.com',
            'www.youtube.com',
            'm.youtube.com',
        ], true)) {
            if (
                str_starts_with($path, '/embed/') ||
                str_starts_with($path, '/shorts/')
            ) {
                $videoId = basename($path);
            } else {
                $videoId = $query['v'] ?? null;
            }
        } elseif (in_array($host, [
            'youtu.be',
            'www.youtu.be',
        ], true)) {
            $videoId = trim($path, '/');
        }

        if (! $videoId || ! preg_match('/^[a-zA-Z0-9_-]{11}$/', $videoId)) {
            return null;
        }

        return 'https://www.youtube-nocookie.com/embed/'.$videoId
            .'?'.http_build_query([
                'enablejsapi' => 1,
                'playsinline' => 1,
            ]);
    }

    public function resources()
    {
        return $this->hasMany(LessonResource::class)->orderBy('sort_order');
    }

    public function notes()
    {
        return $this->hasMany(LessonNote::class);
    }

    // বর্তমান ইউজারের এই লেসনের সব নোট পাওয়ার জন্য
    public function userNotes()
    {
        return $this->hasMany(LessonNote::class)->where('user_id', auth()->id())->latest();
    }
}

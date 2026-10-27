<?php

namespace App\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['category_id', 'user_id', 'title', 'slug', 'description', 'image_path', 'price', 'discount_type', 'discount_amount', 'intro_video_url', 'status', 'published_at'])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    public const DISCOUNT_TYPE_FIXED = 'fixed';

    public const DISCOUNT_TYPE_PERCENT = 'percent';

    /**
     * Bind courses to route parameters by slug.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'published_at' => 'datetime',
        ];
    }

    /**
     * The category the course belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * The instructor who created the course.
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The sections that make up the course curriculum.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    /**
     * The quizzes attached to the course.
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    /**
     * The students enrolled in the course.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * The reviews written for the course.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * The certificates issued for the course.
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * The order line items purchased for this course.
     *
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Whether the course currently sells at a reduced price.
     */
    public function isDiscounted(): bool
    {
        return $this->discount_type !== null
            && $this->discount_amount !== null
            && (float) $this->discount_amount > 0;
    }

    /**
     * The price students actually pay: the list price minus the discount,
     * floored at zero. Courses without a discount sell at list price.
     */
    public function effectivePrice(): float
    {
        $price = (float) $this->price;

        if (! $this->isDiscounted()) {
            return max(0.0, $price);
        }

        $effective = match ($this->discount_type) {
            self::DISCOUNT_TYPE_PERCENT => $price * (1 - ((float) $this->discount_amount / 100)),
            self::DISCOUNT_TYPE_FIXED => $price - (float) $this->discount_amount,
            default => $price,
        };

        return round(max(0.0, $effective), 2);
    }

    /**
     * The URL for embedding the intro video: rewires YouTube, Vimeo and direct
     * media files the same way lesson videos are handled. Null when unset.
     */
    public function introVideoEmbedUrl(): ?string
    {
        if (blank($this->intro_video_url)) {
            return null;
        }

        $url = $this->intro_video_url;

        if (preg_match('#(?:youtube\.com/(?:watch\?.*v=|shorts/|embed/)|youtu\.be/)([\w-]+)#i', $url, $matches)) {
            return 'https://www.youtube.com/embed/'.$matches[1];
        }

        if (preg_match('#vimeo\.com/(?:video/)?(\d+)#i', $url, $matches)) {
            return 'https://player.vimeo.com/video/'.$matches[1];
        }

        if ($this->hasDirectIntroVideo()) {
            return $url;
        }

        return null;
    }

    /**
     * Whether the intro video URL points at a playable media file.
     */
    public function hasDirectIntroVideo(): bool
    {
        return (bool) preg_match('#\.(mp4|webm|ogg|ogv|m4v|mov)(?:\?.*)?$#i', $this->intro_video_url ?? '');
    }

    /**
     * Limit the query to published courses.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('published_at');
    }

    /**
     * Limit the query to draft courses.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Enroll the user in the course, or return their existing enrollment.
     * Pricing and payment processing belong to orders; this only grants
     * course access, so it is intended for free courses (an order is the
     * safe path for anything unpaid).
     */
    public function enroll(User $user): Enrollment
    {
        return $this->enrollments()->firstOrCreate(
            ['student_id' => $user->getKey()],
            [
                'status' => 'active',
                'enrolled_at' => now(),
            ],
        );
    }

    /**
     * Determine whether the user has ever enrolled in the course.
     */
    public function hasEnrolled(User $user): bool
    {
        return $this->enrollments()
            ->where('student_id', $user->getKey())
            ->exists();
    }

    /**
     * The first lesson of the course the user has not completed yet.
     */
    public function resumeLessonFor(User $user): ?Lesson
    {
        return $this->loadMissing('sections.lessons')->sections
            ->flatMap->lessons
            ->reject(fn (Lesson $lesson) => $lesson->isCompletedFor($user))
            ->first();
    }

    /**
     * How many of the course lessons the user has completed.
     */
    public function completedLessonsCountFor(User $user): int
    {
        return LessonProgress::query()
            ->where('user_id', $user->getKey())
            ->completed()
            ->whereIn('lesson_id', $this->loadMissing('sections.lessons')->sections->flatMap->lessons->pluck('id')->all())
            ->count();
    }
}

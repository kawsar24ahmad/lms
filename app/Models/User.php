<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use HasRoles;

    /**
     * Shield (Spatie) role names. Students are simply users without any
     * role, so there is no dedicated constant for them.
     */
    public const ROLE_INSTRUCTOR = 'instructor';

    public const ROLE_ADMIN = 'super_admin';

    /**
     * The courses this user teaches as an instructor.
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'user_id');
    }

    /**
     * The user's orders.
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'student_id');
    }

    /**
     * The user's course enrollments.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'student_id');
    }

    /**
     * The courses the user is enrolled in.
     */
    public function enrolledCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'enrollments', 'student_id')
            ->withPivot('status', 'completed_at')
            ->withTimestamps();
    }

    /**
     * The user's lesson progress records.
     */
    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /**
     * The assignment submissions made by the user.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * The course reviews written by the user.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * The certificates the user earned.
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Determine whether the user can access the Filament admin panel:
     * granted Shield roles only, never plain students.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isInstructor() || $this->isAdmin();
    }

    /**
     * Whether the user teaches.
     */
    public function isInstructor(): bool
    {
        return $this->isAdmin() || $this->hasRole(self::ROLE_INSTRUCTOR);
    }

    /**
     * Whether the user holds the Shield super administrator role.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /**
     * Whether the user is a plain student without any Shield role.
     */
    public function isStudent(): bool
    {
        return $this->roles()->doesntExist();
    }

    /**
     * Whether the user manages the given course (admin or its instructor).
     */
    public function managesCourse(Course $course): bool
    {
        return $this->isAdmin() || ($this->isInstructor() && $course->user_id === $this->getKey());
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    public function instructor(): HasOne
    {
        return $this->hasOne(Instructor::class);
    }
}

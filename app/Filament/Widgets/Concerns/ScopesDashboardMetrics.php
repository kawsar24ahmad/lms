<?php

namespace App\Filament\Widgets\Concerns;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Submission;
use App\Models\User;
use App\Policies\ResourcePolicy;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared dashboard query scoping and aggregation helpers.
 *
 * Admins see platform-wide metrics. Instructors only see the courses they
 * own, and the records attached to those courses, matching the ownership
 * rules used by the Filament resources.
 */
trait ScopesDashboardMetrics
{
    protected function scopedCourses(): Builder
    {
        return ResourcePolicy::scopeToOwner(Course::query(), auth()->user());
    }

    protected function scopedEnrollments(): Builder
    {
        return ResourcePolicy::scopeToOwner(Enrollment::query(), auth()->user());
    }

    protected function scopedLessonProgress(): Builder
    {
        return ResourcePolicy::scopeToOwner(LessonProgress::query(), auth()->user());
    }

    protected function scopedSubmissions(): Builder
    {
        return ResourcePolicy::scopeToOwner(Submission::query(), auth()->user());
    }

    protected function scopedCertificates(): Builder
    {
        return ResourcePolicy::scopeToOwner(Certificate::query(), auth()->user());
    }

    /**
     * Students are users without a Shield role. Instructors only count the
     * distinct students enrolled in one of their courses.
     */
    protected function scopedStudents(): Builder
    {
        $query = User::query()->whereDoesntHave('roles');

        if (auth()->user()?->isAdmin()) {
            return $query;
        }

        return $query->whereIn('id', $this->scopedEnrollments()->select('student_id'));
    }

    /**
     * Admins see every instructor; an instructor only counts themselves.
     */
    protected function instructorCount(): int
    {
        $user = auth()->user();

        if ($user === null) {
            return 0;
        }

        if ($user->isAdmin()) {
            return User::role(User::ROLE_INSTRUCTOR)->count();
        }

        return $user->hasRole(User::ROLE_INSTRUCTOR) ? 1 : 0;
    }

    /**
     * Count the lessons inside every course in scope.
     */
    protected function scopedLessonCount(): int
    {
        $courseIds = $this->scopedCourses()->pluck('id')->all();

        if ($courseIds === []) {
            return 0;
        }

        return Lesson::query()
            ->whereHas('section', fn (Builder $query): Builder => $query->whereIn('course_id', $courseIds))
            ->count();
    }

    /**
     * The average video progress across the records in scope, from 0 to 100.
     */
    protected function averageWatchPercentage(): int
    {
        $result = $this->scopedLessonProgress()
            ->selectRaw('AVG(CASE WHEN is_completed = 1 THEN 1.0 WHEN duration_seconds > 0 THEN furthest_position * 1.0 / duration_seconds ELSE 0 END) as average_progress')
            ->first();

        $average = (float) ($result?->average_progress ?? 0);

        return (int) max(0, min(100, round($average * 100)));
    }

    /**
     * Count records created on each of the last seven days.
     *
     * @return array<int, int>
     */
    protected function lastSevenDays(Builder $query, string $column): array
    {
        $start = now()->subDays(6)->startOfDay();

        $counts = (clone $query)
            ->where($column, '>=', $start)
            ->get([$column])
            ->filter(fn ($record): bool => $record->{$column} !== null)
            ->countBy(fn ($record): string => $record->{$column}->format('Y-m-d'));

        return collect(range(0, 6))
            ->map(fn (int $offset): int => (int) ($counts[now()->subDays(6 - $offset)->format('Y-m-d')] ?? 0))
            ->all();
    }

    /**
     * Count records created in each month from the given start date.
     *
     * @return array<string, int>
     */
    protected function monthlyCounts(Builder $query, string $column, CarbonInterface $start): array
    {
        return (clone $query)
            ->where($column, '>=', $start)
            ->get([$column])
            ->filter(fn ($record): bool => $record->{$column} !== null)
            ->countBy(fn ($record): string => $record->{$column}->format('Y-m'))
            ->all();
    }
}

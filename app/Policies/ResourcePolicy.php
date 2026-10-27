<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Option;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Review;
use App\Models\Section;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base policy backing every Filament resource.
 *
 * Admins may manage everything. Instructors may manage only the records
 * attached to a course they own. Students never see any of it.
 */
class ResourcePolicy
{
    /**
     * The models this policy governs. Registered in AppServiceProvider.
     *
     * @return list<class-string<Model>>
     */
    public static function models(): array
    {
        return [
            Category::class,
            User::class,
            Course::class,
            Section::class,
            Lesson::class,
            Quiz::class,
            Question::class,
            Option::class,
            Enrollment::class,
            LessonProgress::class,
            Assignment::class,
            Submission::class,
            Review::class,
            Certificate::class,
            Order::class,
            Payment::class,
        ];
    }

    /**
     * Restrict a resource query to records the user may manage.
     */
    public static function scopeToOwner(Builder $query, ?User $user): Builder
    {
        if ($user === null || $user->isAdmin()) {
            return $query;
        }

        $ownedCourseIds = $user->courses()->pluck('id')->all();

        if ($ownedCourseIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $query) use ($ownedCourseIds) {
            $belongsViaColumn = in_array($query->getModel()::class, [
                Section::class,
                Quiz::class,
                Enrollment::class,
                Review::class,
                Certificate::class,
            ], true);

            if ($query->getModel()::class === Course::class) {
                $query->whereIn('id', $ownedCourseIds);
            } elseif ($belongsViaColumn) {
                $query->whereIn('course_id', $ownedCourseIds);
            } else {
                $query->whereHas(self::ownerPath($query->getModel()::class), function (Builder $query) use ($ownedCourseIds) {
                    $query->whereIn('course_id', $ownedCourseIds);
                });
            }
        });
    }

    private static function ownerPath(string $modelClass): string
    {
        return (string) match ($modelClass) {
            Assignment::class, LessonProgress::class => 'lesson.section',
            Lesson::class => 'section',
            Question::class => 'quiz',
            Option::class => 'question.quiz',
            Submission::class => 'assignment.lesson.section',
            default => 'course',
        };
    }

    /**
     * The course a record belongs to, so ownership can be checked.
     */
    protected function courseFor(Model $model): ?Course
    {
        return match (true) {
            $model instanceof Course => $model,
            $model instanceof Section => $model->course,
            $model instanceof Lesson => $model->section?->course,
            $model instanceof Quiz => $model->course,
            $model instanceof Question => $model->quiz?->course,
            $model instanceof Option => $model->question?->quiz?->course,
            $model instanceof Enrollment => $model->course,
            $model instanceof LessonProgress => $model->lesson?->section?->course,
            $model instanceof Assignment => $model->lesson?->section?->course,
            $model instanceof Submission => $model->assignment?->lesson?->section?->course,
            $model instanceof Review => $model->course,
            $model instanceof Certificate => $model->course,
        };
    }

    /**
     * The Shield permission name backing a policy ability, e.g. "ViewAny:Lesson".
     */
    protected function shieldPermission(string $ability, string $modelClass): string
    {
        return $ability.':'.class_basename($modelClass);
    }

    /**
     * Whether the user holds the Shield permission backing the ability.
     * Absent permissions count as denied instead of erroring, so roles
     * created before a resource existed simply lack access.
     */
    protected function hasShieldPermission(?User $user, string $ability, string $modelClass): bool
    {
        return $user !== null
            && $user->checkPermissionTo($this->shieldPermission($ability, $modelClass));
    }

    /**
     * Whether the user may manage the given record.
     */
    protected function manages(?User $user, Model $model, string $ability): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isInstructor()
            || ! $this->hasShieldPermission($user, $ability, $model::class)) {
            return false;
        }

        $ownedCourseIds = $user->courses()->pluck('id')->all();

        if ($ownedCourseIds === []) {
            return false;
        }

        return $this->ownedByCourses($model, $ownedCourseIds);
    }

    /**
     * Whether the record is attached to one of the given course ids.
     *
     * @param  list<int>  $ownedCourseIds
     */
    protected function ownedByCourses(Model $model, array $ownedCourseIds): bool
    {
        $course = $this->courseFor($model);

        return $course !== null && in_array($course->getKey(), $ownedCourseIds, true);
    }
}

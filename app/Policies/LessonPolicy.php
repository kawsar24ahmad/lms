<?php

namespace App\Policies;

use App\Models\Lesson;

/**
 * Shield-driven policy for Lesson records.
 */
class LessonPolicy extends OwnedResourcePolicy
{
    /**
     * The model class this policy governs.
     */
    protected static string $resourceModel = Lesson::class;
}

<?php

namespace App\Policies;

use App\Models\LessonProgress;

/**
 * Shield-driven policy for LessonProgress records.
 */
class LessonProgressPolicy extends OwnedResourcePolicy
{
    /**
     * The model class this policy governs.
     */
    protected static string $resourceModel = LessonProgress::class;
}

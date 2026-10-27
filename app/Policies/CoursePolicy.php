<?php

namespace App\Policies;

use App\Models\Course;

/**
 * Shield-driven policy for Course records.
 */
class CoursePolicy extends OwnedResourcePolicy
{
    /**
     * The model class this policy governs.
     */
    protected static string $resourceModel = Course::class;
}

<?php

namespace App\Policies;

use App\Models\Enrollment;

/**
 * Shield-driven policy for Enrollment records.
 */
class EnrollmentPolicy extends OwnedResourcePolicy
{
    /**
     * The model class this policy governs.
     */
    protected static string $resourceModel = Enrollment::class;
}

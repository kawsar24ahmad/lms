<?php

namespace App\Policies;

use App\Models\Assignment;

/**
 * Shield-driven policy for Assignment records.
 */
class AssignmentPolicy extends OwnedResourcePolicy
{
    /**
     * The model class this policy governs.
     */
    protected static string $resourceModel = Assignment::class;
}

<?php

namespace App\Policies;

use App\Models\Submission;

/**
 * Shield-driven policy for Submission records.
 */
class SubmissionPolicy extends OwnedResourcePolicy
{
    /**
     * The model class this policy governs.
     */
    protected static string $resourceModel = Submission::class;
}

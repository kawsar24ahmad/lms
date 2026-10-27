<?php

namespace App\Policies;

use App\Models\Review;

/**
 * Shield-driven policy for Review records.
 */
class ReviewPolicy extends OwnedResourcePolicy
{
    /**
     * The model class this policy governs.
     */
    protected static string $resourceModel = Review::class;
}

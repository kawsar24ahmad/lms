<?php

namespace App\Policies;

use App\Models\Option;

/**
 * Shield-driven policy for Option records.
 */
class OptionPolicy extends OwnedResourcePolicy
{
    /**
     * The model class this policy governs.
     */
    protected static string $resourceModel = Option::class;
}

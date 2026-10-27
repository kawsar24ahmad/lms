<?php

namespace App\Policies;

use App\Models\Section;

/**
 * Shield-driven policy for Section records.
 */
class SectionPolicy extends OwnedResourcePolicy
{
    /**
     * The model class this policy governs.
     */
    protected static string $resourceModel = Section::class;
}

<?php

namespace App\Policies;

use App\Models\Quiz;

/**
 * Shield-driven policy for Quiz records.
 */
class QuizPolicy extends OwnedResourcePolicy
{
    /**
     * The model class this policy governs.
     */
    protected static string $resourceModel = Quiz::class;
}

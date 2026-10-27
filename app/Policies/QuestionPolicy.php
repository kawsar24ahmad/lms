<?php

namespace App\Policies;

use App\Models\Question;

/**
 * Shield-driven policy for Question records.
 */
class QuestionPolicy extends OwnedResourcePolicy
{
    /**
     * The model class this policy governs.
     */
    protected static string $resourceModel = Question::class;
}

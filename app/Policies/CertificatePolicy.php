<?php

namespace App\Policies;

use App\Models\Certificate;

/**
 * Shield-driven policy for Certificate records.
 */
class CertificatePolicy extends OwnedResourcePolicy
{
    /**
     * The model class this policy governs.
     */
    protected static string $resourceModel = Certificate::class;
}

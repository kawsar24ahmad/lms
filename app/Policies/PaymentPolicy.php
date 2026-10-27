<?php

namespace App\Policies;

use App\Models\Payment;

/**
 * Shield-driven policy for Payment records: finance data is administrator
 * only, mirroring categories and users.
 */
class PaymentPolicy extends AdminOnlyPolicy
{
    /**
     * The model class this policy governs.
     */
    protected static string $resourceModel = Payment::class;
}

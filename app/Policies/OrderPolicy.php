<?php

namespace App\Policies;

use App\Models\Order;

/**
 * Shield-driven policy for Order records: finance data is administrator
 * only, mirroring categories and users.
 */
class OrderPolicy extends AdminOnlyPolicy
{
    /**
     * The model class this policy governs.
     */
    protected static string $resourceModel = Order::class;
}

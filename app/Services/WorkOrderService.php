<?php

namespace App\Services;

use App\Models\WorkOrder;

/**
 * Encapsulates work order business logic.
 */
class WorkOrderService
{
    /**
     * Return the next sequential work order number.
     */
    public function nextNumber(): int
    {
        return (int) (WorkOrder::max('number') ?? 0) + 1;
    }
}

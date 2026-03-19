<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * JSON shape for a SupplierOrder, including supplier, customerOrder, and lines.
 */
class SupplierOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'order_date' => $this->order_date?->format('Y-m-d'),
            'total_amount' => (float) $this->total_amount,
            'status' => $this->status,
            'notes' => $this->notes,
            'supplier_id' => $this->supplier_id,
            'customer_order_id' => $this->customer_order_id,
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id' => $this->supplier->id,
                'name' => $this->supplier->name,
            ]),
            'customer_order' => $this->whenLoaded('customerOrder', fn () => $this->customer_order_id ? [
                'id' => $this->customerOrder->id,
                'number' => $this->customerOrder->number,
            ] : null),
            'lines' => SupplierOrderLineResource::collection(
                $this->whenLoaded('lines')
            ),
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }
}

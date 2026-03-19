<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * JSON shape for a CustomerOrder, including eager-loaded client, lines, and supplier orders.
 */
class CustomerOrderResource extends JsonResource
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
            'client_id' => $this->client_id,
            'proposal_id' => $this->proposal_id,
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client->id,
                'name' => $this->client->name,
            ]),
            'lines' => CustomerOrderLineResource::collection(
                $this->whenLoaded('lines')
            ),
            'has_supplier_orders' => $this->whenLoaded(
                'supplierOrders',
                fn () => $this->supplierOrders->isNotEmpty(),
                false
            ),
            'supplier_orders_count' => $this->whenLoaded(
                'supplierOrders',
                fn () => $this->supplierOrders->count(),
                0
            ),
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }
}

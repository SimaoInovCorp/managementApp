<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'invoice_date' => $this->invoice_date?->format('Y-m-d'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'supplier_id' => $this->supplier_id,
            'supplier_order_id' => $this->supplier_order_id,
            'total_amount' => (float) $this->total_amount,
            'document_path' => $this->document_path,
            'payment_proof_path' => $this->payment_proof_path,
            'status' => $this->status,
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id' => $this->supplier->id,
                'name' => $this->supplier->name,
            ]),
            'supplier_order' => $this->whenLoaded('supplierOrder', fn () => $this->supplier_order_id ? [
                'id' => $this->supplierOrder->id,
                'number' => $this->supplierOrder->number,
            ] : null
            ),
            'created_at' => $this->created_at,
        ];
    }
}

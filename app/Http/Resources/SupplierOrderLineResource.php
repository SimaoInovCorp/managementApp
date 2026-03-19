<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * JSON shape for a single SupplierOrderLine.
 */
class SupplierOrderLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'article_id' => $this->article_id,
            'article' => $this->whenLoaded('article', fn () => [
                'id' => $this->article->id,
                'reference' => $this->article->reference,
                'name' => $this->article->name,
                'price' => (float) $this->article->price,
                'vat_id' => $this->article->vat_id,
                'vat_rate' => $this->article->vatRate
                    ? (float) $this->article->vatRate->rate
                    : 0,
            ]),
            'quantity' => (float) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'sort_order' => $this->sort_order,
        ];
    }
}

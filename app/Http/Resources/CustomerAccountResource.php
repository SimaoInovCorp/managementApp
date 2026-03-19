<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entity_id' => $this->entity_id,
            'entity' => $this->whenLoaded('entity', fn () => [
                'id' => $this->entity->id,
                'name' => $this->entity->name,
            ]),
            'description' => $this->description,
            'debit' => (float) $this->debit,
            'credit' => (float) $this->credit,
            'date' => $this->date?->format('Y-m-d'),
            'running_balance' => $this->running_balance ?? null, // appended by controller
            'created_at' => $this->created_at,
        ];
    }
}

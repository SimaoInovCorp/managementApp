<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * JSON shape for a Proposal, including eager-loaded client and lines.
 */
class ProposalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'proposal_date' => $this->proposal_date?->format('Y-m-d'),
            'validity_date' => $this->validity_date?->format('Y-m-d'),
            'total_amount' => (float) $this->total_amount,
            'status' => $this->status,
            'notes' => $this->notes,
            'client_id' => $this->client_id,
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client->id,
                'name' => $this->client->name,
            ]),
            'lines' => ProposalLineResource::collection(
                $this->whenLoaded('lines')
            ),
            'has_order' => $this->whenLoaded(
                'customerOrder',
                fn () => $this->customerOrder !== null,
                false
            ),
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }
}

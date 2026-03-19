<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Consistent JSON shape for Contact payloads.
 * Encrypted fields are automatically decrypted by the model casts.
 */
class ContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'entity_id' => $this->entity_id,
            'entity' => $this->whenLoaded('entity', fn () => [
                'id' => $this->entity->id,
                'name' => $this->entity->name,
                'type' => $this->entity->type,
            ]),
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'role_id' => $this->role_id,
            'role' => $this->whenLoaded('role', fn () => [
                'id' => $this->role->id,
                'name' => $this->role->name,
            ]),
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'gdpr_consent' => $this->gdpr_consent,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }
}

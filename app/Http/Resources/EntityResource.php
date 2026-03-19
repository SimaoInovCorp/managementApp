<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Consistent JSON shape for Entity payloads sent to the frontend.
 * Encrypted fields are automatically decrypted by the model casts.
 */
class EntityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'number' => $this->number,
            'nif' => $this->nif,
            'name' => $this->name,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'locality' => $this->locality,
            'country_id' => $this->country_id,
            'country' => $this->whenLoaded('country', fn () => [
                'id' => $this->country->id,
                'name' => $this->country->name,
                'code' => $this->country->code,
            ]),
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'website' => $this->website,
            'email' => $this->email,
            'gdpr_consent' => $this->gdpr_consent,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }
}

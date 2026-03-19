<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'vat_id' => $this->vat_id,
            'vat' => $this->whenLoaded('vatRate', fn () => [
                'id' => $this->vatRate->id,
                'name' => $this->vatRate->name,
                'rate' => (float) $this->vatRate->rate,
            ]),
            'photo_path' => $this->photo_path,
            'photo_url' => $this->photo_path
                ? route('file.private', ['path' => $this->photo_path])
                : null,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }
}

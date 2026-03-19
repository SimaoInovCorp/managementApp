<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Build ISO 8601 start string for FullCalendar
        $start = $this->date?->format('Y-m-d');
        if ($this->time) {
            $start .= 'T'.$this->time;
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'date' => $this->date?->format('Y-m-d'),
            'time' => $this->time,
            'duration_minutes' => $this->duration_minutes,
            'user_id' => $this->user_id,
            'entity_id' => $this->entity_id,
            'type_id' => $this->type_id,
            'action_id' => $this->action_id,
            'description' => $this->description,
            'shared_with' => $this->shared_with ?? [],
            'knowledge' => $this->knowledge,
            'status' => $this->status,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'entity' => $this->whenLoaded('entity', fn () => $this->entity_id ? [
                'id' => $this->entity->id,
                'name' => $this->entity->name,
            ] : null
            ),
            'type' => $this->whenLoaded('type', fn () => $this->type_id ? ['id' => $this->type->id, 'name' => $this->type->name] : null
            ),
            'action' => $this->whenLoaded('action', fn () => $this->action_id ? ['id' => $this->action->id, 'name' => $this->action->name] : null
            ),
            'created_at' => $this->created_at,
            // FullCalendar-compatible fields
            'start' => $start,
            'extendedProps' => [
                'description' => $this->description,
                'status' => $this->status,
                'entity' => $this->whenLoaded('entity', fn () => $this->entity_id ? $this->entity->name : null
                ),
            ],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'time' => ['nullable', 'date_format:H:i'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'entity_id' => ['nullable', 'integer', 'exists:entities,id'],
            'type_id' => ['nullable', 'integer', 'exists:calendar_types,id'],
            'action_id' => ['nullable', 'integer', 'exists:calendar_actions,id'],
            'description' => ['nullable', 'string'],
            'shared_with' => ['nullable', 'array'],
            'shared_with.*' => ['integer', 'exists:users,id'],
            'knowledge' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,confirmed,cancelled'],
        ];
    }
}

<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCalendarActionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $actionId = $this->route('calendarAction')?->id;

        return [
            'name' => ['required', 'string', 'max:100', 'unique:calendar_actions,name,'.$actionId],
        ];
    }
}

<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCalendarTypeRequest extends FormRequest
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
        $typeId = $this->route('calendarType')?->id;

        return [
            'name' => ['required', 'string', 'max:100', 'unique:calendar_types,name,'.$typeId],
        ];
    }
}

<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContactRoleRequest extends FormRequest
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
        $roleId = $this->route('contactRole')?->id;

        return [
            'name' => ['required', 'string', 'max:100', 'unique:contact_roles,name,'.$roleId],
        ];
    }
}

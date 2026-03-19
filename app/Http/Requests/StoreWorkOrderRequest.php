<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation rules for creating a new work order.
 */
class StoreWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'client_id' => ['required', 'integer', 'exists:entities,id'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'closed'])],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation rules for creating a new customer order.
 */
class StoreCustomerOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_date' => ['required', 'date'],
            'client_id' => ['required', 'integer', 'exists:entities,id'],
            'proposal_id' => ['nullable', 'integer', 'exists:proposals,id'],
            'status' => ['required', Rule::in(['draft', 'closed'])],
            'notes' => ['nullable', 'string'],

            // Line items — at least one line required
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.article_id' => ['required', 'integer', 'exists:articles,id'],
            'lines.*.supplier_id' => ['nullable', 'integer', 'exists:entities,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.cost_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'lines.required' => 'At least one line item is required.',
            'lines.min' => 'At least one line item is required.',
            'lines.*.article_id.required' => 'Each line must have an article.',
            'lines.*.quantity.min' => 'Quantity must be greater than zero.',
        ];
    }
}

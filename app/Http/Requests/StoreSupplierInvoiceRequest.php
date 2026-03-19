<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date'],
            'supplier_id' => ['required', 'integer', 'exists:entities,id'],
            'supplier_order_id' => ['nullable', 'integer', 'exists:supplier_orders,id'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:pending,paid'],
        ];
    }
}

<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVatRateRequest extends FormRequest
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
        $vatRateId = $this->route('vatRate')?->id;

        return [
            'name' => ['required', 'string', 'max:100', 'unique:vat_rates,name,'.$vatRateId],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}

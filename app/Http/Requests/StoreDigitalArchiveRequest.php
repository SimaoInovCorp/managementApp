<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates an incoming digital archive file upload.
 *
 * Accepted types: PDF, Word, Excel, images, ZIP archives.
 * Max size: 50 MB (51200 KB).
 */
class StoreDigitalArchiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:51200',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,zip,rar'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'entity_id' => ['nullable', 'integer', 'exists:entities,id'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

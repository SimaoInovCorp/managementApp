<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var \App\Models\Article $article */
        $article = $this->route('article');

        return [
            'reference' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::unique('articles', 'reference')->ignore($article->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'vat_id' => ['required', 'integer', 'exists:vat_rates,id'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', \Illuminate\Validation\Rule::in(['active', 'inactive'])],
        ];
    }
}

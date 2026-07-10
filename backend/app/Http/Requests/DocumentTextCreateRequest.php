<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentTextCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sourceLabel' => ['required', 'string', 'max:255'],
            'text' => ['required', 'string', 'max:'.max(1, (int) config('rag.text_ingestion_max_input_chars', 1024 * 1024))],
            'tags' => ['nullable', 'array', 'max:20'],
            'tags.*' => ['string', 'max:50'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sourceLabel' => trim((string) $this->input('sourceLabel', '')),
            'text' => trim((string) $this->input('text', '')),
        ]);
    }
}

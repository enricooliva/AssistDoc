<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'filename' => ['required', 'string', 'max:255'],
            'mediaType' => ['nullable', 'string', 'max:128'],
            'sizeBytes' => ['nullable', 'integer', 'min:1'],
        ];
    }
}


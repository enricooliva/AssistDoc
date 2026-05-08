<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuditEventIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actor' => ['nullable', 'string'],
            'eventType' => ['nullable', 'string'],
            'outcome' => ['nullable', 'string', 'in:success,failure,denied'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ];
    }
}


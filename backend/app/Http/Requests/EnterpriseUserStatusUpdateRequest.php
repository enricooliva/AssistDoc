<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnterpriseUserStatusUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['active', 'suspended', 'locked', 'deactivated'])],
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnterpriseUserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email'],
            'tenant_id' => ['required', 'string'],
            'role' => ['required', Rule::in(['tenant-admin', 'operator', 'viewer'])],
            'access_methods' => ['required', 'array', 'min:1'],
            'access_methods.*' => ['string', Rule::in(['company_account', 'password'])],
            'status' => ['required', Rule::in(['provisioned', 'active', 'suspended', 'locked', 'deactivated'])],
            'mfa_policy' => ['sometimes', Rule::in(['required', 'optional', 'inherited'])],
            'password' => ['nullable', 'string', 'min:12'],
        ];
    }
}

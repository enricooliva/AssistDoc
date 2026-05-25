<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnterpriseUserUpdateRequest extends FormRequest
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
            'tenant_id' => ['required', 'string', Rule::exists('tenants', 'id')],
            'role' => ['required', Rule::in(['super-admin', 'tenant-admin', 'operator', 'viewer'])],
            'status' => ['required', Rule::in(['provisioned', 'active', 'suspended', 'locked', 'deactivated', 'reset_pending'])],
            'access_methods' => ['required', 'array', 'min:1'],
            'access_methods.*' => ['string', Rule::in(['company_account', 'password'])],
            'mfa_policy' => ['required', Rule::in(['required', 'optional', 'inherited'])],
            'password' => ['nullable', 'string', 'min:12'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantUserProvisionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'tenant_id' => $this->route('tenantId'),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'string'],
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(['tenant-admin', 'operator', 'viewer'])],
            'access_methods' => ['required', 'array', 'min:1'],
            'access_methods.*' => ['string', Rule::in(['company_account', 'password'])],
            'status' => ['required', Rule::in(['provisioned', 'active', 'suspended', 'locked', 'deactivated'])],
            'mfa_policy' => ['sometimes', Rule::in(['required', 'optional', 'inherited'])],
            'password' => ['nullable', 'string', 'min:12'],
        ];
    }
}

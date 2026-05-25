<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class TenantProvisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('tenant_slug')) {
            $this->merge([
                'tenant_slug' => Str::slug((string) $this->input('tenant_slug')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'tenant_name' => ['required', 'string', 'max:120'],
            'tenant_slug' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('tenants', 'slug')],
            'admin_full_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'admin_role' => ['sometimes', Rule::in(['tenant-admin'])],
            'admin_access_methods' => ['required', 'array', 'min:1'],
            'admin_access_methods.*' => ['string', Rule::in(['company_account', 'password'])],
            'admin_status' => ['required', Rule::in(['provisioned', 'active', 'suspended', 'locked', 'deactivated'])],
            'admin_mfa_policy' => ['sometimes', Rule::in(['required', 'optional', 'inherited'])],
            'admin_password' => ['nullable', 'string', 'min:12'],
        ];
    }
}

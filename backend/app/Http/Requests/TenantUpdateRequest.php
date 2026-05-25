<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class TenantUpdateRequest extends FormRequest
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
        $tenantId = $this->route('tenantId');

        return [
            'tenant_name' => ['required', 'string', 'max:120'],
            'tenant_slug' => [
                'required',
                'string',
                'max:120',
                'alpha_dash',
                Rule::unique('tenants', 'slug')->ignore($tenantId),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}

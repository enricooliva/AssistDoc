<?php

namespace App\Services\Auth;

class TenantContextService
{
    public function fromUser(array $user): array
    {
        return [
            'id' => (string) $user['tenant_id'],
            'name' => $user['tenant_name'],
            'slug' => $user['tenant_slug'],
        ];
    }
}

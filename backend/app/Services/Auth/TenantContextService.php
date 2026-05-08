<?php

namespace App\Services\Auth;

class TenantContextService
{
    public function fromUser(array $user): array
    {
        return [
            'id' => $user['tenant_id'],
            'name' => 'AssistDoc Demo',
            'slug' => 'assistdoc-demo',
        ];
    }
}


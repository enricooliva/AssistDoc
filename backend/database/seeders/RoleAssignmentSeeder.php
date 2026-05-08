<?php

namespace Database\Seeders;

use App\Models\RoleAssignment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->first();
        $admin = User::query()->where('email', 'admin@assistdoc.local')->first();
        $viewer = User::query()->where('email', 'viewer@assistdoc.local')->first();

        if (! $tenant || ! $admin || ! $viewer) {
            return;
        }

        RoleAssignment::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'user_id' => $admin->id],
            ['role' => 'super-admin', 'assigned_by_user_id' => $admin->id]
        );

        RoleAssignment::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'user_id' => $viewer->id],
            ['role' => 'viewer', 'assigned_by_user_id' => $admin->id]
        );
    }
}


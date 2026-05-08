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
        $operator = User::query()->where('email', 'operator@assistdoc.local')->first();
        $viewer = User::query()->where('email', 'viewer@assistdoc.local')->first();
        $tenantB = Tenant::query()->where('slug', 'tenant-b')->first();
        $viewerB = User::query()->where('email', 'viewer-b@assistdoc.local')->first();

        if (! $tenant || ! $admin || ! $operator || ! $viewer) {
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

        RoleAssignment::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'user_id' => $operator->id],
            ['role' => 'operator', 'assigned_by_user_id' => $admin->id]
        );

        if ($tenantB && $viewerB) {
            RoleAssignment::query()->updateOrCreate(
                ['tenant_id' => $tenantB->id, 'user_id' => $viewerB->id],
                ['role' => 'viewer', 'assigned_by_user_id' => $viewerB->id]
            );
        }
    }
}

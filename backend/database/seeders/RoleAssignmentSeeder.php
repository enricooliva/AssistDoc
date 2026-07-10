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
        $tenantAdmin = User::query()->where('email', 'tenant-admin@assistdoc.local')->first();
        $operator = User::query()->where('email', 'operator@assistdoc.local')->first();
        $viewer = User::query()->where('email', 'viewer@assistdoc.local')->first();
        $tenantB = Tenant::query()->where('slug', 'tenant-b')->first();
        $viewerB = User::query()->where('email', 'viewer-b@assistdoc.local')->first();
        $tenantC = Tenant::query()->where('slug', 'tenant-c')->first();
        $viewerC = User::query()->where('email', 'viewer-c@assistdoc.local')->first();

        if (! $tenant || ! $admin || ! $tenantAdmin || ! $operator || ! $viewer) {
            return;
        }

        RoleAssignment::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'user_id' => $admin->id],
            ['role' => 'super-admin', 'assigned_by_user_id' => $admin->id]
        );

        if ($tenantAdmin) {
            RoleAssignment::query()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'user_id' => $tenantAdmin->id],
                ['role' => 'tenant-admin', 'assigned_by_user_id' => $admin->id]
            );
        }

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

        if ($tenantC && $viewerC) {
            RoleAssignment::query()->updateOrCreate(
                ['tenant_id' => $tenantC->id, 'user_id' => $viewerC->id],
                ['role' => 'viewer', 'assigned_by_user_id' => $viewerC->id]
            );
        }
    }
}

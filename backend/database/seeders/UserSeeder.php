<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Models\UserAccessMethod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->first();

        if (! $tenant) {
            return;
        }

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@assistdoc.local'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Admin Demo',
                'password' => Hash::make('password123'),
                'role' => 'super-admin',
                'auth_provider' => 'local',
                'status' => 'active',
            ]
        );
        $this->syncAccessMethods($admin, ['company_account', 'password']);

        $operator = User::query()->updateOrCreate(
            ['email' => 'operator@assistdoc.local'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Operator Demo',
                'password' => Hash::make('password123'),
                'role' => 'operator',
                'auth_provider' => 'local',
                'status' => 'active',
            ]
        );
        $this->syncAccessMethods($operator, ['company_account', 'password']);

        $viewer = User::query()->updateOrCreate(
            ['email' => 'viewer@assistdoc.local'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Viewer Demo',
                'password' => Hash::make('password123'),
                'role' => 'viewer',
                'auth_provider' => 'local',
                'status' => 'active',
            ]
        );
        $this->syncAccessMethods($viewer, ['company_account', 'password']);

        $tenantB = Tenant::query()->where('slug', 'tenant-b')->first();

        if ($tenantB) {
            $viewerB = User::query()->updateOrCreate(
                ['email' => 'viewer-b@assistdoc.local'],
                [
                    'tenant_id' => $tenantB->id,
                    'name' => 'Viewer Tenant B',
                    'password' => Hash::make('password123'),
                    'role' => 'viewer',
                    'auth_provider' => 'local',
                    'status' => 'active',
                ]
            );
            $this->syncAccessMethods($viewerB, ['company_account', 'password']);
        }
    }

    private function syncAccessMethods(User $user, array $methods): void
    {
        foreach (['company_account', 'password'] as $method) {
            UserAccessMethod::query()->updateOrCreate(
                ['user_id' => $user->id, 'method' => $method],
                [
                    'enabled' => in_array($method, $methods, true),
                    'managed_by' => 'seed',
                ]
            );
        }
    }
}

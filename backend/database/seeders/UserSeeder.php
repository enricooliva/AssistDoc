<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
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

        User::query()->updateOrCreate(
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

        User::query()->updateOrCreate(
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

        User::query()->updateOrCreate(
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

        $tenantB = Tenant::query()->where('slug', 'tenant-b')->first();

        if ($tenantB) {
            User::query()->updateOrCreate(
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
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::query()->updateOrCreate(
            ['slug' => 'assistdoc-demo'],
            ['name' => 'AssistDoc Demo', 'status' => 'active']
        );

        Tenant::query()->updateOrCreate(
            ['slug' => 'tenant-b'],
            ['name' => 'Tenant B', 'status' => 'active']
        );

        Tenant::query()->updateOrCreate(
            ['slug' => 'tenant-c'],
            ['name' => 'Tenant C', 'status' => 'active']
        );
    }
}

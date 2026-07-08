<?php

namespace Tests\Unit\Auth;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Auth\EnterpriseUserLifecycleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnterpriseUserLifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_user_with_access_methods_and_role_assignment(): void
    {
        $this->seed(DatabaseSeeder::class);

        $tenant = Tenant::query()->where('slug', 'assistdoc-demo')->firstOrFail();
        $actor = User::query()->where('email', 'admin@assistdoc.local')->firstOrFail();
        $service = app(EnterpriseUserLifecycleService::class);

        $result = $service->createUser((string) $tenant->id, (string) $actor->id, [
            'full_name' => 'Giulia Bianchi',
            'email' => 'giulia.bianchi@example.test',
            'tenant_id' => (string) $tenant->id,
            'role' => 'operator',
            'access_methods' => ['company_account', 'password'],
            'status' => 'active',
            'mfa_policy' => 'required',
            'password' => 'Password12345',
        ]);

        $this->assertSame('giulia.bianchi@example.test', $result['user']['email']);
        $this->assertSame(['company_account', 'password'], $result['user']['access_methods']);
    }
}

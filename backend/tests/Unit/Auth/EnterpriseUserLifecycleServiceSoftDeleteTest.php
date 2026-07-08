<?php

namespace Tests\Unit\Auth;

use App\Models\User;
use App\Services\Auth\EnterpriseUserLifecycleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnterpriseUserLifecycleServiceSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_soft_deletes_a_user_and_tracks_the_deleter(): void
    {
        $this->seed(DatabaseSeeder::class);

        $actor = User::query()->where('email', 'admin@assistdoc.local')->firstOrFail();
        $target = User::query()->where('email', 'viewer@assistdoc.local')->firstOrFail();
        $service = app(EnterpriseUserLifecycleService::class);

        $result = $service->deleteUser([
            'id' => (string) $actor->id,
            'tenant_id' => (string) $actor->tenant_id,
        ], (string) $target->id);

        $this->assertSame('deleted', $result['status']);

        $deleted = User::withTrashed()->whereKey($target->id)->firstOrFail();
        $this->assertNotNull($deleted->deleted_at);
        $this->assertSame((string) $actor->id, (string) $deleted->deleted_by_user_id);
    }

    #[Test]
    public function it_denies_self_delete(): void
    {
        $this->seed(DatabaseSeeder::class);

        $actor = User::query()->where('email', 'admin@assistdoc.local')->firstOrFail();
        $service = app(EnterpriseUserLifecycleService::class);

        $result = $service->deleteUser([
            'id' => (string) $actor->id,
            'tenant_id' => (string) $actor->tenant_id,
        ], (string) $actor->id);

        $this->assertSame('delete_denied', $result['status']);
    }
}

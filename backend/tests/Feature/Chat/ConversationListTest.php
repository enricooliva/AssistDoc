<?php

namespace Tests\Feature\Chat;

use App\Models\ChatConversation;
use App\Models\User;
use App\Services\QdrantService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConversationListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        app(QdrantService::class)->reset();
    }

    #[Test]
    public function it_lists_only_the_authenticated_users_conversations_in_descending_activity_order(): void
    {
        $viewer = User::query()->where('email', 'viewer@assistdoc.local')->firstOrFail();
        $operator = User::query()->where('email', 'operator@assistdoc.local')->firstOrFail();

        $older = ChatConversation::query()->create([
            'tenant_id' => $viewer->tenant_id,
            'user_id' => $viewer->id,
            'title' => 'Conversazione meno recente',
            'status' => 'active',
            'last_message_at' => now()->subHour(),
        ]);

        $newer = ChatConversation::query()->create([
            'tenant_id' => $viewer->tenant_id,
            'user_id' => $viewer->id,
            'title' => 'Conversazione recente',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        ChatConversation::query()->create([
            'tenant_id' => $operator->tenant_id,
            'user_id' => $operator->id,
            'title' => 'Conversazione operatore',
            'status' => 'active',
            'last_message_at' => now()->addMinute(),
        ]);

        $token = $this->login('viewer@assistdoc.local');

        $this->withToken($token)
            ->getJson('/api/v1/chat/conversations')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('items.0.id', (string) $newer->id)
            ->assertJsonPath('items.1.id', (string) $older->id);
    }

    private function login(string $email): string
    {
        return (string) $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'password123',
        ])->json('token');
    }
}

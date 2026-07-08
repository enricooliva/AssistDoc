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

    #[Test]
    public function it_paginates_the_conversation_history_and_hides_soft_deleted_items(): void
    {
        $viewer = User::query()->where('email', 'viewer@assistdoc.local')->firstOrFail();

        foreach (range(1, 26) as $index) {
            ChatConversation::query()->create([
                'tenant_id' => $viewer->tenant_id,
                'user_id' => $viewer->id,
                'title' => sprintf('Conversazione %02d', $index),
                'status' => 'active',
                'last_message_at' => now()->subMinutes($index),
            ]);
        }

        ChatConversation::query()->create([
            'tenant_id' => $viewer->tenant_id,
            'user_id' => $viewer->id,
            'title' => 'Conversazione eliminata',
            'status' => 'active',
            'last_message_at' => now()->subDay(),
            'deleted_at' => now()->subHour(),
            'deleted_by_user_id' => $viewer->id,
        ]);

        $token = $this->login('viewer@assistdoc.local');

        $this->withToken($token)
            ->getJson('/api/v1/chat/conversations?page=1&perPage=25')
            ->assertOk()
            ->assertJsonPath('page', 1)
            ->assertJsonPath('perPage', 25)
            ->assertJsonPath('total', 26)
            ->assertJsonCount(25, 'items');

        $this->withToken($token)
            ->getJson('/api/v1/chat/conversations?page=2&perPage=25')
            ->assertOk()
            ->assertJsonPath('page', 2)
            ->assertJsonPath('perPage', 25)
            ->assertJsonPath('total', 26)
            ->assertJsonCount(1, 'items');

        $this->withToken($token)
            ->getJson('/api/v1/chat/conversations')
            ->assertOk()
            ->assertJsonMissing(['title' => 'Conversazione eliminata']);
    }

    private function login(string $email): string
    {
        return (string) $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'password123',
        ])->json('token');
    }
}

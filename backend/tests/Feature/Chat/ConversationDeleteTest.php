<?php

namespace Tests\Feature\Chat;

use App\Models\ChatConversation;
use App\Models\User;
use App\Services\QdrantService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConversationDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        app(QdrantService::class)->reset();
    }

    #[Test]
    public function it_soft_deletes_a_conversation_and_removes_it_from_the_list(): void
    {
        $viewer = User::query()->where('email', 'viewer@assistdoc.local')->firstOrFail();
        $conversation = ChatConversation::query()->create([
            'tenant_id' => $viewer->tenant_id,
            'user_id' => $viewer->id,
            'title' => 'Da eliminare',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        $token = $this->login('viewer@assistdoc.local');

        $this->withToken($token)
            ->deleteJson('/api/v1/chat/conversations/'.$conversation->id)
            ->assertOk()
            ->assertJsonPath('removedFromList', true)
            ->assertJsonPath('deletedBy.fullName', 'Viewer Demo');

        $this->assertDatabaseHas('chat_conversations', [
            'id' => $conversation->id,
            'deleted_by_user_id' => $viewer->id,
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/chat/conversations')
            ->assertOk()
            ->assertJsonMissing(['id' => (string) $conversation->id]);

        $this->withToken($token)
            ->getJson('/api/v1/chat/conversations/'.$conversation->id)
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    #[Test]
    public function deleting_a_conversation_twice_returns_a_conflict(): void
    {
        $viewer = User::query()->where('email', 'viewer@assistdoc.local')->firstOrFail();
        $conversation = ChatConversation::query()->create([
            'tenant_id' => $viewer->tenant_id,
            'user_id' => $viewer->id,
            'title' => 'Già eliminata',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        $token = $this->login('viewer@assistdoc.local');

        $this->withToken($token)
            ->deleteJson('/api/v1/chat/conversations/'.$conversation->id)
            ->assertOk();

        $this->withToken($token)
            ->deleteJson('/api/v1/chat/conversations/'.$conversation->id)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'ALREADY_DELETED');
    }

    #[Test]
    public function a_user_cannot_delete_a_conversation_from_another_tenant(): void
    {
        $operator = User::query()->where('email', 'operator@assistdoc.local')->firstOrFail();
        $foreignConversation = ChatConversation::query()->create([
            'tenant_id' => $operator->tenant_id,
            'user_id' => $operator->id,
            'title' => 'Conversazione operatore',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        $token = $this->login('viewer@assistdoc.local');

        $this->withToken($token)
            ->deleteJson('/api/v1/chat/conversations/'.$foreignConversation->id)
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    private function login(string $email): string
    {
        return (string) $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'password123',
        ])->json('token');
    }
}

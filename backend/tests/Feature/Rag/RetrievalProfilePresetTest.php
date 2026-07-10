<?php

namespace Tests\Feature\Rag;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RetrievalProfilePresetTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_default_profile_preset_is_selected_when_no_override_is_set(): void
    {
        $this->seed(DatabaseSeeder::class);

        $service = app(\App\Services\Rag\RetrievalModelProfileService::class);

        $profile = $service->defaultAvailable();

        $this->assertSame('qwen', $profile->slug);
        $this->assertSame('qwen3', $profile->generation_model);
        $this->assertSame('qwen3-embedding', $profile->embedding_model);
    }

    #[Test]
    public function the_low_spec_profile_preset_can_be_selected_from_configuration(): void
    {
        config()->set('rag.profile_preset', 'low_spec');
        $this->seed(DatabaseSeeder::class);

        $service = app(\App\Services\Rag\RetrievalModelProfileService::class);

        $profile = $service->defaultAvailable();

        $this->assertSame('qwen-pc-lenti', $profile->slug);
        $this->assertSame('qwen3:1.7b', $profile->generation_model);
        $this->assertSame('qwen3-embedding:0.6b', $profile->embedding_model);
        $this->assertSame(1024, $profile->embedding_dimensions);
    }
}

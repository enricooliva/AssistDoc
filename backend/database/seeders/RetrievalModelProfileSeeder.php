<?php

namespace Database\Seeders;

use App\Models\RetrievalModelProfile;
use Illuminate\Database\Seeder;

class RetrievalModelProfileSeeder extends Seeder
{
    public function run(): void
    {
        foreach ((array) config('rag.profiles', []) as $profile) {

            if (! is_array($profile) || ! isset($profile['slug'])) {
                continue;
            }

            RetrievalModelProfile::query()->updateOrCreate(
                ['slug' => $profile['slug']],
                [
                    'name' => $profile['name'] ?? $profile['slug'],
                    'generation_model' => $profile['generation_model'] ?? 'qwen3',
                    'embedding_model' => $profile['embedding_model'] ?? 'qwen3-embedding',
                    'tokenizer_key' => $profile['tokenizer_key'] ?? 'qwen3',
                    'token_window' => $profile['token_window'] ?? 40000,
                    'embedding_dimensions' => $profile['embedding_dimensions'] ?? 4096,
                    'available_for_new_runs' => $profile['available_for_new_runs'] ?? true,
                    'effective_from' => now(),
                ],
            );
        }
    }
}

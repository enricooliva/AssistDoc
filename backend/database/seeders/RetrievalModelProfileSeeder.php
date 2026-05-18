<?php

namespace Database\Seeders;

use App\Models\RetrievalModelProfile;
use Illuminate\Database\Seeder;

class RetrievalModelProfileSeeder extends Seeder
{
    public function run(): void
    {
        RetrievalModelProfile::query()->updateOrCreate(
            ['slug' => config('rag.default_retrieval_profile.slug', 'qwen')],
            [
                'name' => config('rag.default_retrieval_profile.name', 'Qwen'),
                'generation_model' => config('rag.default_retrieval_profile.generation_model', 'qwen3'),
                'embedding_model' => config('rag.default_retrieval_profile.embedding_model', 'qwen3-embedding'),
                'tokenizer_key' => config('rag.default_retrieval_profile.tokenizer_key', 'qwen3'),
                'token_window' => config('rag.default_retrieval_profile.token_window', 40000),
                'embedding_dimensions' => config('rag.default_retrieval_profile.embedding_dimensions', 4096),
                'available_for_new_runs' => config('rag.default_retrieval_profile.available_for_new_runs', true),
                'effective_from' => now(),
            ],
        );
    }
}

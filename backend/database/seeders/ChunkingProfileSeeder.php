<?php

namespace Database\Seeders;

use App\Models\ChunkingProfile;
use Illuminate\Database\Seeder;

class ChunkingProfileSeeder extends Seeder
{
    public function run(): void
    {
        ChunkingProfile::query()->updateOrCreate(
            ['slug' => 'small'],
            [
                'name' => 'Small',
                'chunk_size_tokens' => 180,
                'overlap_tokens' => 20,
                'active' => true,
                'notes' => 'Profilo compatto per documenti brevi.',
            ],
        );

        ChunkingProfile::query()->updateOrCreate(
            ['slug' => 'medium'],
            [
                'name' => 'Medium',
                'chunk_size_tokens' => 300,
                'overlap_tokens' => 40,
                'active' => true,
                'notes' => 'Profilo bilanciato predefinito.',
            ],
        );

        ChunkingProfile::query()->updateOrCreate(
            ['slug' => 'large'],
            [
                'name' => 'Large',
                'chunk_size_tokens' => 1200,
                'overlap_tokens' => 120,
                'active' => true,
                'notes' => 'Profilo esteso per modelli con finestra contestuale ampia.',
            ],
        );
    }
}

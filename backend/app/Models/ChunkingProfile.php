<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChunkingProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'chunk_size_tokens',
        'overlap_tokens',
        'active',
        'notes',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function preparationRuns(): HasMany
    {
        return $this->hasMany(ChunkPreparationRun::class);
    }

    public function segments(): HasMany
    {
        return $this->hasMany(DocumentSegment::class);
    }
}

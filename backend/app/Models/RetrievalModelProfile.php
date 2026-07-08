<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RetrievalModelProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'generation_model',
        'embedding_model',
        'tokenizer_key',
        'token_window',
        'embedding_dimensions',
        'available_for_new_runs',
        'effective_from',
        'retired_at',
    ];

    protected $casts = [
        'available_for_new_runs' => 'boolean',
        'effective_from' => 'datetime',
        'retired_at' => 'datetime',
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

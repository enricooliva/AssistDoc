<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSegment extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'document_id',
        'chunk_preparation_run_id',
        'retrieval_model_profile_id',
        'chunking_profile_id',
        'segment_index',
        'content_text',
        'token_count',
        'source_label',
        'searchable',
        'activated_at',
        'retired_at',
    ];

    protected $casts = [
        'searchable' => 'boolean',
        'activated_at' => 'datetime',
        'retired_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function preparationRun(): BelongsTo
    {
        return $this->belongsTo(ChunkPreparationRun::class, 'chunk_preparation_run_id');
    }

    public function retrievalModelProfile(): BelongsTo
    {
        return $this->belongsTo(RetrievalModelProfile::class);
    }

    public function chunkingProfile(): BelongsTo
    {
        return $this->belongsTo(ChunkingProfile::class);
    }
}

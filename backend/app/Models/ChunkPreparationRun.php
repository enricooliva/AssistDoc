<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChunkPreparationRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'document_id',
        'retrieval_model_profile_id',
        'chunking_profile_id',
        'requested_by_user_id',
        'status',
        'failure_code',
        'failure_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function retrievalModelProfile(): BelongsTo
    {
        return $this->belongsTo(RetrievalModelProfile::class);
    }

    public function chunkingProfile(): BelongsTo
    {
        return $this->belongsTo(ChunkingProfile::class);
    }

    public function validationFailures(): HasMany
    {
        return $this->hasMany(PreparationValidationFailure::class);
    }
}

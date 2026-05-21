<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'uploaded_by_user_id',
        'active_retrieval_model_profile_id',
        'active_chunking_profile_id',
        'active_preparation_run_id',
        'filename',
        'media_type',
        'storage_path',
        'tags',
        'size_bytes',
        'status',
        'failure_reason',
        'uploaded_at',
        'last_status_at',
        'indexed_at',
        'deleted_at',
        'deleted_by_user_id',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'last_status_at' => 'datetime',
        'indexed_at' => 'datetime',
        'deleted_at' => 'datetime',
        'tags' => 'array',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by_user_id');
    }

    public function activeRetrievalModelProfile(): BelongsTo
    {
        return $this->belongsTo(RetrievalModelProfile::class, 'active_retrieval_model_profile_id');
    }

    public function activeChunkingProfile(): BelongsTo
    {
        return $this->belongsTo(ChunkingProfile::class, 'active_chunking_profile_id');
    }

    public function activePreparationRun(): BelongsTo
    {
        return $this->belongsTo(ChunkPreparationRun::class, 'active_preparation_run_id');
    }

    public function segments(): HasMany
    {
        return $this->hasMany(DocumentSegment::class);
    }

    public function preparationRuns(): HasMany
    {
        return $this->hasMany(ChunkPreparationRun::class);
    }
}

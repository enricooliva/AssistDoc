<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreparationValidationFailure extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'chunk_preparation_run_id',
        'document_id',
        'failure_code',
        'failure_message',
        'segment_index',
        'measured_token_count',
        'allowed_token_count',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function preparationRun(): BelongsTo
    {
        return $this->belongsTo(ChunkPreparationRun::class, 'chunk_preparation_run_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}

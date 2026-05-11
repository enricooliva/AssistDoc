<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'uploaded_by_user_id',
        'filename',
        'media_type',
        'storage_path',
        'size_bytes',
        'status',
        'failure_reason',
        'uploaded_at',
        'last_status_at',
        'indexed_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'last_status_at' => 'datetime',
        'indexed_at' => 'datetime',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function segments(): HasMany
    {
        return $this->hasMany(DocumentSegment::class);
    }
}

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
        'segment_index',
        'content_text',
        'source_label',
        'searchable',
    ];

    protected $casts = [
        'searchable' => 'boolean',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}

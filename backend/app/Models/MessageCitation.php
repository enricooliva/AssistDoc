<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageCitation extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'chat_message_id',
        'document_id',
        'document_segment_id',
        'quote_text',
        'source_label',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function chatMessage(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'chat_message_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function documentSegment(): BelongsTo
    {
        return $this->belongsTo(DocumentSegment::class, 'document_segment_id');
    }
}

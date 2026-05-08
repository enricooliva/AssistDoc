<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}


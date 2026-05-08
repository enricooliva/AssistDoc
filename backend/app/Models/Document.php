<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}


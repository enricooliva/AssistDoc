<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MfaChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'authentication_method',
        'status',
        'required_by_policy',
        'verification_code_hash',
        'failure_count',
        'initiated_at',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'required_by_policy' => 'boolean',
        'initiated_at' => 'datetime',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

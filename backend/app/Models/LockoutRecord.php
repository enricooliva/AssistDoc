<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LockoutRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'trigger_type',
        'status',
        'failure_threshold',
        'failure_count',
        'locked_at',
        'locked_until',
        'released_at',
        'released_by_user_id',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
        'locked_until' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by_user_id');
    }
}

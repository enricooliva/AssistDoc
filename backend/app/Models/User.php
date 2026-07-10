<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'auth_provider',
        'status',
        'mfa_policy',
        'last_login_at',
        'locked_at',
        'locked_until',
        'lockout_reason',
        'password_reset_required',
        'deleted_at',
        'deleted_by_user_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'locked_at' => 'datetime',
            'locked_until' => 'datetime',
            'deleted_at' => 'datetime',
            'password_reset_required' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function roleAssignment(): HasOne
    {
        return $this->hasOne(RoleAssignment::class);
    }

    public function accessMethods(): HasMany
    {
        return $this->hasMany(UserAccessMethod::class);
    }

    public function passwordResetJourneys(): HasMany
    {
        return $this->hasMany(PasswordResetJourney::class);
    }

    public function mfaChallenges(): HasMany
    {
        return $this->hasMany(MfaChallenge::class);
    }

    public function lockoutRecords(): HasMany
    {
        return $this->hasMany(LockoutRecord::class);
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(AuditEvent::class, 'actor_user_id');
    }

    public function deletedAuditEvents(): HasMany
    {
        return $this->hasMany(AuditEvent::class, 'target_id')
            ->where('target_type', 'user');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by_user_id');
    }

    public function deletedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'deleted_by_user_id');
    }
}

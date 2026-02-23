<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'auditee_agency',
        'period_start',
        'period_end',
        'status',
        'created_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_REVIEW = 'review';
    public const STATUS_FINAL = 'final';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_REVIEW,
        self::STATUS_FINAL,
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role_in_project')
            ->withTimestamps();
    }

    public function photoEvidences(): HasMany
    {
        return $this->hasMany(PhotoEvidence::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->morphMany(AuditLog::class, 'entity');
    }

    public function isFinal(): bool
    {
        return $this->status === self::STATUS_FINAL;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isReview(): bool
    {
        return $this->status === self::STATUS_REVIEW;
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function getMemberRole(User $user): ?string
    {
        $member = $this->members()->where('user_id', $user->id)->first();
        return $member ? $member->pivot->role_in_project : null;
    }
}

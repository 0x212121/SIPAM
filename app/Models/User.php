<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->withPivot('role_in_project')
            ->withTimestamps();
    }

    public function photoEvidences()
    {
        return $this->hasMany(PhotoEvidence::class, 'uploaded_by');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'actor_user_id');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isAuditor(): bool
    {
        return $this->hasRole('auditor');
    }

    public function isReviewer(): bool
    {
        return $this->hasRole('reviewer');
    }

    public function isReadonly(): bool
    {
        return $this->hasRole('readonly');
    }
}

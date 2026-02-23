<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMember extends Model
{
    use HasFactory;

    protected $table = 'project_members';

    protected $fillable = [
        'project_id',
        'user_id',
        'role_in_project',
    ];

    public const ROLE_AUDITOR = 'auditor';
    public const ROLE_REVIEWER = 'reviewer';
    public const ROLE_READONLY = 'readonly';

    public const ROLES = [
        self::ROLE_AUDITOR,
        self::ROLE_REVIEWER,
        self::ROLE_READONLY,
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'action',
        'field',
        'old_value',
        'new_value',
        'actor_user_id',
    ];

    protected $casts = [
        'old_value' => 'json',
        'new_value' => 'json',
    ];

    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_LOCK = 'lock';
    public const ACTION_STATUS_CHANGE = 'status_change';

    public const ACTIONS = [
        self::ACTION_CREATE,
        self::ACTION_UPDATE,
        self::ACTION_DELETE,
        self::ACTION_LOCK,
        self::ACTION_STATUS_CHANGE,
    ];

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public static function log(
        Model $entity,
        string $action,
        ?string $field = null,
        mixed $oldValue = null,
        mixed $newValue = null,
        ?User $actor = null
    ): self {
        return self::create([
            'entity_type' => get_class($entity),
            'entity_id' => $entity->getKey(),
            'action' => $action,
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'actor_user_id' => $actor?->id ?? auth()->id(),
        ]);
    }
}

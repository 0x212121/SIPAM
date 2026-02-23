<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public static function log(
        Model $entity,
        string $action,
        ?string $field = null,
        mixed $oldValue = null,
        mixed $newValue = null,
        ?User $actor = null
    ): AuditLog {
        return AuditLog::log($entity, $action, $field, $oldValue, $newValue, $actor);
    }

    public static function logCreate(Model $entity, ?User $actor = null): AuditLog
    {
        return self::log($entity, AuditLog::ACTION_CREATE, null, null, null, $actor);
    }

    public static function logUpdate(Model $entity, string $field, mixed $oldValue, mixed $newValue, ?User $actor = null): AuditLog
    {
        return self::log($entity, AuditLog::ACTION_UPDATE, $field, $oldValue, $newValue, $actor);
    }

    public static function logDelete(Model $entity, ?User $actor = null): AuditLog
    {
        return self::log($entity, AuditLog::ACTION_DELETE, null, null, null, $actor);
    }

    public static function logStatusChange(Model $entity, string $oldStatus, string $newStatus, ?User $actor = null): AuditLog
    {
        return self::log($entity, AuditLog::ACTION_STATUS_CHANGE, 'status', $oldStatus, $newStatus, $actor);
    }

    public static function logLock(Model $entity, ?User $actor = null): AuditLog
    {
        return self::log($entity, AuditLog::ACTION_LOCK, null, null, null, $actor);
    }

    public static function getEntityLogs(Model $entity, int $limit = 50)
    {
        return AuditLog::where('entity_type', get_class($entity))
            ->where('entity_id', $entity->getKey())
            ->with('actor')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}

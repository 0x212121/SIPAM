<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhotoEvidence extends Model
{
    use HasFactory;

    protected $table = 'photo_evidences';

    protected $fillable = [
        'project_id',
        'original_path',
        'preview_path',
        'thumb_path',
        'exif_lat',
        'exif_lng',
        'gps_status',
        'taken_at',
        'taken_at_source',
        'caption',
        'tags',
        'uploaded_by',
        'uploaded_at',
        'sha256_hash',
        'raw_exif_json',
    ];

    protected $casts = [
        'exif_lat' => 'float',
        'exif_lng' => 'float',
        'taken_at' => 'datetime',
        'uploaded_at' => 'datetime',
        'tags' => 'array',
        'raw_exif_json' => 'array',
    ];

    public const GPS_STATUS_ORIGINAL = 'original';
    public const GPS_STATUS_MANUAL = 'manual';
    public const GPS_STATUS_MISSING = 'missing';

    public const GPS_STATUSES = [
        self::GPS_STATUS_ORIGINAL,
        self::GPS_STATUS_MANUAL,
        self::GPS_STATUS_MISSING,
    ];

    public const TAKEN_AT_SOURCE_DATETIME_ORIGINAL = 'DateTimeOriginal';
    public const TAKEN_AT_SOURCE_CREATE_DATE = 'CreateDate';
    public const TAKEN_AT_SOURCE_FILE_TIMESTAMP = 'FileTimestamp';
    public const TAKEN_AT_SOURCE_MANUAL = 'manual';
    public const TAKEN_AT_SOURCE_MISSING = 'missing';

    public const TAKEN_AT_SOURCES = [
        self::TAKEN_AT_SOURCE_DATETIME_ORIGINAL,
        self::TAKEN_AT_SOURCE_CREATE_DATE,
        self::TAKEN_AT_SOURCE_FILE_TIMESTAMP,
        self::TAKEN_AT_SOURCE_MANUAL,
        self::TAKEN_AT_SOURCE_MISSING,
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->morphMany(AuditLog::class, 'entity');
    }

    public function hasGps(): bool
    {
        return $this->exif_lat !== null && $this->exif_lng !== null;
    }

    public function getLat(): ?float
    {
        return $this->exif_lat;
    }

    public function getLng(): ?float
    {
        return $this->exif_lng;
    }

    public function setManualGps(float $lat, float $lng): void
    {
        $this->update([
            'exif_lat' => $lat,
            'exif_lng' => $lng,
            'gps_status' => self::GPS_STATUS_MANUAL,
        ]);
    }
}

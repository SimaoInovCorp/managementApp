<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Represents a file uploaded to private storage.
 *
 * Append-only — files are immutable once uploaded (delete to replace).
 */
class DigitalArchive extends Model
{
    use HasFactory, LogsActivity;

    /** No updated_at — files are immutable once uploaded. */
    public $timestamps = false;

    protected $fillable = [
        'name',
        'path',
        'category',
        'entity_id',
        'description',
        'uploaded_by',
    ];

    /** Cast created_at manually since timestamps are disabled. */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    // ─── Relationships ──────────────────────────────────────────────────────

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ─── Activity Log ───────────────────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logExcept(['path'])                // path is an implementation detail
            ->useLogName('digital_archive');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Represents a work order assigned to a client.
 *
 * @property int $id
 * @property int $number
 * @property string $date
 * @property int $client_id
 * @property string|null $description
 * @property string $status draft | closed
 */
class WorkOrder extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'number',
        'date',
        'client_id',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
        ];
    }

    // ─── Relationships ──────────────────────────────────────────────────────────

    public function client(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'client_id');
    }

    // ─── Activity Log ─────────────────────────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['number', 'status', 'client_id'])
            ->logOnlyDirty()
            ->useLogName('work_orders')
            ->dontSubmitEmptyLogs();
    }
}

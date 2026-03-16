<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A calendar event that can be personal or shared with other users.
 */
class CalendarEvent extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'title',
        'date',
        'time',
        'duration_minutes',
        'user_id',
        'entity_id',
        'type_id',
        'action_id',
        'description',
        'shared_with',
        'knowledge',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'shared_with' => 'array',
        ];
    }

    // ─── Relationships ──────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(CalendarType::class, 'type_id');
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(CalendarAction::class, 'action_id');
    }

    // ─── Activity Log ───────────────────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('calendar');
    }
}

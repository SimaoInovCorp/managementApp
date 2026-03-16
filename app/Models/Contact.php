<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Represents a person linked to an Entity (client or supplier).
 *
 * Sensitive fields (`phone`, `mobile`, `email`) are stored encrypted.
 *
 * @property int $id
 * @property int $number
 * @property int $entity_id
 * @property string $first_name
 * @property string $last_name
 * @property int|null $role_id
 * @property string|null $phone
 * @property string|null $mobile
 * @property string|null $email
 * @property bool $gdpr_consent
 * @property string|null $notes
 * @property string $status active | inactive
 */
class Contact extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'number',
        'entity_id',
        'first_name',
        'last_name',
        'role_id',
        'phone',
        'mobile',
        'email',
        'gdpr_consent',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'phone' => 'encrypted',
            'mobile' => 'encrypted',
            'email' => 'encrypted',
            'gdpr_consent' => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    /** The entity this contact belongs to. */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /** The configurable role assigned to this contact. */
    public function role(): BelongsTo
    {
        return $this->belongsTo(ContactRole::class, 'role_id');
    }

    // ─── Activity Log ─────────────────────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['first_name', 'last_name', 'entity_id', 'status'])
            ->logOnlyDirty()
            ->useLogName('contacts')
            ->dontSubmitEmptyLogs();
    }
}

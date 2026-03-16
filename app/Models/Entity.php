<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Represents both clients and suppliers (distinguished by the `type` field).
 * Sensitive fields (nif, email, phone, mobile) are stored encrypted.
 *
 * @property int $id
 * @property string $type client | supplier | both
 * @property int $number
 * @property string|null $nif
 * @property string $name
 * @property string|null $address
 * @property string|null $postal_code
 * @property string|null $locality
 * @property int|null $country_id
 * @property string|null $phone
 * @property string|null $mobile
 * @property string|null $website
 * @property string|null $email
 * @property bool $gdpr_consent
 * @property string|null $notes
 * @property string $status active | inactive
 */
class Entity extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'type',
        'number',
        'nif',
        'name',
        'address',
        'postal_code',
        'locality',
        'country_id',
        'phone',
        'mobile',
        'website',
        'email',
        'gdpr_consent',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'nif' => 'encrypted',
            'phone' => 'encrypted',
            'mobile' => 'encrypted',
            'email' => 'encrypted',
            'gdpr_consent' => 'boolean',
        ];
    }

    // ─── Relationships ──────────────────────────────────────────────────────

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** Contacts linked to this entity. */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    // ─── Activity Log ───────────────────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'name', 'status'])
            ->logOnlyDirty()
            ->useLogName('entities')
            ->dontSubmitEmptyLogs();
    }
}

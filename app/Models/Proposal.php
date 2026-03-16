<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Represents a commercial proposal sent to a client.
 *
 * @property int $id
 * @property int $number
 * @property string $proposal_date
 * @property int $client_id
 * @property string $validity_date
 * @property float $total_amount total including VAT
 * @property string $status draft | closed
 * @property string|null $notes
 */
class Proposal extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'number',
        'proposal_date',
        'client_id',
        'validity_date',
        'total_amount',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'proposal_date' => 'date:Y-m-d',
            'validity_date' => 'date:Y-m-d',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    /** The client entity this proposal was issued to. */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'client_id');
    }

    /** Line items, ordered by sort_order. */
    public function lines(): HasMany
    {
        return $this->hasMany(ProposalLine::class)->orderBy('sort_order');
    }

    /** The customer order derived from this proposal (if converted). */
    public function customerOrder(): HasOne
    {
        return $this->hasOne(CustomerOrder::class, 'proposal_id');
    }

    // ─── Activity Log ────────────────────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['number', 'status', 'total_amount'])
            ->logOnlyDirty()
            ->useLogName('proposals')
            ->dontSubmitEmptyLogs();
    }
}

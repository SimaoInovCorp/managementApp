<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only ledger entry for a client's current account.
 * No updated_at — once written, entries are immutable.
 */
class CustomerAccount extends Model
{
    public $timestamps = false;  // only created_at (set via DB default)

    protected $fillable = [
        'entity_id',
        'description',
        'debit',
        'credit',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
            'date' => 'date:Y-m-d',
        ];
    }

    /** The client/entity this ledger entry belongs to. */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }
}

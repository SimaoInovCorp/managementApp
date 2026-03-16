<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Represents a confirmed customer order.
 * May originate from a Proposal or be created independently.
 *
 * @property int $id
 * @property int $number
 * @property string $order_date
 * @property int $client_id
 * @property int|null $proposal_id
 * @property float $total_amount
 * @property string $status draft | closed
 * @property string|null $notes
 */
class CustomerOrder extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'number',
        'order_date',
        'client_id',
        'proposal_id',
        'total_amount',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'order_date' => 'date:Y-m-d',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function client(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'client_id');
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CustomerOrderLine::class)->orderBy('sort_order');
    }

    public function supplierOrders(): HasMany
    {
        return $this->hasMany(SupplierOrder::class);
    }

    // ─── Activity Log ────────────────────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['number', 'status', 'total_amount'])
            ->logOnlyDirty()
            ->useLogName('customer_orders')
            ->dontSubmitEmptyLogs();
    }
}

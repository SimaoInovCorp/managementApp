<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Represents a purchase order sent to a supplier.
 * May originate from a CustomerOrder or be created independently.
 *
 * @property int $id
 * @property int $number
 * @property string $order_date
 * @property int $supplier_id
 * @property int|null $customer_order_id
 * @property float $total_amount
 * @property string $status draft | closed
 * @property string|null $notes
 */
class SupplierOrder extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'number',
        'order_date',
        'supplier_id',
        'customer_order_id',
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

    // ─── Relationships ──────────────────────────────────────────────────────────

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'supplier_id');
    }

    public function customerOrder(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SupplierOrderLine::class)->orderBy('sort_order');
    }

    // ─── Activity Log ─────────────────────────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['number', 'status', 'total_amount'])
            ->logOnlyDirty()
            ->useLogName('supplier_orders')
            ->dontSubmitEmptyLogs();
    }
}

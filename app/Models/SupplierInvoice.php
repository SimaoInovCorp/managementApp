<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Invoice received from a supplier — tracks document, payment proof, and status.
 */
class SupplierInvoice extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'number',
        'invoice_date',
        'due_date',
        'supplier_id',
        'supplier_order_id',
        'total_amount',
        'document_path',
        'payment_proof_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'invoice_date' => 'date:Y-m-d',
            'due_date' => 'date:Y-m-d',
        ];
    }

    // ─── Relationships ──────────────────────────────────────────────────────

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'supplier_id');
    }

    public function supplierOrder(): BelongsTo
    {
        return $this->belongsTo(SupplierOrder::class);
    }

    // ─── Activity Log ───────────────────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('supplier_invoices');
    }
}

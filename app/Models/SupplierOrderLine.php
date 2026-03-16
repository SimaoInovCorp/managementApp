<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single line item on a SupplierOrder.
 *
 * @property int $id
 * @property int $supplier_order_id
 * @property int $article_id
 * @property float $quantity
 * @property float $unit_price
 * @property int $sort_order
 */
class SupplierOrderLine extends Model
{
    protected $fillable = [
        'supplier_order_id',
        'article_id',
        'quantity',
        'unit_price',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
        ];
    }

    public function supplierOrder(): BelongsTo
    {
        return $this->belongsTo(SupplierOrder::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}

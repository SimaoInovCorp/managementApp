<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single line item on a CustomerOrder.
 *
 * @property int $id
 * @property int $customer_order_id
 * @property int $article_id
 * @property int|null $supplier_id
 * @property float $quantity
 * @property float $unit_price
 * @property float|null $cost_price
 * @property int $sort_order
 */
class CustomerOrderLine extends Model
{
    protected $fillable = [
        'customer_order_id',
        'article_id',
        'supplier_id',
        'quantity',
        'unit_price',
        'cost_price',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
        ];
    }

    public function customerOrder(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'supplier_id');
    }
}

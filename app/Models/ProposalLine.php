<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single line item on a Proposal.
 *
 * @property int $id
 * @property int $proposal_id
 * @property int $article_id
 * @property int|null $supplier_id preferred supplier for this line
 * @property float $quantity
 * @property float $unit_price selling price per unit (ex-VAT)
 * @property float|null $cost_price purchase cost from supplier
 * @property int $sort_order
 */
class ProposalLine extends Model
{
    protected $fillable = [
        'proposal_id',
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

    // ─── Relationships ───────────────────────────────────────────────────────

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /** The article (product/service) in this line. */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /** Optional preferred supplier for purchasing this line's article. */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'supplier_id');
    }
}

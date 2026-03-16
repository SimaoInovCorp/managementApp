<?php

namespace App\Services;

use App\Models\CustomerOrder;
use App\Models\CustomerOrderLine;
use App\Models\Proposal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Encapsulates proposal business logic, keeping controllers lean.
 */
class ProposalService
{
    /**
     * Return the next sequential proposal number.
     */
    public function nextNumber(): int
    {
        return (int) (Proposal::max('number') ?? 0) + 1;
    }

    /**
     * Return the next sequential customer order number.
     */
    public function nextOrderNumber(): int
    {
        return (int) (CustomerOrder::max('number') ?? 0) + 1;
    }

    /**
     * Compute the grand total (inc. VAT) for a set of line items.
     *
     * Each element of $lines must have:
     *   - quantity    (numeric)
     *   - unit_price  (numeric, ex-VAT)
     *   - vat_rate    (numeric percentage, e.g. 23 for 23%)
     *
     * @param  array<int, array{quantity: float|string, unit_price: float|string, vat_rate: float|string}>  $lines
     */
    public function computeTotal(array $lines): float
    {
        return (float) collect($lines)->sum(function (array $line): float {
            $qty = (float) ($line['quantity'] ?? 0);
            $price = (float) ($line['unit_price'] ?? 0);
            $vatPct = (float) ($line['vat_rate'] ?? 0);

            return $qty * $price * (1 + $vatPct / 100);
        });
    }

    /**
     * Convert a Proposal to a new CustomerOrder in 'draft' status.
     * Copies all header fields and line items.
     * Wrapped in a DB transaction so it's atomic.
     *
     * @param  Proposal  $proposal  Must have 'lines.article.vatRate' eager-loaded.
     */
    public function convertToCustomerOrder(Proposal $proposal): CustomerOrder
    {
        return DB::transaction(function () use ($proposal): CustomerOrder {
            /** @var CustomerOrder $order */
            $order = CustomerOrder::create([
                'number' => $this->nextOrderNumber(),
                'order_date' => Carbon::today(),
                'client_id' => $proposal->client_id,
                'proposal_id' => $proposal->id,
                'total_amount' => $proposal->total_amount,
                'status' => 'draft',
                'notes' => $proposal->notes,
            ]);

            foreach ($proposal->lines as $line) {
                CustomerOrderLine::create([
                    'customer_order_id' => $order->id,
                    'article_id' => $line->article_id,
                    'supplier_id' => $line->supplier_id,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'cost_price' => $line->cost_price,
                    'sort_order' => $line->sort_order,
                ]);
            }

            return $order;
        });
    }
}

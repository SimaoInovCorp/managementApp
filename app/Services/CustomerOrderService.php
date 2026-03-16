<?php

namespace App\Services;

use App\Models\Article;
use App\Models\CustomerOrder;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderLine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Encapsulates customer order business logic, keeping controllers lean.
 */
class CustomerOrderService
{
    /**
     * Return the next sequential customer order number.
     */
    public function nextNumber(): int
    {
        return (int) (CustomerOrder::max('number') ?? 0) + 1;
    }

    /**
     * Return the next sequential supplier order number.
     */
    public function nextSupplierOrderNumber(): int
    {
        return (int) (SupplierOrder::max('number') ?? 0) + 1;
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
     * Enrich a lines array with the vat_rate from each article's VAT relation.
     * Loads all referenced articles in a single query (N+1 prevention).
     *
     * @param  array<int, array<string, mixed>>  $lines  raw lines from request
     * @return array<int, array<string, mixed>>
     */
    public function enrichLinesWithVat(array $lines): array
    {
        $articleIds = array_unique(array_column($lines, 'article_id'));
        $articles = Article::with('vatRate:id,rate')
            ->whereIn('id', $articleIds)
            ->get(['id', 'vat_id'])
            ->keyBy('id');

        return array_map(function (array $line) use ($articles): array {
            $article = $articles->get((int) $line['article_id']);
            $line['vat_rate'] = $article?->vatRate ? (float) $article->vatRate->rate : 0;

            return $line;
        }, $lines);
    }

    /**
     * Convert a closed CustomerOrder to one SupplierOrder per supplier.
     *
     * Lines without a supplier_id are skipped (cannot be directed to a supplier).
     * All operations are wrapped in a single DB transaction.
     *
     * @param  CustomerOrder  $order  Must have 'lines.article.vatRate' eager-loaded.
     * @return Collection<int, SupplierOrder> Collection of the newly created SupplierOrders.
     */
    public function convertToSupplierOrders(CustomerOrder $order): Collection
    {
        return DB::transaction(function () use ($order): Collection {
            // Group lines by supplier — skip lines with no supplier
            $linesBySupplier = $order->lines
                ->filter(fn ($line) => $line->supplier_id !== null)
                ->groupBy('supplier_id');

            $supplierOrders = collect();
            $baseNumber = $this->nextSupplierOrderNumber();
            $offset = 0;

            foreach ($linesBySupplier as $supplierId => $lines) {
                // Compute total for this supplier's lines
                $total = $lines->sum(function ($line) {
                    $vatRate = $line->article?->vatRate ? (float) $line->article->vatRate->rate : 0;

                    return (float) $line->quantity * (float) $line->unit_price * (1 + $vatRate / 100);
                });

                /** @var SupplierOrder $supplierOrder */
                $supplierOrder = SupplierOrder::create([
                    'number' => $baseNumber + $offset,
                    'order_date' => Carbon::today(),
                    'supplier_id' => $supplierId,
                    'customer_order_id' => $order->id,
                    'total_amount' => $total,
                    'status' => 'draft',
                    'notes' => null,
                ]);

                foreach ($lines as $sortIndex => $line) {
                    SupplierOrderLine::create([
                        'supplier_order_id' => $supplierOrder->id,
                        'article_id' => $line->article_id,
                        'quantity' => $line->quantity,
                        'unit_price' => $line->unit_price,
                        'sort_order' => $sortIndex,
                    ]);
                }

                $supplierOrders->push($supplierOrder);
                $offset++;
            }

            return $supplierOrders;
        });
    }
}

<?php

namespace App\Services;

use App\Models\Article;
use App\Models\SupplierOrder;

/**
 * Encapsulates supplier order business logic, keeping controllers lean.
 */
class SupplierOrderService
{
    /**
     * Return the next sequential supplier order number.
     */
    public function nextNumber(): int
    {
        return (int) (SupplierOrder::max('number') ?? 0) + 1;
    }

    /**
     * Compute the grand total (inc. VAT) for a set of line items.
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
}

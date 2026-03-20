<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierOrderRequest;
use App\Http\Requests\UpdateSupplierOrderRequest;
use App\Http\Resources\SupplierOrderResource;
use App\Models\Article;
use App\Models\CompanySetting;
use App\Models\CustomerOrder;
use App\Models\Entity;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderLine;
use App\Services\SupplierOrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages the supplier orders lifecycle:
 * listing, create/edit/delete, and PDF export.
 */
class SupplierOrderController extends Controller
{
    public function __construct(private readonly SupplierOrderService $service) {}

    /**
     * List all supplier orders with supplier, lines, and customer order reference.
     */
    public function index(Request $request): Response
    {
        $orders = SupplierOrder::with([
            'supplier:id,name,type',
            'customerOrder:id,number',
            'lines.article.vatRate',
        ])
            ->orderByDesc('order_date')
            ->orderByDesc('number')
            ->paginate(15);

        $suppliers = Entity::whereIn('type', ['supplier', 'both'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $customerOrders = CustomerOrder::orderByDesc('number')
            ->get(['id', 'number', 'status']);

        $articles = Article::with('vatRate:id,name,rate')
            ->where('status', 'active')
            ->orderBy('reference')
            ->get(['id', 'reference', 'name', 'price', 'vat_id']);

        return Inertia::render('supplier_orders/Index', [
            'orders' => SupplierOrderResource::collection($orders),
            'suppliers' => $suppliers,
            'customerOrders' => $customerOrders,
            'articles' => $articles->map(fn (Article $a) => [
                'id' => $a->id,
                'reference' => $a->reference,
                'name' => $a->name,
                'price' => (float) $a->price,
                'vat_id' => $a->vat_id,
                'vat_rate' => $a->vatRate ? (float) $a->vatRate->rate : 0,
            ]),
        ]);
    }

    /**
     * Store a new supplier order with its line items.
     */
    public function store(StoreSupplierOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $lines = $data['lines'];
        unset($data['lines']);

        $enriched = $this->service->enrichLinesWithVat($lines);
        $data['number'] = $this->service->nextNumber();
        $data['total_amount'] = $this->service->computeTotal($enriched);

        DB::transaction(function () use ($data, $lines): void {
            /** @var SupplierOrder $order */
            $order = SupplierOrder::create($data);
            $this->saveLines($order, $lines);
        });

        return back()->with('success', 'Supplier order created successfully.');
    }

    /**
     * Update an existing supplier order, replacing all line items.
     */
    public function update(UpdateSupplierOrderRequest $request, SupplierOrder $order): RedirectResponse
    {
        $data = $request->validated();
        $lines = $data['lines'];
        unset($data['lines']);

        $enriched = $this->service->enrichLinesWithVat($lines);
        $data['total_amount'] = $this->service->computeTotal($enriched);

        DB::transaction(function () use ($order, $data, $lines): void {
            $order->update($data);
            $order->lines()->delete();
            $this->saveLines($order, $lines);
        });

        return back()->with('success', 'Supplier order updated successfully.');
    }

    /**
     * Delete a supplier order (lines cascade via DB FK).
     */
    public function destroy(SupplierOrder $order): RedirectResponse
    {
        $order->delete();

        return back()->with('success', 'Supplier order deleted.');
    }

    /**
     * Download the supplier order as a PDF file.
     */
    public function downloadPdf(SupplierOrder $order): HttpResponse
    {
        $order->load([
            'supplier',
            'customerOrder:id,number',
            'lines.article.vatRate',
        ]);

        $company = CompanySetting::first();
        $logoUrl = $company?->logo_path
            ? route('file.private', ['path' => $company->logo_path])
            : null;

        $vatBreakdown = $this->buildVatBreakdown($order->lines->all());

        $pdf = Pdf::loadView('pdf.supplier_order', compact('order', 'company', 'logoUrl', 'vatBreakdown'))
            ->setPaper('a4');

        $filename = 'SupplierOrder-'.str_pad((string) $order->number, 5, '0', STR_PAD_LEFT).'.pdf';

        return $pdf->download($filename);
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * Persist line items for a supplier order.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function saveLines(SupplierOrder $order, array $lines): void
    {
        foreach ($lines as $index => $lineData) {
            SupplierOrderLine::create([
                'supplier_order_id' => $order->id,
                'article_id' => $lineData['article_id'],
                'quantity' => $lineData['quantity'],
                'unit_price' => $lineData['unit_price'],
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * Group line totals by VAT rate for the PDF breakdown section.
     *
     * @param  SupplierOrderLine[]  $lines  eager-loaded with article.vatRate
     * @return array<int, array{rate: float, base: float, vat_amount: float}>
     */
    private function buildVatBreakdown(array $lines): array
    {
        $breakdown = [];

        foreach ($lines as $line) {
            $rate = $line->article?->vatRate ? (float) $line->article->vatRate->rate : 0;
            $base = (float) $line->quantity * (float) $line->unit_price;
            $vatAmt = $base * ($rate / 100);

            if (! isset($breakdown[$rate])) {
                $breakdown[$rate] = ['rate' => $rate, 'base' => 0, 'vat_amount' => 0];
            }
            $breakdown[$rate]['base'] += $base;
            $breakdown[$rate]['vat_amount'] += $vatAmt;
        }

        return array_values($breakdown);
    }
}

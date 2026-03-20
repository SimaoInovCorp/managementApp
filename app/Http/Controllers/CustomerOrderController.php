<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerOrderRequest;
use App\Http\Requests\UpdateCustomerOrderRequest;
use App\Http\Resources\CustomerOrderResource;
use App\Models\Article;
use App\Models\CompanySetting;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderLine;
use App\Models\Entity;
use App\Services\CustomerOrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages the customer orders lifecycle:
 * listing, create/edit/delete, PDF export, and conversion to SupplierOrders.
 */
class CustomerOrderController extends Controller
{
    public function __construct(private readonly CustomerOrderService $service) {}

    /**
     * List all customer orders with client, lines, and supplier order status.
     */
    public function index(Request $request): Response
    {
        $orders = CustomerOrder::with([
            'client:id,name,type',
            'lines.article.vatRate',
            'lines.supplier:id,name',
            'supplierOrders:id,customer_order_id,number,supplier_id',
        ])
            ->orderByDesc('order_date')
            ->orderByDesc('number')
            ->paginate(15);

        $clients = Entity::whereIn('type', ['client', 'both'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $suppliers = Entity::whereIn('type', ['supplier', 'both'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $articles = Article::with('vatRate:id,name,rate')
            ->where('status', 'active')
            ->orderBy('reference')
            ->get(['id', 'reference', 'name', 'price', 'vat_id']);

        return Inertia::render('customer_orders/Index', [
            'orders' => CustomerOrderResource::collection($orders),
            'clients' => $clients,
            'suppliers' => $suppliers,
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
     * Store a new customer order with its line items.
     */
    public function store(StoreCustomerOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $lines = $data['lines'];
        unset($data['lines']);

        $enriched = $this->service->enrichLinesWithVat($lines);
        $data['number'] = $this->service->nextNumber();
        $data['total_amount'] = $this->service->computeTotal($enriched);

        DB::transaction(function () use ($data, $lines): void {
            /** @var CustomerOrder $order */
            $order = CustomerOrder::create($data);
            $this->saveLines($order, $lines);
        });

        return back()->with('success', 'Customer order created successfully.');
    }

    /**
     * Update an existing customer order, replacing all line items.
     */
    public function update(UpdateCustomerOrderRequest $request, CustomerOrder $order): RedirectResponse
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

        return back()->with('success', 'Customer order updated successfully.');
    }

    /**
     * Delete a customer order (lines cascade via DB FK).
     */
    public function destroy(CustomerOrder $order): RedirectResponse
    {
        $order->delete();

        return back()->with('success', 'Customer order deleted.');
    }

    /**
     * Download the customer order as a PDF file.
     */
    public function downloadPdf(CustomerOrder $order): HttpResponse
    {
        $order->load([
            'client',
            'lines.article.vatRate',
            'lines.supplier:id,name',
        ]);

        $company = CompanySetting::first();
        $logoUrl = $company?->logo_path
            ? route('file.private', ['path' => $company->logo_path])
            : null;

        $vatBreakdown = $this->buildVatBreakdown($order->lines->all());

        $pdf = Pdf::loadView('pdf.customer_order', compact('order', 'company', 'logoUrl', 'vatBreakdown'))
            ->setPaper('a4');

        $filename = 'Order-'.str_pad((string) $order->number, 5, '0', STR_PAD_LEFT).'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Convert a closed customer order to one SupplierOrder per supplier.
     * Returns JSON with a summary of the created supplier orders.
     */
    public function convertToSupplierOrders(CustomerOrder $order): RedirectResponse
    {
        if ($order->status !== 'closed') {
            return back()->with('error', 'Only closed orders can be converted to supplier orders.');
        }

        if ($order->supplierOrders()->exists()) {
            return back()->with('error', 'This order has already been converted to supplier orders.');
        }

        $order->load('lines.article.vatRate');

        $supplierOrderCount = $order->lines->filter(fn ($l) => $l->supplier_id !== null)
            ->pluck('supplier_id')
            ->unique()
            ->count();

        if ($supplierOrderCount === 0) {
            return back()->with('error', 'No lines have a supplier assigned. Assign suppliers to lines before converting.');
        }

        $supplierOrders = $this->service->convertToSupplierOrders($order);

        $count = $supplierOrders->count();

        return back()->with('success', "Created {$count} supplier order(s) from this order.");
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * Persist line items for an order, assigning sort_order automatically.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function saveLines(CustomerOrder $order, array $lines): void
    {
        foreach ($lines as $index => $lineData) {
            CustomerOrderLine::create([
                'customer_order_id' => $order->id,
                'article_id' => $lineData['article_id'],
                'supplier_id' => $lineData['supplier_id'] ?? null,
                'quantity' => $lineData['quantity'],
                'unit_price' => $lineData['unit_price'],
                'cost_price' => $lineData['cost_price'] ?? null,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * Group line totals by VAT rate for the PDF breakdown section.
     *
     * @param  CustomerOrderLine[]  $lines  eager-loaded with article.vatRate
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

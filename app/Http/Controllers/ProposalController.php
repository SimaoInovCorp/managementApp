<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProposalRequest;
use App\Http\Requests\UpdateProposalRequest;
use App\Http\Resources\ProposalResource;
use App\Models\Article;
use App\Models\CompanySetting;
use App\Models\Entity;
use App\Models\Proposal;
use App\Models\ProposalLine;
use App\Services\ProposalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages the commercial proposals lifecycle:
 * listing, create/edit/delete, PDF export, and conversion to CustomerOrder.
 */
class ProposalController extends Controller
{
    public function __construct(private readonly ProposalService $service) {}

    /**
     * List all proposals with client name and lines.
     * Eager loads everything needed for both the list and the edit form.
     */
    public function index(Request $request): Response
    {
        $proposals = Proposal::with([
            'client:id,name,type',
            'lines.article.vatRate',
            'lines.supplier:id,name',
            'customerOrder:id,proposal_id,number',
        ])
            ->orderByDesc('proposal_date')
            ->orderByDesc('number')
            ->paginate(15);

        // Clients: entities with type client or both
        $clients = Entity::whereIn('type', ['client', 'both'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        // Suppliers: entities with type supplier or both
        $suppliers = Entity::whereIn('type', ['supplier', 'both'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        // Active articles with their VAT rates for line item selection
        $articles = Article::with('vatRate:id,name,rate')
            ->where('status', 'active')
            ->orderBy('reference')
            ->get(['id', 'reference', 'name', 'price', 'vat_id']);

        return Inertia::render('proposals/Index', [
            'proposals' => ProposalResource::collection($proposals),
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
     * Store a new proposal with its line items.
     * Auto-sets validity_date to +30 days if not provided.
     */
    public function store(StoreProposalRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $lines = $data['lines'];
        unset($data['lines']);

        // Auto-set validity date if not provided
        if (empty($data['validity_date'])) {
            $data['validity_date'] = Carbon::parse($data['proposal_date'])->addDays(30);
        }

        $data['number'] = $this->service->nextNumber();
        $data['total_amount'] = $this->service->computeTotal(
            $this->enrichLinesWithVat($lines)
        );

        DB::transaction(function () use ($data, $lines): void {
            /** @var Proposal $proposal */
            $proposal = Proposal::create($data);
            $this->saveLines($proposal, $lines);
        });

        return back()->with('success', 'Proposal created successfully.');
    }

    /**
     * Update an existing proposal, replacing all line items.
     */
    public function update(UpdateProposalRequest $request, Proposal $proposal): RedirectResponse
    {
        $data = $request->validated();
        $lines = $data['lines'];
        unset($data['lines']);

        if (empty($data['validity_date'])) {
            $data['validity_date'] = Carbon::parse($data['proposal_date'])->addDays(30);
        }

        $data['total_amount'] = $this->service->computeTotal(
            $this->enrichLinesWithVat($lines)
        );

        DB::transaction(function () use ($proposal, $data, $lines): void {
            $proposal->update($data);
            // Delete and re-insert lines for simplicity (ERP scale)
            $proposal->lines()->delete();
            $this->saveLines($proposal, $lines);
        });

        return back()->with('success', 'Proposal updated successfully.');
    }

    /**
     * Delete a proposal (lines cascade via DB FK).
     */
    public function destroy(Proposal $proposal): RedirectResponse
    {
        $proposal->delete();

        return back()->with('success', 'Proposal deleted.');
    }

    /**
     * Download the proposal as a PDF file.
     */
    public function downloadPdf(Proposal $proposal): HttpResponse
    {
        $proposal->load([
            'client',
            'lines.article.vatRate',
            'lines.supplier:id,name',
        ]);

        $company = CompanySetting::first();
        $logoUrl = $company?->logo_path
            ? route('file.private', ['path' => $company->logo_path])
            : null;

        // Build VAT breakdown for footer summary
        $vatBreakdown = $this->buildVatBreakdown($proposal->lines->all());

        $pdf = Pdf::loadView('pdf.proposal', compact('proposal', 'company', 'logoUrl', 'vatBreakdown'))
            ->setPaper('a4');

        $filename = 'Proposal-'.str_pad((string) $proposal->number, 5, '0', STR_PAD_LEFT).'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Convert a closed proposal to a CustomerOrder in draft status.
     */
    public function convertToOrder(Proposal $proposal): RedirectResponse
    {
        if ($proposal->status !== 'closed') {
            return back()->with('error', 'Only closed proposals can be converted to an order.');
        }

        if ($proposal->customerOrder !== null) {
            return back()->with('error', 'This proposal has already been converted to an order.');
        }

        $proposal->load('lines');
        $order = $this->service->convertToCustomerOrder($proposal);

        return back()->with('success', "Proposal converted to Customer Order #{$order->number}.");
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * Persist line items for a proposal, assigning sort_order automatically.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function saveLines(Proposal $proposal, array $lines): void
    {
        foreach ($lines as $index => $lineData) {
            ProposalLine::create([
                'proposal_id' => $proposal->id,
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
     * Enrich a lines array with the vat_rate from the article's VAT relation.
     * Used by computeTotal which needs vat_rate per line.
     *
     * @param  array<int, array<string, mixed>>  $lines  raw lines from request
     * @return array<int, array<string, mixed>>
     */
    private function enrichLinesWithVat(array $lines): array
    {
        // Load all referenced articles with their VAT rates in one query (N+1 prevention)
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
     * Group line totals by VAT rate for the PDF breakdown section.
     *
     * @param  ProposalLine[]  $lines  eager-loaded with article.vatRate
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

        ksort($breakdown);

        return array_values($breakdown);
    }
}

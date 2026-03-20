<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierInvoiceRequest;
use App\Http\Requests\UpdateSupplierInvoiceRequest;
use App\Http\Resources\SupplierInvoiceResource;
use App\Models\Entity;
use App\Models\SupplierInvoice;
use App\Models\SupplierOrder;
use App\Services\SupplierInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages supplier invoices, document uploads, and payment confirmation emails.
 */
class SupplierInvoiceController extends Controller
{
    public function __construct(private readonly SupplierInvoiceService $service) {}

    /**
     * List all supplier invoices with supplier and order info.
     */
    public function index(): Response
    {
        $invoices = SupplierInvoice::with([
            'supplier:id,name,type',
            'supplierOrder:id,number',
        ])
            ->orderByDesc('invoice_date')
            ->orderByDesc('number')
            ->paginate(15);

        $suppliers = Entity::whereIn('type', ['supplier', 'both'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $supplierOrders = SupplierOrder::orderByDesc('number')
            ->get(['id', 'number', 'status']);

        return Inertia::render('financial/SupplierInvoices', [
            'invoices' => SupplierInvoiceResource::collection($invoices),
            'suppliers' => $suppliers,
            'supplierOrders' => $supplierOrders,
        ]);
    }

    /**
     * Store a new supplier invoice.
     */
    public function store(StoreSupplierInvoiceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['number'] = $this->service->nextNumber();

        // Handle optional document upload
        if ($request->hasFile('document')) {
            $data['document_path'] = $this->service->storeDocument($request->file('document'));
        }

        SupplierInvoice::create($data);

        return back()->with('success', 'Supplier invoice created successfully.');
    }

    /**
     * Update an existing supplier invoice.
     */
    public function update(UpdateSupplierInvoiceRequest $request, SupplierInvoice $invoice): RedirectResponse
    {
        $data = $request->validated();

        // Handle optional document replacement
        if ($request->hasFile('document')) {
            $data['document_path'] = $this->service->storeDocument($request->file('document'));
        }

        $invoice->update($data);

        return back()->with('success', 'Supplier invoice updated successfully.');
    }

    /**
     * Delete a supplier invoice.
     */
    public function destroy(SupplierInvoice $invoice): RedirectResponse
    {
        $invoice->delete();

        return back()->with('success', 'Supplier invoice deleted.');
    }

    /**
     * Mark invoice as paid, optionally store payment proof, and send confirmation email.
     * Called when user clicks "Confirm Payment" in the dialog.
     */
    public function sendPaymentConfirmation(Request $request, SupplierInvoice $invoice): RedirectResponse
    {
        $request->validate([
            'payment_proof' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
            'send_email' => ['nullable', 'boolean'],
        ]);

        // Load supplier relation (needed for email address)
        $invoice->load('supplier');

        $this->service->markAsPaid($invoice, $request->file('payment_proof') ?: null);

        if ($request->boolean('send_email', true)) {
            $this->service->sendPaymentConfirmation($invoice);
        }

        return back()->with('success', 'Invoice marked as paid.'.($request->boolean('send_email', true) ? ' Payment confirmation sent to supplier.' : ''));
    }
}

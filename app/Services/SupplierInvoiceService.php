<?php

namespace App\Services;

use App\Models\SupplierInvoice;
use App\Notifications\PaymentConfirmationNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Storage;

/**
 * Encapsulates supplier invoice business logic.
 */
class SupplierInvoiceService
{
    /**
     * Return the next sequential supplier invoice number.
     */
    public function nextNumber(): int
    {
        return (int) (SupplierInvoice::max('number') ?? 0) + 1;
    }

    /**
     * Store an uploaded invoice document in private storage.
     * Returns the relative path.
     */
    public function storeDocument(UploadedFile $file): string
    {
        return $file->store('invoices/documents', 'private');
    }

    /**
     * Store payment proof file and update invoice status to 'paid'.
     * Returns the stored path.
     */
    public function markAsPaid(SupplierInvoice $invoice, ?UploadedFile $proof): ?string
    {
        $proofPath = null;

        if ($proof) {
            $proofPath = $proof->store('invoices/proofs', 'private');
            $invoice->payment_proof_path = $proofPath;
        }

        $invoice->status = 'paid';
        $invoice->save();

        return $proofPath;
    }

    /**
     * Send payment confirmation email to the supplier.
     * The supplier's email is already decrypted via the encrypted cast.
     */
    public function sendPaymentConfirmation(SupplierInvoice $invoice): void
    {
        $supplierEmail = $invoice->supplier?->email;

        if (! $supplierEmail) {
            return;
        }

        // Use AnonymousNotifiable to route to the supplier's email address
        // without requiring the Entity model to implement Notifiable.
        (new AnonymousNotifiable)
            ->route('mail', $supplierEmail)
            ->notify(new PaymentConfirmationNotification($invoice));
    }
}

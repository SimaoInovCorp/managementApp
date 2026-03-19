<?php

namespace App\Notifications;

use App\Models\SupplierInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Sent to a supplier when a payment is confirmed for their invoice.
 * Uses a simple Laravel MailMessage — the `$notifiable` is an ad-hoc
 * object with the supplier's decrypted email set as the routeNotificationFor.
 */
class PaymentConfirmationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly SupplierInvoice $invoice,
    ) {}

    /** @return string[] */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name');
        $number = str_pad((string) $this->invoice->number, 5, '0', STR_PAD_LEFT);
        $supplierName = $this->invoice->supplier?->name ?? 'Supplier';
        $invoiceDate = $this->invoice->invoice_date->format('d/m/Y');
        $totalFormatted = number_format((float) $this->invoice->total_amount, 2, '.', ',').' EUR';

        $message = (new MailMessage)
            ->subject("Payment Confirmation — Invoice #{$number}")
            ->markdown('emails.payment_confirmation', [
                'appName'        => $appName,
                'supplierName'   => $supplierName,
                'invoiceNumber'  => $number,
                'invoiceDate'    => $invoiceDate,
                'totalFormatted' => $totalFormatted,
            ]);

        // Attach payment proof if it exists
        if ($this->invoice->payment_proof_path && Storage::disk('private')->exists($this->invoice->payment_proof_path)) {
            $message->attach(
                Storage::disk('private')->path($this->invoice->payment_proof_path),
                ['as' => 'payment_proof.'.pathinfo($this->invoice->payment_proof_path, PATHINFO_EXTENSION)]
            );
        }

        return $message;
    }
}

@component('mail::message')

# Payment Confirmation — Invoice #{{ $invoiceNumber }}

Dear {{ $supplierName }},

We are pleased to confirm that payment has been processed for the following invoice:

@component('mail::table')
| | |
|:---|:---|
| **Invoice Number** | #{{ $invoiceNumber }} |
| **Invoice Date** | {{ $invoiceDate }} |
| **Amount Paid** | {{ $totalFormatted }} |
@endcomponent

Please find the payment confirmation document attached to this email, if applicable.

Should you have any questions regarding this payment, please do not hesitate to contact us.

Thank you for your services and continued partnership.

Kind regards,
**{{ $appName }}**

@endcomponent

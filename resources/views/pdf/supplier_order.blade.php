<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Order #{{ str_pad($order->number, 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #222; background: #fff; }

        /* ── Header ── */
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; border-bottom: 2px solid #7c3aed; padding-bottom: 16px; }
        .company-info h1 { font-size: 18px; font-weight: 700; color: #7c3aed; margin-bottom: 4px; }
        .company-info p { font-size: 10px; color: #555; line-height: 1.5; }
        .logo { max-height: 64px; max-width: 160px; }
        .doc-meta { text-align: right; }
        .doc-meta h2 { font-size: 16px; font-weight: 700; text-transform: uppercase; color: #7c3aed; margin-bottom: 6px; }
        .doc-meta .number { font-size: 22px; font-weight: 800; color: #111; }
        .doc-meta p { font-size: 10px; color: #555; margin-top: 3px; }

        /* ── Two-column info block ── */
        .info-grid { display: flex; gap: 24px; margin-bottom: 20px; }
        .info-box { flex: 1; border: 1px solid #ddd; border-radius: 4px; padding: 10px 12px; }
        .info-box h4 { font-size: 9px; text-transform: uppercase; letter-spacing: .06em; color: #888; margin-bottom: 6px; }
        .info-box p { font-size: 10.5px; line-height: 1.6; }
        .info-box strong { font-weight: 600; }

        /* ── Status badge ── */
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; }
        .badge-draft { background: #fef3c7; color: #92400e; }
        .badge-closed { background: #ede9fe; color: #4c1d95; }

        /* ── Lines table ── */
        table.lines { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.lines th { background: #7c3aed; color: #fff; font-size: 9px; text-transform: uppercase; letter-spacing: .05em; padding: 6px 8px; text-align: left; }
        table.lines th.r, table.lines td.r { text-align: right; }
        table.lines td { padding: 7px 8px; border-bottom: 1px solid #e5e7eb; font-size: 10.5px; vertical-align: top; }
        table.lines tr:last-child td { border-bottom: none; }
        table.lines tr:nth-child(even) td { background: #f9fafb; }
        .ref { color: #6b7280; font-size: 9.5px; }

        /* ── Totals ── */
        .totals-wrap { display: flex; justify-content: flex-end; margin-bottom: 20px; }
        table.totals { width: 260px; border-collapse: collapse; }
        table.totals td { padding: 5px 8px; font-size: 10.5px; }
        table.totals td.label { color: #6b7280; }
        table.totals td.value { text-align: right; font-weight: 600; }
        table.totals tr.grand td { border-top: 2px solid #7c3aed; font-size: 13px; font-weight: 800; color: #7c3aed; padding-top: 8px; }

        /* ── VAT breakdown ── */
        .vat-section { margin-bottom: 24px; }
        .vat-section h4 { font-size: 9px; text-transform: uppercase; letter-spacing: .06em; color: #888; margin-bottom: 6px; }
        table.vat-breakdown { border-collapse: collapse; }
        table.vat-breakdown td, table.vat-breakdown th { padding: 4px 10px; font-size: 10px; }
        table.vat-breakdown th { background: #f3f4f6; color: #374151; font-weight: 600; }
        table.vat-breakdown td { border-bottom: 1px solid #e5e7eb; }
        table.vat-breakdown td.r { text-align: right; }

        /* ── Notes ── */
        .notes { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 10px 12px; margin-bottom: 20px; font-size: 10.5px; color: #374151; }
        .notes h4 { font-size: 9px; text-transform: uppercase; letter-spacing: .06em; color: #888; margin-bottom: 4px; }

        /* ── Footer ── */
        .footer { border-top: 1px solid #e5e7eb; padding-top: 10px; font-size: 9px; color: #9ca3af; text-align: center; margin-top: auto; }
    </style>
</head>
<body>

    {{-- ── Header ─────────────────────────────────────────────────────── --}}
    <div class="header">
        <div class="company-info">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $company?->name }}" class="logo" style="margin-bottom:8px;">
            @endif
            <h1>{{ $company?->name ?? 'Company Name' }}</h1>
            @if($company)
                <p>
                    {{ $company->address }}<br>
                    {{ $company->postal_code }} {{ $company->locality }}<br>
                    @if($company->tax_number) NIF: {{ $company->tax_number }} @endif
                </p>
            @endif
        </div>
        <div class="doc-meta">
            <h2>Supplier Order</h2>
            <div class="number">#{{ str_pad($order->number, 5, '0', STR_PAD_LEFT) }}</div>
            <p>Date: <strong>{{ $order->order_date->format('d/m/Y') }}</strong></p>
            <p style="margin-top:6px;">
                <span class="badge badge-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
            </p>
        </div>
    </div>

    {{-- ── Supplier Info ────────────────────────────────────────────────── --}}
    <div class="info-grid">
        <div class="info-box">
            <h4>Supplier</h4>
            <p>
                <strong>{{ $order->supplier->name }}</strong><br>
                @if($order->supplier->address){{ $order->supplier->address }}<br>@endif
                @if($order->supplier->postal_code || $order->supplier->locality)
                    {{ $order->supplier->postal_code }} {{ $order->supplier->locality }}<br>
                @endif
                @if($order->supplier->nif) NIF: {{ $order->supplier->nif }} @endif
            </p>
        </div>
        <div class="info-box" style="flex:0 0 200px;">
            <h4>Reference</h4>
            <p>
                Order No.: <strong>{{ str_pad($order->number, 5, '0', STR_PAD_LEFT) }}</strong><br>
                Date: {{ $order->order_date->format('d/m/Y') }}
                @if($order->customer_order_id && $order->customerOrder)
                    <br>From Order: #{{ str_pad($order->customerOrder->number, 5, '0', STR_PAD_LEFT) }}
                @endif
            </p>
        </div>
    </div>

    {{-- ── Line Items ────────────────────────────────────────────────────── --}}
    @php
        $subtotalExVat = 0;
        $totalIncVat   = 0;
    @endphp

    <table class="lines">
        <thead>
            <tr>
                <th style="width:14%">Ref.</th>
                <th>Description</th>
                <th class="r" style="width:9%">Qty</th>
                <th class="r" style="width:12%">Unit Price</th>
                <th class="r" style="width:8%">VAT %</th>
                <th class="r" style="width:13%">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->lines as $line)
                @php
                    $vatRate   = $line->article?->vatRate ? (float) $line->article->vatRate->rate : 0;
                    $lineBase  = (float) $line->quantity * (float) $line->unit_price;
                    $lineTotal = $lineBase * (1 + $vatRate / 100);
                    $subtotalExVat += $lineBase;
                    $totalIncVat   += $lineTotal;
                @endphp
                <tr>
                    <td class="ref">{{ $line->article?->reference }}</td>
                    <td>{{ $line->article?->name }}</td>
                    <td class="r">{{ rtrim(rtrim(number_format((float)$line->quantity, 2, '.', ''), '0'), '.') ?: '0' }}</td>
                    <td class="r">{{ number_format((float)$line->unit_price, 2, ',', '.') }} €</td>
                    <td class="r">{{ number_format($vatRate, 0) }}%</td>
                    <td class="r">{{ number_format($lineTotal, 2, ',', '.') }} €</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ── Totals ────────────────────────────────────────────────────────── --}}
    <div class="totals-wrap">
        <table class="totals">
            <tr>
                <td class="label">Subtotal (ex-VAT)</td>
                <td class="value">{{ number_format($subtotalExVat, 2, ',', '.') }} €</td>
            </tr>
            @foreach($vatBreakdown as $vb)
                <tr>
                    <td class="label">VAT {{ number_format($vb['rate'], 0) }}%</td>
                    <td class="value">{{ number_format($vb['vat_amount'], 2, ',', '.') }} €</td>
                </tr>
            @endforeach
            <tr class="grand">
                <td class="label">Total (inc. VAT)</td>
                <td class="value">{{ number_format($totalIncVat, 2, ',', '.') }} €</td>
            </tr>
        </table>
    </div>

    {{-- ── VAT Breakdown ─────────────────────────────────────────────────── --}}
    @if(!empty($vatBreakdown))
    <div class="vat-section">
        <h4>VAT Summary</h4>
        <table class="vat-breakdown">
            <tr>
                <th>Rate</th>
                <th style="text-align:right">Base (ex-VAT)</th>
                <th style="text-align:right">VAT Amount</th>
            </tr>
            @foreach($vatBreakdown as $vb)
                <tr>
                    <td>{{ number_format($vb['rate'], 0) }}%</td>
                    <td class="r">{{ number_format($vb['base'], 2, ',', '.') }} €</td>
                    <td class="r">{{ number_format($vb['vat_amount'], 2, ',', '.') }} €</td>
                </tr>
            @endforeach
        </table>
    </div>
    @endif

    {{-- ── Notes ─────────────────────────────────────────────────────────── --}}
    @if($order->notes)
    <div class="notes">
        <h4>Notes</h4>
        <p>{{ $order->notes }}</p>
    </div>
    @endif

    {{-- ── Footer ─────────────────────────────────────────────────────────── --}}
    <div class="footer">
        {{ $company?->name }} &mdash; {{ $company?->address }}, {{ $company?->postal_code }} {{ $company?->locality }}
        @if($company?->tax_number) &mdash; NIF: {{ $company->tax_number }} @endif
    </div>

</body>
</html>
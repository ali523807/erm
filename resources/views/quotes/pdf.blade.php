<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $quote->quote_number }}</title>
    <style>
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.45; margin: 0; }
        .header { border-bottom: 2px solid #111827; margin-bottom: 24px; padding-bottom: 16px; }
        .brand { font-size: 22px; font-weight: 700; }
        .muted { color: #6b7280; }
        .grid { display: table; width: 100%; }
        .col { display: table-cell; vertical-align: top; width: 50%; }
        .right { text-align: right; }
        h1 { font-size: 26px; margin: 0 0 6px; }
        h2 { font-size: 14px; margin: 0 0 8px; text-transform: uppercase; }
        table { border-collapse: collapse; margin-top: 20px; width: 100%; }
        th { background: #f3f4f6; color: #374151; font-size: 11px; padding: 9px; text-align: left; text-transform: uppercase; }
        td { border-bottom: 1px solid #e5e7eb; padding: 9px; }
        .totals { margin-left: auto; margin-top: 22px; width: 42%; }
        .totals td { border-bottom: none; padding: 5px 0; }
        .totals .grand td { border-top: 2px solid #111827; font-size: 15px; font-weight: 700; padding-top: 8px; }
        .notes { background: #f9fafb; margin-top: 24px; padding: 12px; }
    </style>
</head>
<body>
    @php($money = app(\App\Support\Money::class))
    <div class="header grid">
        <div class="col">
            <div class="brand">{{ auth()->user()->currentCompany?->name ?? 'RentalHook' }}</div>
            <div class="muted">{{ auth()->user()->currentCompany?->email }}</div>
        </div>
        <div class="col right">
            <h1>Quote</h1>
            <div>{{ $quote->quote_number }}</div>
            <div class="muted">Status: {{ str($quote->status)->headline() }}</div>
        </div>
    </div>

    <div class="grid">
        <div class="col">
            <h2>Quote To</h2>
            <strong>{{ $quote->customer?->company_name }}</strong><br>
            {{ $quote->customer?->contact_person }}<br>
            {{ $quote->customer?->email }}<br>
            {{ $quote->customer?->phone }}
        </div>
        <div class="col right">
            <h2>Quote Details</h2>
            <div>Quote Date: {{ $quote->quote_date?->format('Y-m-d') }}</div>
            <div>Valid Until: {{ $quote->valid_until?->format('Y-m-d') ?: '-' }}</div>
            <div>Rental: {{ $quote->rental_start_date?->format('Y-m-d') }} - {{ $quote->rental_end_date?->format('Y-m-d') }}</div>
        </div>
    </div>

    <table>
        <thead>
        <tr>
            <th>Equipment</th>
            <th>Period</th>
            <th>Duration</th>
            <th class="right">Rate</th>
            <th class="right">Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($quote->items as $item)
            <tr>
                <td>
                    <strong>{{ $item->product?->name }}</strong><br>
                    <span class="muted">{{ $item->product?->equipment_code }}</span>
                </td>
                <td>{{ $item->start_date?->format('Y-m-d') }} - {{ $item->end_date?->format('Y-m-d') }}</td>
                <td>1 asset x {{ number_format((float) $item->no_of_duration, 2) }} {{ $item->duration_type }}</td>
                <td class="right">{{ $money->format($item->rate, $quote->currency) }}</td>
                <td class="right">{{ $money->format($item->line_total, $quote->currency) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Subtotal</td>
            <td class="right">{{ $money->format($quote->subtotal, $quote->currency) }}</td>
        </tr>
        <tr>
            <td>Tax</td>
            <td class="right">{{ $money->format($quote->tax_amount, $quote->currency) }}</td>
        </tr>
        <tr>
            <td>Discount</td>
            <td class="right">-{{ $money->format($quote->discount_amount, $quote->currency) }}</td>
        </tr>
        <tr class="grand">
            <td>Total</td>
            <td class="right">{{ $money->format($quote->total_amount, $quote->currency) }}</td>
        </tr>
    </table>

    @if($quote->terms || $quote->notes)
        <div class="notes">
            @if($quote->terms)
                <strong>Terms</strong><br>
                {{ $quote->terms }}<br><br>
            @endif
            @if($quote->notes)
                <strong>Notes</strong><br>
                {{ $quote->notes }}
            @endif
        </div>
    @endif
</body>
</html>

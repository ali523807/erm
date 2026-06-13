<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $payment->receiptNumber() }}</title>
    <style>
        body {
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.45;
            margin: 0;
        }

        .header {
            border-bottom: 2px solid #111827;
            margin-bottom: 24px;
            padding-bottom: 16px;
        }

        .brand {
            font-size: 22px;
            font-weight: 700;
        }

        .muted {
            color: #6b7280;
        }

        .grid {
            display: table;
            width: 100%;
        }

        .col {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }

        .right {
            text-align: right;
        }

        h1 {
            font-size: 26px;
            margin: 0 0 6px;
        }

        h2 {
            font-size: 14px;
            margin: 0 0 8px;
            text-transform: uppercase;
        }

        table {
            border-collapse: collapse;
            margin-top: 24px;
            width: 100%;
        }

        th {
            background: #f3f4f6;
            color: #374151;
            font-size: 11px;
            padding: 9px;
            text-align: left;
            text-transform: uppercase;
        }

        td {
            border-bottom: 1px solid #e5e7eb;
            padding: 9px;
        }

        .amount-box {
            border: 2px solid #111827;
            margin-top: 26px;
            padding: 16px;
            text-align: center;
        }

        .amount {
            font-size: 28px;
            font-weight: 700;
        }

        .notes {
            background: #f9fafb;
            margin-top: 24px;
            padding: 12px;
        }
    </style>
</head>
<body>
    @php
        $money = app(\App\Support\Money::class);
        $company = auth()->user()?->currentCompany ?? $payment->invoice?->company;
    @endphp
    <div class="header grid">
        <div class="col">
            <div class="brand">{{ $company?->name ?? 'ERM Cloud' }}</div>
            <div class="muted">{{ $company?->email }}</div>
        </div>
        <div class="col right">
            <h1>Payment Receipt</h1>
            <div>{{ $payment->receiptNumber() }}</div>
            <div class="muted">{{ $payment->payment_date?->format('Y-m-d') }}</div>
        </div>
    </div>

    <div class="grid">
        <div class="col">
            <h2>Received From</h2>
            <strong>{{ $payment->invoice?->customer?->company_name }}</strong><br>
            {{ $payment->invoice?->customer?->contact_person }}<br>
            {{ $payment->invoice?->customer?->email }}<br>
            {{ $payment->invoice?->customer?->phone }}
        </div>
        <div class="col right">
            <h2>Applied To</h2>
            <div>Invoice: {{ $payment->invoice?->invoice_number }}</div>
            <div>Rental: RTN-{{ $payment->invoice?->rental_id }}</div>
            <div>Method: {{ str($payment->method)->headline() }}</div>
            <div>Reference: {{ $payment->reference ?: '-' }}</div>
        </div>
    </div>

    <div class="amount-box">
        <div class="muted">Amount Received</div>
        <div class="amount">{{ $money->format($payment->amount, $payment->invoice?->currency) }}</div>
    </div>

    <table>
        <thead>
        <tr>
            <th>Invoice</th>
            <th>Invoice Total</th>
            <th>Paid To Date</th>
            <th>Balance Due</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>{{ $payment->invoice?->invoice_number }}</td>
            <td>{{ $money->format($payment->invoice?->total_amount, $payment->invoice?->currency) }}</td>
            <td>{{ $money->format($payment->invoice?->paid_amount, $payment->invoice?->currency) }}</td>
            <td>{{ $money->format($payment->invoice?->balance_due, $payment->invoice?->currency) }}</td>
        </tr>
        </tbody>
    </table>

    @if($payment->notes)
        <div class="notes">
            <strong>Notes</strong><br>
            {{ $payment->notes }}
        </div>
    @endif
</body>
</html>

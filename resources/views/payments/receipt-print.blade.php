<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $payment->receiptNumber() }}</title>
    <style>
        body {
            background: #f3f4f6;
            color: #111827;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 24px;
        }

        .toolbar {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            margin: 0 auto 16px;
            max-width: 820px;
        }

        .btn {
            background: #111827;
            border: 1px solid #111827;
            border-radius: 6px;
            color: #fff;
            cursor: pointer;
            padding: 9px 14px;
            text-decoration: none;
        }

        .btn.secondary {
            background: #fff;
            color: #111827;
        }

        .receipt {
            background: #fff;
            box-shadow: 0 12px 35px rgba(15, 23, 42, .12);
            margin: 0 auto;
            max-width: 820px;
            padding: 40px;
        }

        .header {
            align-items: flex-start;
            border-bottom: 2px solid #111827;
            display: flex;
            justify-content: space-between;
            margin-bottom: 28px;
            padding-bottom: 18px;
        }

        .brand {
            font-size: 24px;
            font-weight: 700;
        }

        .muted {
            color: #6b7280;
        }

        h1 {
            font-size: 30px;
            margin: 0 0 6px;
            text-align: right;
        }

        h2 {
            font-size: 13px;
            letter-spacing: .05em;
            margin: 0 0 8px;
            text-transform: uppercase;
        }

        .grid {
            display: grid;
            gap: 28px;
            grid-template-columns: 1fr 1fr;
        }

        .right {
            text-align: right;
        }

        .amount-box {
            border: 2px solid #111827;
            margin: 28px 0;
            padding: 18px;
            text-align: center;
        }

        .amount {
            font-size: 34px;
            font-weight: 700;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th {
            background: #f3f4f6;
            color: #374151;
            font-size: 12px;
            padding: 10px;
            text-align: left;
            text-transform: uppercase;
        }

        td {
            border-bottom: 1px solid #e5e7eb;
            padding: 10px;
        }

        .notes {
            background: #f9fafb;
            margin-top: 24px;
            padding: 14px;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .toolbar {
                display: none;
            }

            .receipt {
                box-shadow: none;
                max-width: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    @php
        $money = app(\App\Support\Money::class);
        $company = auth()->user()?->currentCompany ?? $payment->invoice?->company;
    @endphp
    <div class="toolbar">
        <a class="btn secondary" href="{{ route('payments.receipt.download', $payment) }}">Download PDF</a>
        <button class="btn" type="button" onclick="window.print()">Print Receipt</button>
    </div>

    <main class="receipt">
        <div class="header">
            <div>
                <div class="brand">{{ $company?->name ?? 'ERM Cloud' }}</div>
                <div class="muted">{{ $company?->email }}</div>
            </div>
            <div>
                <h1>Payment Receipt</h1>
                <div>{{ $payment->receiptNumber() }}</div>
                <div class="muted">{{ $payment->payment_date?->format('Y-m-d') }}</div>
            </div>
        </div>

        <div class="grid">
            <section>
                <h2>Received From</h2>
                <strong>{{ $payment->invoice?->customer?->company_name }}</strong><br>
                {{ $payment->invoice?->customer?->contact_person }}<br>
                {{ $payment->invoice?->customer?->email }}<br>
                {{ $payment->invoice?->customer?->phone }}
            </section>
            <section class="right">
                <h2>Applied To</h2>
                <div>Invoice: {{ $payment->invoice?->invoice_number }}</div>
                <div>Rental: RTN-{{ $payment->invoice?->rental_id }}</div>
                <div>Method: {{ str($payment->method)->headline() }}</div>
                <div>Reference: {{ $payment->reference ?: '-' }}</div>
            </section>
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
    </main>
</body>
</html>

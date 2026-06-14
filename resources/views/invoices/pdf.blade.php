<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
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
            margin-top: 20px;
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

        .totals {
            margin-left: auto;
            margin-top: 22px;
            width: 42%;
        }

        .totals td {
            border-bottom: none;
            padding: 5px 0;
        }

        .totals .grand td {
            border-top: 2px solid #111827;
            font-size: 15px;
            font-weight: 700;
            padding-top: 8px;
        }

        .notes {
            background: #f9fafb;
            margin-top: 24px;
            padding: 12px;
        }
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
            <h1>Invoice</h1>
            <div>{{ $invoice->invoice_number }}</div>
            <div class="muted">Status: {{ str($invoice->status)->headline() }}</div>
        </div>
    </div>

    <div class="grid">
        <div class="col">
            <h2>Bill To</h2>
            <strong>{{ $invoice->customer?->company_name }}</strong><br>
            {{ $invoice->customer?->contact_person }}<br>
            {{ $invoice->customer?->email }}<br>
            {{ $invoice->customer?->phone }}
        </div>
        <div class="col right">
            <h2>Invoice Details</h2>
            <div>Rental: RTN-{{ $invoice->rental_id }}</div>
            <div>Invoice Date: {{ $invoice->invoice_date?->format('Y-m-d') }}</div>
            <div>Due Date: {{ $invoice->due_date?->format('Y-m-d') ?: '-' }}</div>
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
        @foreach($invoice->rental->rentalItems as $item)
            <tr>
                <td>
                    <strong>{{ $item->product?->name }}</strong><br>
                    <span class="muted">{{ $item->product?->equipment_code }}</span>
                </td>
                <td>{{ $item->start_date }} - {{ $item->end_date }}</td>
                <td>{{ number_format((float) $item->no_of_duration, 2) }} {{ $item->duration_type }}</td>
                <td class="right">{{ $money->format($item->rate, $invoice->currency) }}</td>
                <td class="right">{{ $money->format($item->total_price, $invoice->currency) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Subtotal</td>
            <td class="right">{{ $money->format($invoice->subtotal, $invoice->currency) }}</td>
        </tr>
        <tr>
            <td>Tax</td>
            <td class="right">{{ $money->format($invoice->tax_amount, $invoice->currency) }}</td>
        </tr>
        <tr>
            <td>Damage Charges</td>
            <td class="right">{{ $money->format($invoice->damage_amount, $invoice->currency) }}</td>
        </tr>
        <tr>
            <td>Late Fees</td>
            <td class="right">{{ $money->format($invoice->late_fee_amount, $invoice->currency) }}</td>
        </tr>
        <tr>
            <td>Billable Expenses</td>
            <td class="right">{{ $money->format($invoice->billable_expense_amount, $invoice->currency) }}</td>
        </tr>
        <tr>
            <td>Discount</td>
            <td class="right">-{{ $money->format($invoice->discount_amount, $invoice->currency) }}</td>
        </tr>
        <tr class="grand">
            <td>Total</td>
            <td class="right">{{ $money->format($invoice->total_amount, $invoice->currency) }}</td>
        </tr>
        <tr>
            <td>Paid</td>
            <td class="right">{{ $money->format($invoice->paid_amount, $invoice->currency) }}</td>
        </tr>
        <tr>
            <td>Credits</td>
            <td class="right">-{{ $money->format($invoice->creditNotes->where('status', '!=', 'voided')->sum('amount'), $invoice->currency) }}</td>
        </tr>
        <tr>
            <td>Balance Due</td>
            <td class="right">{{ $money->format($invoice->balance_due, $invoice->currency) }}</td>
        </tr>
    </table>

    @if($invoice->notes)
        <div class="notes">
            <strong>Notes</strong><br>
            {{ $invoice->notes }}
        </div>
    @endif
</body>
</html>

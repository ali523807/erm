<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $agreement->agreement_number }}</title>
    <style>
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.45; margin: 0; }
        .header { border-bottom: 2px solid #111827; margin-bottom: 22px; padding-bottom: 14px; }
        .grid { display: table; width: 100%; }
        .col { display: table-cell; vertical-align: top; width: 50%; }
        .right { text-align: right; }
        .brand { font-size: 22px; font-weight: 700; }
        .muted { color: #6b7280; }
        h1 { font-size: 24px; margin: 0 0 6px; }
        h2 { font-size: 14px; margin: 22px 0 8px; text-transform: uppercase; }
        table { border-collapse: collapse; margin-top: 12px; width: 100%; }
        th { background: #f3f4f6; color: #374151; font-size: 11px; padding: 8px; text-align: left; text-transform: uppercase; }
        td { border-bottom: 1px solid #e5e7eb; padding: 8px; }
        .box { background: #f9fafb; border: 1px solid #e5e7eb; margin-top: 10px; padding: 10px; }
        .signature { border-top: 1px solid #111827; margin-top: 42px; padding-top: 6px; width: 44%; }
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
            <h1>Rental Agreement</h1>
            <div>{{ $agreement->agreement_number }}</div>
            <div class="muted">Status: {{ str($agreement->status)->headline() }}</div>
        </div>
    </div>

    <div class="grid">
        <div class="col">
            <h2>Customer</h2>
            <strong>{{ $agreement->rental?->customer?->company_name }}</strong><br>
            {{ $agreement->rental?->customer?->contact_person }}<br>
            {{ $agreement->rental?->customer?->email }}<br>
            {{ $agreement->rental?->customer?->phone }}
        </div>
        <div class="col right">
            <h2>Rental</h2>
            <div>Rental: RTN-{{ $agreement->rental_id }}</div>
            <div>Agreement Date: {{ $agreement->agreement_date?->format('Y-m-d') }}</div>
            <div>Rental Period: {{ $agreement->rental?->rental_start_date?->format('Y-m-d') }} - {{ $agreement->rental?->rental_end_date?->format('Y-m-d') }}</div>
        </div>
    </div>

    <h2>Equipment</h2>
    <table>
        <thead>
        <tr>
            <th>Equipment</th>
            <th>Period</th>
            <th>Duration</th>
            <th class="right">Rate</th>
            <th class="right">Deposit</th>
        </tr>
        </thead>
        <tbody>
        @foreach($agreement->rental->rentalItems as $item)
            <tr>
                <td><strong>{{ $item->product?->name }}</strong><br><span class="muted">{{ $item->product?->equipment_code }}</span></td>
                <td>{{ $item->start_date }} - {{ $item->end_date }}</td>
                <td>{{ number_format((float) $item->no_of_duration, 2) }} {{ $item->duration_type }}</td>
                <td class="right">{{ $money->format($item->rate) }}</td>
                <td class="right">{{ $money->format($item->deposit_amount) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h2>Terms</h2>
    <div class="box">{{ $agreement->terms }}</div>

    <h2>Check-Out Sign-Off</h2>
    <div class="box">
        <strong>Representative:</strong> {{ $agreement->checkout_representative ?: '-' }}<br>
        <strong>ID / License:</strong> {{ $agreement->checkout_id_number ?: '-' }}<br>
        <strong>Checked Out At:</strong> {{ $agreement->checked_out_at?->format('Y-m-d H:i') ?: 'Pending' }}<br>
        <strong>Condition:</strong> {{ $agreement->checkout_condition ?: '-' }}<br>
        <strong>Accessories:</strong> {{ $agreement->checkout_accessories ?: '-' }}
    </div>

    <h2>Return Sign-Off</h2>
    <div class="box">
        <strong>Representative:</strong> {{ $agreement->return_representative ?: '-' }}<br>
        <strong>Returned At:</strong> {{ $agreement->returned_at?->format('Y-m-d H:i') ?: 'Pending' }}<br>
        <strong>Condition:</strong> {{ $agreement->return_condition ?: '-' }}<br>
        <strong>Missing Accessories:</strong> {{ $agreement->return_missing_accessories ?: '-' }}<br>
        <strong>Damage Notes:</strong> {{ $agreement->return_damage_notes ?: '-' }}<br>
        <strong>Damage Charge:</strong> {{ $money->format($agreement->damage_amount) }}
    </div>

    <div class="grid">
        <div class="col">
            <div class="signature">Customer Signature</div>
        </div>
        <div class="col">
            <div class="signature">Company Representative</div>
        </div>
    </div>
</body>
</html>

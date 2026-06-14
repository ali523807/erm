@php($money = app(\App\Support\Money::class))

<div class="header">
    <div>
        <div class="brand">{{ auth()->user()->currentCompany?->name ?? 'RentalHook' }}</div>
        <div class="muted">{{ auth()->user()->currentCompany?->email }}</div>
    </div>
    <div>
        <h1>Rental Agreement</h1>
        <div class="right">{{ $agreement->agreement_number }}</div>
        <div class="muted right">Status: {{ str($agreement->status)->headline() }}</div>
    </div>
</div>

<div class="grid">
    <section>
        <h2>Customer</h2>
        <strong>{{ $agreement->rental?->customer?->company_name }}</strong><br>
        {{ $agreement->rental?->customer?->contact_person }}<br>
        {{ $agreement->rental?->customer?->email }}<br>
        {{ $agreement->rental?->customer?->phone }}
    </section>
    <section class="right">
        <h2>Rental</h2>
        <div>Rental: RTN-{{ $agreement->rental_id }}</div>
        <div>Agreement Date: {{ $agreement->agreement_date?->format('Y-m-d') }}</div>
        <div>Rental Period: {{ $agreement->rental?->rental_start_date?->format('Y-m-d') }} - {{ $agreement->rental?->rental_end_date?->format('Y-m-d') }}</div>
    </section>
</div>

<h2>Equipment</h2>
<table>
    <thead>
    <tr>
        <th>Equipment</th>
        <th>Period</th>
        <th>Duration</th>
        <th>Rate</th>
        <th>Deposit</th>
    </tr>
    </thead>
    <tbody>
    @foreach($agreement->rental->rentalItems as $item)
        <tr>
            <td><strong>{{ $item->product?->name }}</strong><br><span class="muted">{{ $item->product?->equipment_code }}</span></td>
            <td>{{ $item->start_date }} - {{ $item->end_date }}</td>
            <td>{{ number_format((float) $item->no_of_duration, 2) }} {{ $item->duration_type }}</td>
            <td>{{ $money->format($item->rate) }}</td>
            <td>{{ $money->format($item->deposit_amount) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<h2>Terms</h2>
<div class="box">{{ $agreement->terms }}</div>

<div class="grid">
    <section>
        <h2>Check-Out Sign-Off</h2>
        <div class="box">
            <strong>Representative:</strong> {{ $agreement->checkout_representative ?: '-' }}<br>
            <strong>ID / License:</strong> {{ $agreement->checkout_id_number ?: '-' }}<br>
            <strong>Checked Out At:</strong> {{ $agreement->checked_out_at?->format('Y-m-d H:i') ?: 'Pending' }}<br>
            <strong>Condition:</strong> {{ $agreement->checkout_condition ?: '-' }}<br>
            <strong>Accessories:</strong> {{ $agreement->checkout_accessories ?: '-' }}
        </div>
    </section>
    <section>
        <h2>Return Sign-Off</h2>
        <div class="box">
            <strong>Representative:</strong> {{ $agreement->return_representative ?: '-' }}<br>
            <strong>Returned At:</strong> {{ $agreement->returned_at?->format('Y-m-d H:i') ?: 'Pending' }}<br>
            <strong>Condition:</strong> {{ $agreement->return_condition ?: '-' }}<br>
            <strong>Missing Accessories:</strong> {{ $agreement->return_missing_accessories ?: '-' }}<br>
            <strong>Damage Notes:</strong> {{ $agreement->return_damage_notes ?: '-' }}<br>
            <strong>Damage Charge:</strong> {{ $money->format($agreement->damage_amount) }}
        </div>
    </section>
</div>

<div class="grid">
    <div class="signature">Customer Signature</div>
    <div class="signature">Company Representative</div>
</div>

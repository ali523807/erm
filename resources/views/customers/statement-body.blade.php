@php
    $printMode = $printMode ?? false;
    $money = fn ($amount): string => number_format((float) $amount, 2);
    $agingLabels = [
        'current' => 'Current',
        'days_1_30' => '1-30 Days',
        'days_31_60' => '31-60 Days',
        'days_61_90' => '61-90 Days',
        'days_90_plus' => '90+ Days',
    ];
@endphp

<div class="{{ $printMode ? '' : 'row g-3 mb-3' }}">
    @foreach([
        ['label' => 'Invoice Total', 'value' => $summary['invoiceTotal'], 'note' => 'All invoices in period'],
        ['label' => 'Paid', 'value' => $summary['paidTotal'], 'note' => 'Payments received'],
        ['label' => 'Balance Due', 'value' => $summary['balanceDue'], 'note' => $summary['openInvoices'].' open invoices'],
        ['label' => 'As Of', 'value' => $asOfDate->format('Y-m-d'), 'note' => $fromDate ? 'From '.$fromDate->format('Y-m-d') : 'Full customer history', 'raw' => true],
    ] as $card)
        <div class="{{ $printMode ? 'statement-card' : 'col-md-3' }}">
            <section class="{{ $printMode ? 'box' : 'panel h-100' }}">
                <span class="eyebrow">{{ $card['label'] }}</span>
                <h2 class="mb-0">{{ ($card['raw'] ?? false) ? $card['value'] : $money($card['value']) }}</h2>
                <p class="text-muted mb-0">{{ $card['note'] }}</p>
            </section>
        </div>
    @endforeach
</div>

<section class="{{ $printMode ? 'box' : 'panel mb-3' }}">
    <div class="panel-header align-items-start">
        <div>
            <h2>Aging</h2>
            <p>Outstanding balance grouped by due date age.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table modern-table align-middle statement-table">
            <thead>
            <tr>
                @foreach($agingLabels as $label)
                    <th>{{ $label }}</th>
                @endforeach
                <th>Total Due</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                @foreach(array_keys($agingLabels) as $bucket)
                    <td>{{ $money($aging[$bucket]) }}</td>
                @endforeach
                <td><strong>{{ $money(array_sum($aging)) }}</strong></td>
            </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="{{ $printMode ? 'box' : 'panel mb-3' }}">
    <div class="panel-header align-items-start">
        <div>
            <h2>Open Invoices</h2>
            <p>Invoices included in this customer statement.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table modern-table align-middle statement-table">
            <thead>
            <tr>
                <th>Invoice</th>
                <th>Invoice Date</th>
                <th>Due Date</th>
                <th>Status</th>
                <th class="text-end">Total</th>
                <th class="text-end">Paid</th>
                <th class="text-end">Balance</th>
            </tr>
            </thead>
            <tbody>
            @forelse($invoices as $invoice)
                <tr>
                    <td>
                        @if($printMode)
                            {{ $invoice->invoice_number }}
                        @else
                            <a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a>
                        @endif
                    </td>
                    <td>{{ $invoice->invoice_date?->format('Y-m-d') ?: '-' }}</td>
                    <td>{{ $invoice->due_date?->format('Y-m-d') ?: '-' }}</td>
                    <td><span class="badge badge-soft-secondary">{{ str($invoice->status)->headline() }}</span></td>
                    <td class="text-end">{{ $money($invoice->total_amount) }}</td>
                    <td class="text-end">{{ $money($invoice->paid_amount) }}</td>
                    <td class="text-end">{{ $money($invoice->balance_due) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No invoices found for this statement period.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="{{ $printMode ? 'box' : 'panel' }}">
    <div class="panel-header align-items-start">
        <div>
            <h2>Transaction History</h2>
            <p>Invoice charges and payment credits in date order.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table modern-table align-middle statement-table">
            <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Reference</th>
                <th>Description</th>
                <th class="text-end">Debit</th>
                <th class="text-end">Credit</th>
            </tr>
            </thead>
            <tbody>
            @forelse($transactions as $transaction)
                <tr>
                    <td>{{ optional($transaction['date'])->format('Y-m-d') ?: '-' }}</td>
                    <td>{{ $transaction['type'] }}</td>
                    <td>
                        @if($printMode)
                            {{ $transaction['reference'] }}
                        @else
                            <a href="{{ $transaction['url'] }}">{{ $transaction['reference'] }}</a>
                        @endif
                    </td>
                    <td>{{ $transaction['description'] }}</td>
                    <td class="text-end">{{ $transaction['debit'] > 0 ? $money($transaction['debit']) : '-' }}</td>
                    <td class="text-end">{{ $transaction['credit'] > 0 ? $money($transaction['credit']) : '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No transactions found for this statement period.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

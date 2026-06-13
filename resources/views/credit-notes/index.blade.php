@extends('layouts.app')

@section('title', 'Credit Notes')

@section('content')
    @php($money = app(\App\Support\Money::class))
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Adjustments</span>
                <h1>Credit Notes</h1>
                <p>Track invoice credits, corrections, and refunds issued to customers.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Credited</span>
                    <h2 class="mb-0">{{ $money->format($summary['credited']) }}</h2>
                    <p class="text-muted mb-0">Total credit issued</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Refunded</span>
                    <h2 class="mb-0">{{ $money->format($summary['refunded']) }}</h2>
                    <p class="text-muted mb-0">Money returned</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Notes</span>
                    <h2 class="mb-0">{{ $summary['count'] }}</h2>
                    <p class="text-muted mb-0">Credit note records</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Applied</span>
                    <h2 class="mb-0">{{ $summary['open'] }}</h2>
                    <p class="text-muted mb-0">Credits without refund</p>
                </section>
            </div>
        </div>

        <section class="panel">
            <div class="table-responsive">
                <table class="table modern-table align-middle">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Credit Note</th>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Refund</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($creditNotes as $creditNote)
                        <tr>
                            <td>{{ $creditNote->credit_date?->format('Y-m-d') }}</td>
                            <td><a href="{{ route('credit-notes.show', $creditNote) }}">{{ $creditNote->credit_note_number }}</a></td>
                            <td>
                                @if($creditNote->invoice)
                                    <a href="{{ route('invoices.show', $creditNote->invoice) }}">{{ $creditNote->invoice->invoice_number }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $creditNote->customer?->company_name ?: 'Unknown customer' }}</td>
                            <td>{{ str($creditNote->reason)->headline() }}</td>
                            <td><span class="badge badge-soft-secondary">{{ str($creditNote->status)->headline() }}</span></td>
                            <td>{{ $money->format($creditNote->amount, $creditNote->invoice?->currency) }}</td>
                            <td>{{ $money->format($creditNote->refund_amount, $creditNote->invoice?->currency) }}</td>
                            <td>
                                <div class="table-actions justify-content-end">
                                    <a href="{{ route('credit-notes.print', $creditNote) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                                        <x-lucide-printer class="w-4 h-4"/>
                                        Print
                                    </a>
                                    @if($creditNote->status !== 'voided')
                                        <a href="{{ route('credit-notes.edit', $creditNote) }}" class="btn btn-sm btn-outline-secondary">
                                            <x-lucide-pencil class="w-4 h-4"/>
                                            Edit
                                        </a>
                                    @endif
                                    <a href="{{ route('credit-notes.download', $creditNote) }}" class="btn btn-sm btn-primary">
                                        <x-lucide-file-down class="w-4 h-4"/>
                                        PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No credit notes issued yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

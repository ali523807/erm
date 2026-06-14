@extends('layouts.app')

@section('title', 'Delivery Log')

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Documents</span>
                <h1>Delivery Log</h1>
                <p>Track sent PDFs, recipients, delivery status, and failures across quotes, invoices, receipts, credit notes, and statements.</p>
            </div>
            <x-button :link="route('documents.index')" color="outline-secondary">
                <x-lucide-folder-open class="w-4 h-4"/>
                <span>Documents</span>
            </x-button>
        </div>

        <section class="panel">
            <div class="panel-header align-items-start">
                <div>
                    <h2>Email Deliveries</h2>
                    <p>Newest delivery attempts are listed first.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table modern-table align-middle">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Document</th>
                        <th>Recipient</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Sent By</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($deliveries as $delivery)
                        <tr>
                            <td>{{ $delivery->created_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                <strong>{{ str($delivery->type)->headline() }}</strong>
                                <div class="text-muted text-xs">{{ $delivery->attachment_name ?: 'PDF attachment' }}</div>
                            </td>
                            <td>
                                {{ $delivery->recipient_email }}
                                <div class="text-muted text-xs">{{ $delivery->recipient_name ?: '-' }}</div>
                            </td>
                            <td>{{ $delivery->subject }}</td>
                            <td>
                                <span class="badge {{ $delivery->status === 'sent' ? 'badge-soft-success' : ($delivery->status === 'failed' ? 'badge-soft-danger' : 'badge-soft-warning') }}">
                                    {{ str($delivery->status)->headline() }}
                                </span>
                                @if($delivery->error_message)
                                    <div class="text-danger text-xs mt-1">{{ $delivery->error_message }}</div>
                                @endif
                            </td>
                            <td>{{ $delivery->sender?->name ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No email deliveries recorded yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <x-pagination :paginator="$deliveries"/>
        </section>
    </div>
@endsection

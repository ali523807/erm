@extends('layouts.customer-portal')

@section('title', 'Dashboard')

@section('content')
    <div class="page-header">
        <div>
            <span class="eyebrow">Welcome</span>
            <h1>{{ $customer->company_name }}</h1>
            <p>Review your active rental activity, open invoices, quote decisions, and shared documents.</p>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @foreach([
            ['Quotes', $summary['quotes'], 'file-signature'],
            ['Rentals', $summary['rentals'], 'file-box'],
            ['Invoices', $summary['invoices'], 'file-text'],
            ['Balance', number_format($summary['balance'], 2), 'credit-card'],
        ] as [$label, $value, $icon])
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">{{ $label }}</span>
                    <h2 class="mb-0">{{ $value }}</h2>
                </section>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            @include('customer-portal.partials._mini-table', ['title' => 'Recent Quotes', 'rows' => $recentQuotes, 'empty' => 'No quotes yet.', 'route' => 'customer-portal.quotes'])
        </div>
        <div class="col-lg-4">
            @include('customer-portal.partials._mini-table', ['title' => 'Recent Rentals', 'rows' => $recentRentals, 'empty' => 'No rentals yet.', 'route' => 'customer-portal.rentals'])
        </div>
        <div class="col-lg-4">
            @include('customer-portal.partials._mini-table', ['title' => 'Recent Invoices', 'rows' => $recentInvoices, 'empty' => 'No invoices yet.', 'route' => 'customer-portal.invoices'])
        </div>
    </div>
@endsection

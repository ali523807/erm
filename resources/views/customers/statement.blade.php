@extends('layouts.app')

@section('title', 'Statement - '.$customer->company_name)

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Customer finance</span>
                <h1>Customer Statement</h1>
                <p>{{ $customer->company_name }} - as of {{ $asOfDate->format('Y-m-d') }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <x-button :link="route('customers.show', $customer)" color="outline-secondary">
                    <x-lucide-arrow-left class="w-4 h-4"/>
                    <span>Profile</span>
                </x-button>
                <x-button :link="route('customers.statement.print', ['customer' => $customer, 'as_of' => $asOfDate->format('Y-m-d'), 'from' => $fromDate?->format('Y-m-d')])" color="outline-secondary">
                    <x-lucide-printer class="w-4 h-4"/>
                    <span>Print</span>
                </x-button>
                <x-button :link="route('customers.statement.download', ['customer' => $customer, 'as_of' => $asOfDate->format('Y-m-d'), 'from' => $fromDate?->format('Y-m-d')])" color="dark">
                    <x-lucide-download class="w-4 h-4"/>
                    <span>PDF</span>
                </x-button>
            </div>
        </div>

        <section class="panel mb-3">
            <div class="panel-header align-items-start">
                <div>
                    <h2>Statement Filters</h2>
                    <p>Choose the statement period and aging date.</p>
                </div>
            </div>
            <form method="GET" action="{{ route('customers.statement.show', $customer) }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="from" class="form-label">From Date</label>
                    <input id="from" name="from" type="date" class="form-control" value="{{ $fromDate?->format('Y-m-d') }}">
                </div>
                <div class="col-md-4">
                    <label for="as_of" class="form-label">As Of Date</label>
                    <input id="as_of" name="as_of" type="date" class="form-control" value="{{ $asOfDate->format('Y-m-d') }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-dark">
                        <x-lucide-search class="w-4 h-4"/>
                        Refresh Statement
                    </button>
                </div>
            </form>
        </section>

        @include('customers.statement-body')
    </div>
@endsection

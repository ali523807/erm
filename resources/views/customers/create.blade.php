@extends('layouts.app')

@section('title', 'Create Customer')

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Customer desk</span>
                <h1>Create Customer</h1>
                <p>Add a customer account for quotes, rentals, invoices, documents, and future statements.</p>
            </div>
            <x-button :link="route('customers.index')" color="outline-secondary">
                <x-lucide-arrow-left class="w-4 h-4"/>
                <span>Back</span>
            </x-button>
        </div>

        <section class="panel">
            <div class="panel-header align-items-start">
                <div>
                    <h2>Customer Information</h2>
                    <p>Keep the profile clean and useful for operations, billing, and customer-facing documents.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('customers.store') }}">
                @csrf
                @include('customers._form-fields')
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-dark">
                        <x-lucide-save class="w-4 h-4"/>
                        Save Customer
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Edit Customer')

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Customer desk</span>
                <h1>Edit Customer</h1>
                <p>Update contact, billing, tax, and operating notes for {{ $customer->company_name }}.</p>
            </div>
            <x-button :link="route('customers.show', $customer)" color="outline-secondary">
                <x-lucide-arrow-left class="w-4 h-4"/>
                <span>Profile</span>
            </x-button>
        </div>

        <section class="panel">
            <div class="panel-header align-items-start">
                <div>
                    <h2>Customer Information</h2>
                    <p>Changes here affect future quotes, rentals, invoices, and customer documents.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('customers.update', $customer) }}">
                @csrf
                @method('PUT')
                @include('customers._form-fields')
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-dark">
                        <x-lucide-save class="w-4 h-4"/>
                        Save Changes
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection

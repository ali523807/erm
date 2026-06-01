@extends('layouts.app')

@section('title', 'Edit Equipment')

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Equipment profile</span>
                <h1>Edit Equipment</h1>
                <p>Update identity, location, rental defaults, compliance dates, and custom attributes for this rentable asset.</p>
            </div>

            <x-button :link="route('products.index')" color="outline-secondary">
                <x-lucide-arrow-left class="w-4 h-4"/>
                <span>Back</span>
            </x-button>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @include('products._page-form', [
            'action' => route('products.update', $product),
            'method' => 'PUT',
            'submitLabel' => 'Save Changes',
        ])
    </div>
@endsection

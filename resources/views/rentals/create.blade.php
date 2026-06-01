@extends('layouts.app')

@section('title', 'Create Rental')

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">New rental</span>
                <h1>Create Rental</h1>
                <p>Reserve equipment directly for a customer, with availability checks before the booking is saved.</p>
            </div>
            <x-button :link="route('rentals.index')" color="outline-secondary">
                <x-lucide-arrow-left class="w-4 h-4"/>
                <span>Back</span>
            </x-button>
        </div>

        @include('rentals._page-form', [
            'action' => route('rentals.store'),
            'method' => 'POST',
            'submitLabel' => 'Create Rental',
        ])
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Edit Rental')

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Rental revision</span>
                <h1>Edit RTN-{{ $rental->id }}</h1>
                <p>Adjust customer dates, delivery information, equipment lines, deposits, and rates.</p>
            </div>
            <x-button :link="route('rentals.show', $rental)" color="outline-secondary">
                <x-lucide-arrow-left class="w-4 h-4"/>
                <span>Back</span>
            </x-button>
        </div>

        @include('rentals._page-form', [
            'action' => route('rentals.update', $rental),
            'method' => 'PUT',
            'submitLabel' => 'Save Rental',
        ])
    </div>
@endsection

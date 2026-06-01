@extends('layouts.app')

@section('title', 'Edit Quote')

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Quote revision</span>
                <h1>Edit {{ $quote->quote_number }}</h1>
                <p>Adjust dates, equipment, pricing, and terms before the quote is accepted.</p>
            </div>
            <x-button :link="route('quotes.show', $quote)" color="outline-secondary">
                <x-lucide-arrow-left class="w-4 h-4"/>
                <span>Back</span>
            </x-button>
        </div>

        @include('quotes._form', [
            'action' => route('quotes.update', $quote),
            'method' => 'PUT',
            'submitLabel' => 'Save Quote',
        ])
    </div>
@endsection

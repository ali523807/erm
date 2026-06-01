@extends('layouts.app')

@section('title', 'Create Quote')

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">New quote</span>
                <h1>Create Quote</h1>
                <p>Build a priced rental offer and check equipment availability before sending it to the customer.</p>
            </div>
            <x-button :link="route('quotes.index')" color="outline-secondary">
                <x-lucide-arrow-left class="w-4 h-4"/>
                <span>Back</span>
            </x-button>
        </div>

        @include('quotes._form', [
            'action' => route('quotes.store'),
            'method' => 'POST',
            'submitLabel' => 'Create Quote',
        ])
    </div>
@endsection

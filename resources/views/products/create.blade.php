@extends('layouts.app')

@section('title', 'Add Equipment')

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">New equipment</span>
                <h1>Add Equipment</h1>
                <p>Create a complete asset profile that can work for machines, vehicles, tools, kits, furniture, structures, or any rentable item.</p>
            </div>

            <x-button :link="route('products.index')" color="outline-secondary">
                <x-lucide-arrow-left class="w-4 h-4"/>
                <span>Back</span>
            </x-button>
        </div>

        @include('products._page-form', [
            'action' => route('products.store'),
            'method' => 'POST',
            'submitLabel' => 'Create Equipment',
        ])
    </div>
@endsection

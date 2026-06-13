@extends('layouts.app')

@section('title', 'Edit '.$creditNote->credit_note_number)

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Credit note</span>
                <h1>Edit {{ $creditNote->credit_note_number }}</h1>
                <p>Update credit details, refund information, and notes before final reporting.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <x-button :link="route('credit-notes.show', $creditNote)" color="outline-secondary">
                    <x-lucide-arrow-left class="w-4 h-4"/>
                    <span>Back</span>
                </x-button>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <section class="panel">
            <div class="panel-header align-items-start">
                <div>
                    <h2>Credit Note Details</h2>
                    <p>Changes recalculate the linked invoice balance immediately.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('credit-notes.update', $creditNote) }}">
                @csrf
                @method('PUT')
                @include('credit-notes._form-fields')
                <button type="submit" class="btn btn-dark mt-3">
                    <x-lucide-save class="w-4 h-4"/>
                    Update Credit Note
                </button>
            </form>
        </section>
    </div>
@endsection

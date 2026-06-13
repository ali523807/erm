@extends('layouts.app')

@section('title', $creditNote->credit_note_number)

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Credit note</span>
                <h1>{{ $creditNote->credit_note_number }}</h1>
                <p>{{ $creditNote->customer?->company_name }} - {{ $creditNote->invoice?->invoice_number }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <x-button :link="route('credit-notes.index')" color="outline-secondary">
                    <x-lucide-arrow-left class="w-4 h-4"/>
                    <span>Back</span>
                </x-button>
                @if($creditNote->invoice)
                    <x-button :link="route('invoices.show', $creditNote->invoice)" color="outline-secondary">
                        <x-lucide-file-text class="w-4 h-4"/>
                        <span>Invoice</span>
                    </x-button>
                @endif
                @if($creditNote->status !== 'voided')
                    <x-button :link="route('credit-notes.edit', $creditNote)" color="outline-secondary">
                        <x-lucide-pencil class="w-4 h-4"/>
                        <span>Edit</span>
                    </x-button>
                @endif
                <x-button :link="route('credit-notes.print', $creditNote)" color="outline-secondary" target="_blank">
                    <x-lucide-printer class="w-4 h-4"/>
                    <span>Print</span>
                </x-button>
                <x-button :link="route('credit-notes.download', $creditNote)" color="dark">
                    <x-lucide-download class="w-4 h-4"/>
                    <span>PDF</span>
                </x-button>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        @include('credit-notes._document', ['printMode' => false])

        @include('document-deliveries._send-form', [
            'action' => route('credit-notes.send', $creditNote),
            'idPrefix' => 'credit_note_email',
            'title' => 'Email Credit Note',
            'description' => 'Send the credit note PDF to the customer and keep a record of the delivery.',
            'recipientEmail' => $creditNote->customer?->email,
            'recipientName' => $creditNote->customer?->contact_person,
            'subject' => 'Credit Note '.$creditNote->credit_note_number,
            'message' => 'Please find the attached credit note for your records.',
            'class' => 'mt-3',
        ])

        @if($creditNote->status !== 'voided')
            <section class="panel mt-3">
                <div class="panel-header align-items-start">
                    <div>
                        <h2>Void Credit Note</h2>
                        <p>Voiding keeps the record for audit history and removes its effect from the invoice balance.</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('credit-notes.void', $creditNote) }}">
                    @csrf
                    @method('PATCH')
                    <label for="void_reason" class="form-label">Void Reason</label>
                    <textarea id="void_reason" name="void_reason" rows="3" class="form-control" required>{{ old('void_reason') }}</textarea>
                    <button type="submit" class="btn btn-outline-danger mt-3">
                        <x-lucide-ban class="w-4 h-4"/>
                        Void Credit Note
                    </button>
                </form>
            </section>
        @endif
    </div>
@endsection

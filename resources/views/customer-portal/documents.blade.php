@extends('layouts.customer-portal')

@section('title', 'Documents')

@section('content')
    <div class="page-header"><div><span class="eyebrow">Portal</span><h1>Documents</h1><p>Download shared files and upload customer documents.</p></div></div>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="row g-3">
        <div class="col-lg-4">
            <section class="panel">
                <h2>Upload Document</h2>
                <form method="POST" action="{{ route('customer-portal.documents.store') }}" enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <div class="col-12"><label class="form-label">Title</label><input name="title" class="form-control" required></div>
                    <div class="col-12"><label class="form-label">Type</label><select name="type" class="form-select" required>@foreach(['trade_license'=>'Trade License','tax_certificate'=>'Tax Certificate','insurance'=>'Insurance','id_document'=>'ID Document','payment_proof'=>'Payment Proof','other'=>'Other'] as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                    <div class="col-12"><label class="form-label">Expiry</label><input name="expires_at" type="date" class="form-control"></div>
                    <div class="col-12"><label class="form-label">File</label><input name="file" type="file" class="form-control" required></div>
                    <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3"></textarea></div>
                    <div class="col-12 d-grid"><button class="btn btn-dark">Upload</button></div>
                </form>
            </section>
        </div>
        <div class="col-lg-8">
            <section class="panel">
                <div class="table-responsive">
                    <table class="table modern-table align-middle">
                        <thead><tr><th>Document</th><th>Expiry</th><th></th></tr></thead>
                        <tbody>
                        @forelse($documents as $document)
                            <tr>
                                <td><strong>{{ $document->title }}</strong><span class="d-block text-muted small">{{ str($document->type)->headline() }} · {{ $document->original_name }}</span></td>
                                <td>{{ $document->expires_at?->format('Y-m-d') ?: 'No expiry' }}</td>
                                <td class="text-end"><a href="{{ route('customer-portal.documents.download', $document) }}" class="btn btn-sm btn-outline-secondary">Download</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">No documents available.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
@endsection

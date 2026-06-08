@extends('layouts.app')

@section('title', 'Documents')

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Document Center</span>
                <h1>Documents</h1>
                <p>Store licenses, insurance, IDs, agreements, delivery notes, payment proofs, and compliance files.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="row g-3">
            <div class="col-xl-4">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Upload Document</h2>
                            <p>Attach a file to the company, customer, equipment, rental, or invoice record.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="row g-3">
                        @csrf

                        <div class="col-12">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input id="title" name="title" class="form-control" value="{{ old('title') }}" placeholder="Customer trade license" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="type" class="form-label">Document Type <span class="text-danger">*</span></label>
                            <select id="type" name="type" class="form-select" required>
                                @foreach($documentTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="owner_type" class="form-label">Attach To <span class="text-danger">*</span></label>
                            <select id="owner_type" name="owner_type" class="form-select" required>
                                @foreach(['company' => 'Company', 'customer' => 'Customer', 'equipment' => 'Equipment', 'rental' => 'Rental', 'invoice' => 'Invoice'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('owner_type', 'company') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="owner_id" class="form-label">Specific Record</label>
                            <select id="owner_id" name="owner_id" class="form-select">
                                <option value="">Company document or choose after selecting type</option>
                                <optgroup label="Customers">
                                    @foreach($attachableRecords['customers'] as $customer)
                                        <option value="{{ $customer->id }}" data-owner-type="customer">{{ $customer->company_name }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Equipment">
                                    @foreach($attachableRecords['equipment'] as $equipment)
                                        <option value="{{ $equipment->id }}" data-owner-type="equipment">{{ $equipment->name }} {{ $equipment->equipment_code ? "({$equipment->equipment_code})" : '' }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Rentals">
                                    @foreach($attachableRecords['rentals'] as $rental)
                                        <option value="{{ $rental->id }}" data-owner-type="rental">RTN-{{ $rental->id }} · {{ str($rental->status)->headline() }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Invoices">
                                    @foreach($attachableRecords['invoices'] as $invoice)
                                        <option value="{{ $invoice->id }}" data-owner-type="invoice">{{ $invoice->invoice_number }} · {{ str($invoice->status)->headline() }}</option>
                                    @endforeach
                                </optgroup>
                            </select>
                            <div class="form-text">For company documents, leave this blank.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="issued_at" class="form-label">Issued Date</label>
                            <input id="issued_at" name="issued_at" type="date" class="form-control" value="{{ old('issued_at') }}">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="expires_at" class="form-label">Expiry Date</label>
                            <input id="expires_at" name="expires_at" type="date" class="form-control" value="{{ old('expires_at') }}">
                        </div>

                        <div class="col-12">
                            <label for="file" class="form-label">File <span class="text-danger">*</span></label>
                            <input id="file" name="file" type="file" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx" required>
                            <div class="form-text">Images, PDF, Word, and Excel files up to 10 MB.</div>
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" name="notes" rows="3" class="form-control" placeholder="Renewal note, customer reference, or document context.">{{ old('notes') }}</textarea>
                        </div>

                        <div class="col-12 d-grid">
                            <button type="submit" class="btn btn-dark">
                                <x-lucide-upload class="w-4 h-4 me-1"/>
                                Upload Document
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            <div class="col-xl-8">
                <section class="panel mb-3">
                    <form method="GET" action="{{ route('documents.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="filter_type" class="form-label">Type</label>
                            <select id="filter_type" name="type" class="form-select">
                                <option value="">All types</option>
                                @foreach($documentTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="owner_filter" class="form-label">Attached To</label>
                            <select id="owner_filter" name="owner_type" class="form-select">
                                <option value="">All records</option>
                                @foreach(['company' => 'Company', 'customer' => 'Customer', 'equipment' => 'Equipment', 'rental' => 'Rental', 'invoice' => 'Invoice'] as $value => $label)
                                    <option value="{{ $value }}" @selected(($filters['owner_type'] ?? '') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="expiry" class="form-label">Expiry</label>
                            <select id="expiry" name="expiry" class="form-select">
                                <option value="">All</option>
                                <option value="expiring" @selected(($filters['expiry'] ?? '') === 'expiring')>Expiring within 30 days</option>
                                <option value="expired" @selected(($filters['expiry'] ?? '') === 'expired')>Expired</option>
                                <option value="none" @selected(($filters['expiry'] ?? '') === 'none')>No expiry</option>
                            </select>
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <a href="{{ route('documents.index') }}" class="btn btn-soft-secondary">Reset</a>
                            <button type="submit" class="btn btn-dark">
                                <x-lucide-filter class="w-4 h-4 me-1"/>
                                Filter
                            </button>
                        </div>
                    </form>
                </section>

                <section class="panel">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Document Library</h2>
                            <p>Files uploaded across this company workspace.</p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Document</th>
                                <th>Attached To</th>
                                <th>Expiry</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($documents as $document)
                                <tr>
                                    <td>
                                        <strong>{{ $document->title }}</strong>
                                        <div class="text-muted text-xs">{{ $documentTypes[$document->type] ?? str($document->type)->headline() }} · {{ $document->original_name }} · {{ number_format($document->size / 1024, 1) }} KB</div>
                                        @if($document->notes)
                                            <div class="text-muted text-xs">{{ $document->notes }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($document->documentable)
                                            {{ class_basename($document->documentable_type) }}
                                            <span class="d-block text-muted text-xs">
                                                {{ $document->documentable->name ?? $document->documentable->company_name ?? $document->documentable->invoice_number ?? 'RTN-'.$document->documentable_id }}
                                            </span>
                                        @else
                                            Company
                                        @endif
                                    </td>
                                    <td>
                                        {{ $document->expires_at?->format('Y-m-d') ?: 'No expiry' }}
                                        @if($document->expires_at && $document->expires_at->isPast())
                                            <span class="badge text-bg-danger d-block mt-1">Expired</span>
                                        @elseif($document->expires_at && $document->expires_at->lte(now()->addDays(30)))
                                            <span class="badge text-bg-warning d-block mt-1">Expiring soon</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-outline-secondary">Download</a>
                                        <form method="POST" action="{{ route('documents.destroy', $document) }}" class="d-inline" onsubmit="return confirm('Delete this document?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No documents uploaded yet.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $documents->links() }}
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('title', $product->name)

@php
    $locationPath = collect([
        $product->branch?->name,
        $product->warehouse?->name,
        $product->storageLocation?->name,
    ])->filter()->join(' / ') ?: ($product->location ?: 'Unassigned');

    $formatMoney = fn ($value): string => number_format((float) $value, 2);
@endphp

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Equipment profile</span>
                <h1>{{ $product->name }}</h1>
                <p>{{ $product->equipment_code ?: 'No asset code' }} · {{ $product->category?->name ?: 'No category' }} · {{ str($product->status ?: 'available')->headline() }}</p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <x-button :link="route('products.index')" color="outline-secondary">
                    <x-lucide-arrow-left class="w-4 h-4"/>
                    <span>Back</span>
                </x-button>
                <x-button :link="route('products.edit', $product)" color="dark">
                    <x-lucide-pencil class="w-4 h-4"/>
                    <span>Edit</span>
                </x-button>
                <x-button :link="route('availability.index', ['start_date' => now()->toDateString(), 'end_date' => now()->addDays(7)->toDateString()])" color="outline-primary">
                    <x-lucide-calendar-check class="w-4 h-4"/>
                    <span>Availability</span>
                </x-button>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="row g-3">
            <div class="col-xl-8">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Overview</h2>
                            <p>Identity, rental readiness, and commercial defaults for this equipment.</p>
                        </div>
                    </div>

                    <dl class="detail-grid">
                        <div>
                            <dt>Brand / Model</dt>
                            <dd>{{ collect([$product->brand, $product->model])->filter()->join(' ') ?: 'Not recorded' }}</dd>
                        </div>
                        <div>
                            <dt>Serial Number</dt>
                            <dd>{{ $product->serial_number ?: 'Not recorded' }}</dd>
                        </div>
                        <div>
                            <dt>Condition</dt>
                            <dd>{{ $product->condition ?: 'Not recorded' }}</dd>
                        </div>
                        <div>
                            <dt>Ownership</dt>
                            <dd>{{ str($product->ownership_type ?: 'owned')->headline() }}</dd>
                        </div>
                        <div>
                            <dt>Default Rate</dt>
                            <dd>{{ $product->default_rate_type ? str($product->default_rate_type)->headline().' '.$formatMoney($product->default_rate) : 'No default rate' }}</dd>
                        </div>
                        <div>
                            <dt>Unit of Measure</dt>
                            <dd>{{ $product->unit_of_measure ?: 'unit' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-3">
                        <h3 class="section-subtitle">Description</h3>
                        <p class="text-muted mb-0 mt-2">{{ $product->description }}</p>
                    </div>
                </section>
            </div>

            <div class="col-xl-4">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Location and Dates</h2>
                            <p>Where this asset belongs and the key lifecycle dates.</p>
                        </div>
                    </div>

                    <dl class="detail-grid">
                        <div>
                            <dt>Location</dt>
                            <dd>{{ $locationPath }}</dd>
                        </div>
                        <div>
                            <dt>Active</dt>
                            <dd>{{ $product->active ? 'Yes' : 'No' }}</dd>
                        </div>
                        <div>
                            <dt>Acquisition</dt>
                            <dd>{{ $product->acquisition_date?->format('Y-m-d') ?: ($product->purchase_date ?: 'Not recorded') }}</dd>
                        </div>
                        <div>
                            <dt>Warranty Expiry</dt>
                            <dd>{{ $product->warranty_expiry ?: 'Not recorded' }}</dd>
                        </div>
                        <div>
                            <dt>Certificate Expiry</dt>
                            <dd>{{ $product->certificate_expires_at?->format('Y-m-d') ?: 'Not recorded' }}</dd>
                        </div>
                        <div>
                            <dt>Replacement Value</dt>
                            <dd>{{ $formatMoney($product->replacement_value) }}</dd>
                        </div>
                    </dl>
                </section>
            </div>

            <div class="col-xl-6">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Specifications</h2>
                            <p>Template-driven and custom attributes saved for this equipment.</p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Attribute</th>
                                <th>Value</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($product->attributes as $attribute)
                                <tr>
                                    <td>{{ $attribute->key }}</td>
                                    <td>{{ $attribute->value }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-4">No specifications saved yet.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="col-xl-6">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Rental History</h2>
                            <p>Recent rentals where this equipment was reserved or dispatched.</p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Period</th>
                                <th>Status</th>
                                <th>Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($product->rentalItems->sortByDesc('created_at') as $item)
                                <tr>
                                    <td>{{ $item->rental?->customer?->company_name ?: 'Unknown customer' }}</td>
                                    <td>{{ $item->rental?->rental_start_date ?: 'Open' }} - {{ $item->rental?->rental_end_date ?: 'Open' }}</td>
                                    <td>{{ str($item->status ?: $item->rental?->status ?: 'open')->headline() }}</td>
                                    <td>{{ $formatMoney($item->total_price) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No rental history yet.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="col-12">
                <section class="panel">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Maintenance and Inspection History</h2>
                            <p>Service, repair, inspection, and compliance work recorded for this equipment.</p>
                        </div>
                        <a href="{{ route('maintenance.index') }}" class="btn btn-sm btn-outline-secondary">Open Maintenance</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Work</th>
                                <th>Schedule</th>
                                <th>Status</th>
                                <th>Provider</th>
                                <th>Cost</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($product->maintenanceLogs->sortByDesc('scheduled_at') as $log)
                                <tr>
                                    <td>
                                        <strong>{{ $log->title ?: str($log->type)->headline() }}</strong>
                                        <div class="text-muted text-xs">{{ str($log->type)->headline() }} · {{ str($log->priority)->headline() }}</div>
                                    </td>
                                    <td>
                                        {{ $log->scheduled_at?->format('Y-m-d') ?: 'Not scheduled' }}
                                        <div class="text-muted text-xs">Next: {{ $log->next_service_due ?: 'Not set' }}</div>
                                    </td>
                                    <td>{{ str($log->status)->headline() }}</td>
                                    <td>{{ $log->service_provider ?: 'Not recorded' }}</td>
                                    <td>{{ $formatMoney($log->cost) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No maintenance history yet.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="col-xl-5">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Upload Files</h2>
                            <p>Add photos, certificates, manuals, warranty files, inspection reports, or insurance documents.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('products.documents.store', $product) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="document_title" class="form-label">Title <span class="text-danger">*</span></label>
                                <input id="document_title" name="title" class="form-control" placeholder="Annual safety certificate" required>
                                <div class="form-text">Use a clear name your team can recognize later.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="document_type" class="form-label">Type <span class="text-danger">*</span></label>
                                <select id="document_type" name="type" class="form-select" required>
                                    @foreach($documentTypes as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="document_expires_at" class="form-label">Expiry Date</label>
                                <input id="document_expires_at" name="expires_at" type="date" class="form-control">
                            </div>
                            <div class="col-12">
                                <label for="document_file" class="form-label">File <span class="text-danger">*</span></label>
                                <input id="document_file" name="file" type="file" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx" required>
                                <div class="form-text">Supported: images, PDFs, Word, and Excel files up to 10 MB.</div>
                            </div>
                            <div class="col-12">
                                <label for="document_notes" class="form-label">Notes</label>
                                <textarea id="document_notes" name="notes" rows="3" class="form-control" placeholder="Inspection result, renewal reminder, or file context."></textarea>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-dark">
                                    <x-lucide-upload class="w-4 h-4 me-1"/>
                                    Upload File
                                </button>
                            </div>
                        </div>
                    </form>
                </section>
            </div>

            <div class="col-xl-7">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Files and Photos</h2>
                            <p>Documents attached to this asset for operations, compliance, and customer support.</p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>File</th>
                                <th>Type</th>
                                <th>Expiry</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($product->documents->sortByDesc('created_at') as $document)
                                <tr>
                                    <td>
                                        <strong>{{ $document->title }}</strong>
                                        <div class="text-muted text-xs">{{ $document->original_name }} · {{ number_format($document->size / 1024, 1) }} KB</div>
                                        @if($document->notes)
                                            <div class="text-muted text-xs">{{ $document->notes }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $documentTypes[$document->type] ?? str($document->type)->headline() }}</td>
                                    <td>{{ $document->expires_at?->format('Y-m-d') ?: 'No expiry' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('products.documents.download', [$product, $document]) }}" class="btn btn-sm btn-outline-secondary">Download</a>
                                        <form method="POST" action="{{ route('products.documents.destroy', [$product, $document]) }}" class="d-inline" onsubmit="return confirm('Delete this file?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No files uploaded yet.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection

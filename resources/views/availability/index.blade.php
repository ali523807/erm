@extends('layouts.app')

@section('title', 'Availability')

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Booking control</span>
                <h1>Availability Calendar</h1>
                <p>Check whether equipment is free for a date range before confirming rentals, reservations, or dispatch plans.</p>
            </div>
        </div>

        <section class="panel">
            <div class="panel-header align-items-start">
                <div>
                    <h2>Search Availability</h2>
                    <p>Conflicts include existing rentals, reservations, maintenance, and unavailable equipment status.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('availability.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input id="start_date" name="start_date" type="date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input id="end_date" name="end_date" type="date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select id="category_id" name="category_id" class="form-select">
                            <option value="">All categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label">Equipment Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">All statuses</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->headline() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-dark w-100">Check</button>
                    </div>
                </div>
            </form>
        </section>

        <section class="panel mt-3">
            <div class="panel-header align-items-start">
                <div>
                    <h2>{{ $startDate }} to {{ $endDate }}</h2>
                    <p>Available equipment can be booked. Blocked equipment shows the reason so the team knows what to resolve.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table modern-table align-middle">
                    <thead>
                    <tr>
                        <th>Equipment</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Availability</th>
                        <th>Conflicts</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $row)
                        @php
                            $product = $row['product'];
                            $conflicts = $row['conflicts'];
                            $location = collect([$product->branch?->name, $product->warehouse?->name, $product->storageLocation?->name])->filter()->join(' / ') ?: 'Unassigned';
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $product->name }}</strong>
                                <div class="text-muted text-xs">{{ $product->equipment_code ?: 'No code' }}</div>
                            </td>
                            <td>{{ $product->category?->name ?: 'No category' }}</td>
                            <td>{{ $location }}</td>
                            <td>
                                @if($conflicts->isEmpty())
                                    <span class="badge badge-soft-success">Available</span>
                                @else
                                    <span class="badge badge-soft-danger">Blocked</span>
                                @endif
                            </td>
                            <td>
                                @if($conflicts->isEmpty())
                                    <span class="text-muted">No conflicts in this range.</span>
                                @else
                                    <div class="d-grid gap-1">
                                        @foreach($conflicts as $conflict)
                                            <span>
                                                <strong>{{ str($conflict['type'])->headline() }}:</strong>
                                                {{ $conflict['label'] }}
                                                @if($conflict['start'] || $conflict['end'])
                                                    <span class="text-muted">({{ $conflict['start'] ?: 'open' }} - {{ $conflict['end'] ?: 'open' }})</span>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No equipment found for this filter.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

<div class="table-responsive">
    <table class="table modern-table align-middle">
        <thead>
        <tr>
            <th>Rental</th>
            <th>Customer</th>
            <th>{{ $dateColumn }}</th>
            <th>Equipment</th>
            <th>Status</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($rentals as $rental)
            <tr>
                <td>
                    <strong>RTN-{{ $rental->id }}</strong>
                    <div class="text-muted text-xs">{{ $rental->delivery_location ?: 'No movement location' }}</div>
                </td>
                <td>{{ $rental->customer?->company_name ?? 'Unknown customer' }}</td>
                <td>{{ $dateResolver($rental) }}</td>
                <td>
                    <strong>{{ $rental->rentalItems->count() }}</strong>
                    <span class="text-muted">assets</span>
                    @if($rental->rentalItems->isNotEmpty())
                        <div class="text-muted text-xs">{{ $rental->rentalItems->pluck('product.name')->filter()->take(2)->join(', ') }}</div>
                    @endif
                </td>
                <td><span class="badge {{ $statusBadge($rental->status) }}">{{ str($rental->status ?: 'reserved')->headline() }}</span></td>
                <td>
                    <div class="table-actions justify-content-end">
                        <a href="{{ route('rentals.show', $rental) }}" class="btn btn-sm btn-outline-secondary">
                            <x-lucide-eye class="w-4 h-4"/>
                            Rental
                        </a>

                        @if($rental->agreement)
                            <a href="{{ route('agreements.show', $rental->agreement) }}" class="btn btn-sm btn-primary">
                                <x-lucide-file-signature class="w-4 h-4"/>
                                {{ $actionMode === 'return' ? 'Return' : 'Check-Out' }}
                            </a>
                        @else
                            <form method="POST" action="{{ route('rentals.agreements.store', $rental) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <x-lucide-file-plus-2 class="w-4 h-4"/>
                                    Agreement
                                </button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">{{ $emptyText }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

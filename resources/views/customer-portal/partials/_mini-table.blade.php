<section class="panel h-100">
    <div class="panel-header">
        <div>
            <h2>{{ $title }}</h2>
            <p><a href="{{ route($route) }}" wire:navigate>View all</a></p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table modern-table align-middle">
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>
                        <strong>{{ $row->quote_number ?? $row->invoice_number ?? 'RTN-'.$row->id }}</strong>
                        <span class="d-block text-muted small">{{ str($row->status)->headline() }}</span>
                    </td>
                </tr>
            @empty
                <tr><td class="text-center text-muted py-4">{{ $empty }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

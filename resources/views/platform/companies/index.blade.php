@extends('layouts.platform')

@section('title', 'Companies')

@section('content')
    <div class="container-fluid erm-page">
        <div class="page-header">
            <div>
                <span class="eyebrow">Platform Admin</span>
                <h1>Registered Companies</h1>
                <p>Every tenant workspace, owner access, subscription state, and usage footprint.</p>
            </div>
        </div>

        <section class="panel">
            <div class="table-responsive">
                <table class="table align-middle modern-table">
                    <thead>
                    <tr>
                        <th>Company</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Next Billing</th>
                        <th>Users</th>
                        <th>Equipment</th>
                        <th>Rentals</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($companies as $company)
                        <tr>
                            <td>
                                <strong>{{ $company->name }}</strong>
                                <div class="text-muted text-xs">{{ $company->email ?? 'No billing email' }}</div>
                            </td>
                            <td>{{ $company->subscription?->plan?->name ?? 'No plan' }}</td>
                            <td>@include('platform.partials._subscription-status', ['status' => $company->subscription?->status])</td>
                            <td>{{ $company->subscription?->next_billing_at?->format('M d, Y') ?? 'Not scheduled' }}</td>
                            <td>{{ number_format($company->users_count) }}</td>
                            <td>{{ number_format($company->products_count) }}</td>
                            <td>{{ number_format($company->rentals_count) }}</td>
                            <td class="text-end">
                                <a href="{{ route('platform.companies.show', $company) }}" class="btn btn-sm btn-outline-secondary">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No companies registered yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <x-pagination :paginator="$companies"/>
            </div>
        </section>
    </div>
@endsection

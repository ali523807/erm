@extends('layouts.platform')

@section('title', 'Platform')

@section('content')
    <div class="container-fluid erm-page">
        <div class="page-header">
            <div>
                <span class="eyebrow">Platform Admin</span>
                <h1>Client and Subscription Control</h1>
                <p>Track registered rental companies, subscription status, billing dates, and recurring revenue.</p>
            </div>
            <a href="{{ route('platform.companies.index') }}" class="btn btn-dark">
                <x-lucide-building-2 class="w-4 h-4 me-1"/>
                View Companies
            </a>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="metric-card">
                    <div class="metric-icon bg-blue-50 text-blue-700">
                        <x-lucide-building-2 class="w-5 h-5"/>
                    </div>
                    <span>Registered Companies</span>
                    <strong>{{ number_format($companyCount) }}</strong>
                    <small>{{ number_format($activeCompanyCount) }} active clients</small>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="metric-card">
                    <div class="metric-icon bg-green-50 text-green-700">
                        <x-lucide-badge-check class="w-5 h-5"/>
                    </div>
                    <span>Active Subscriptions</span>
                    <strong>{{ number_format($activeSubscriptionCount) }}</strong>
                    <small>{{ number_format($trialingSubscriptionCount) }} trials</small>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="metric-card">
                    <div class="metric-icon bg-yellow-50 text-yellow-700">
                        <x-lucide-wallet-cards class="w-5 h-5"/>
                    </div>
                    <span>Monthly Run Rate</span>
                    <strong>${{ number_format($monthlyRecurringRevenue, 2) }}</strong>
                    <small>Trialing and active plans</small>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="metric-card">
                    <div class="metric-icon bg-red-50 text-red-700">
                        <x-lucide-layers-3 class="w-5 h-5"/>
                    </div>
                    <span>Plans</span>
                    <strong>{{ number_format($planCount) }}</strong>
                    <small>Available SaaS packages</small>
                </div>
            </div>
        </div>

        <section class="panel">
            <div class="panel-header">
                <div>
                    <h2>Recent Companies</h2>
                    <p>Newest tenants and their current billing state.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle modern-table">
                    <thead>
                    <tr>
                        <th>Company</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Next Billing</th>
                        <th>Usage</th>
                        <th class="text-end">MRR</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($companies as $company)
                        <tr>
                            <td>
                                <a href="{{ route('platform.companies.show', $company) }}" class="fw-bold text-gray-900">
                                    {{ $company->name }}
                                </a>
                                <div class="text-muted text-xs">{{ $company->email ?? 'No billing email' }}</div>
                            </td>
                            <td>{{ $company->subscription?->plan?->name ?? 'No plan' }}</td>
                            <td>@include('platform.partials._subscription-status', ['status' => $company->subscription?->status])</td>
                            <td>{{ $company->subscription?->next_billing_at?->format('M d, Y') ?? 'Not scheduled' }}</td>
                            <td>
                                {{ $company->products_count }} equipment,
                                {{ $company->users_count }} users
                            </td>
                            <td class="text-end">${{ number_format($company->subscription?->amount ?? 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No companies registered yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

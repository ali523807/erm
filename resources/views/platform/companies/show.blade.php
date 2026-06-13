@extends('layouts.platform')

@section('title', $company->name)

@section('content')
    @php
        $moduleCatalog = app(\App\Support\SubscriptionModuleCatalog::class);
        $currentPlan = $company->subscription?->plan;
        $moduleMatrix = $moduleCatalog->matrixForPlan($currentPlan);
    @endphp

    <div class="container-fluid erm-page">
        <div class="page-header">
            <div>
                <span class="eyebrow">Client Workspace</span>
                <h1>{{ $company->name }}</h1>
                <p>{{ $company->email ?? 'No billing email configured' }}</p>
            </div>
            <a href="{{ route('platform.companies.index') }}" class="btn btn-outline-secondary">
                <x-lucide-arrow-left class="w-4 h-4 me-1"/>
                Companies
            </a>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="metric-card">
                    <span>Subscription</span>
                    <strong>{{ $company->subscription?->plan?->name ?? 'No Plan' }}</strong>
                    <small>@include('platform.partials._subscription-status', ['status' => $company->subscription?->status])</small>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="metric-card">
                    <span>Monthly Amount</span>
                    <strong>${{ number_format($company->subscription?->amount ?? 0, 2) }}</strong>
                    <small>{{ strtoupper($company->subscription?->currency ?? 'USD') }}</small>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="metric-card">
                    <span>Next Billing</span>
                    <strong>{{ $company->subscription?->next_billing_at?->format('M d') ?? 'None' }}</strong>
                    <small>{{ $company->subscription?->next_billing_at?->format('Y') ?? 'Not scheduled' }}</small>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="metric-card">
                    <span>Usage</span>
                    <strong>{{ number_format($company->products_count) }}</strong>
                    <small>equipment records</small>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-xl-7">
                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <h2>Manage Subscription</h2>
                            <p>Update plan, billing status, period dates, and manual billing notes.</p>
                        </div>
                    </div>

                    @if(session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    <form method="POST" action="{{ route('platform.companies.subscription.update', $company) }}" class="subscription-form">
                        @csrf
                        @method('PATCH')

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="subscription_plan_id" class="form-label">Plan</label>
                                <select id="subscription_plan_id" name="subscription_plan_id" class="form-select @error('subscription_plan_id') is-invalid @enderror">
                                    @foreach($plans as $plan)
                                        <option value="{{ $plan->id }}" @selected(old('subscription_plan_id', $company->subscription?->subscription_plan_id) == $plan->id)>
                                            {{ $plan->name }} - ${{ number_format($plan->monthly_price, 2) }}/mo
                                        </option>
                                    @endforeach
                                </select>
                                @error('subscription_plan_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                                    @foreach(['trialing', 'active', 'past_due', 'cancelled'] as $status)
                                        <option value="{{ $status }}" @selected(old('status', $company->subscription?->status ?? 'trialing') === $status)>
                                            {{ str($status)->headline() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="billing_cycle" class="form-label">Billing Cycle</label>
                                <select id="billing_cycle" name="billing_cycle" class="form-select @error('billing_cycle') is-invalid @enderror">
                                    @foreach(['monthly', 'yearly'] as $cycle)
                                        <option value="{{ $cycle }}" @selected(old('billing_cycle', $company->subscription?->billing_cycle ?? 'monthly') === $cycle)>
                                            {{ str($cycle)->headline() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('billing_cycle')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12 col-md-4">
                                <x-input label="Amount" type="number" name="amount" id="amount" step="0.01" min="0"
                                         value="{{ old('amount', $company->subscription?->amount ?? 0) }}"/>
                            </div>

                            <div class="col-12 col-md-4">
                                <x-input label="Currency" name="currency" id="currency" maxlength="3"
                                         value="{{ old('currency', $company->subscription?->currency ?? 'USD') }}"/>
                            </div>

                            <div class="col-12 col-md-6">
                                <x-input label="Trial Ends" type="date" name="trial_ends_at" id="trial_ends_at"
                                         value="{{ old('trial_ends_at', $company->subscription?->trial_ends_at?->format('Y-m-d')) }}"/>
                            </div>

                            <div class="col-12 col-md-6">
                                <x-input label="Next Billing" type="date" name="next_billing_at" id="next_billing_at"
                                         value="{{ old('next_billing_at', $company->subscription?->next_billing_at?->format('Y-m-d')) }}"/>
                            </div>

                            <div class="col-12 col-md-6">
                                <x-input label="Period Starts" type="date" name="current_period_starts_at" id="current_period_starts_at"
                                         value="{{ old('current_period_starts_at', $company->subscription?->current_period_starts_at?->format('Y-m-d')) }}"/>
                            </div>

                            <div class="col-12 col-md-6">
                                <x-input label="Period Ends" type="date" name="current_period_ends_at" id="current_period_ends_at"
                                         value="{{ old('current_period_ends_at', $company->subscription?->current_period_ends_at?->format('Y-m-d')) }}"/>
                            </div>

                            <div class="col-12">
                                <label for="notes" class="form-label">Internal Notes</label>
                                <textarea id="notes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $company->subscription?->notes) }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-dark">
                                <x-lucide-save class="w-4 h-4 me-1"/>
                                Save Subscription
                            </button>
                        </div>
                    </form>
                </section>
            </div>
            <div class="col-12 col-xl-5">
                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <h2>Users</h2>
                            <p>People attached to this tenant.</p>
                        </div>
                    </div>
                    <div class="roadmap-list">
                        @foreach($company->users as $user)
                            <div class="roadmap-item">
                                <span>{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                <div>
                                    <strong>{{ $user->name }}</strong>
                                    <p>{{ $user->email }} · {{ str($user->pivot->role)->headline() }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="panel mt-3">
                    <div class="panel-header">
                        <div>
                            <h2>Plan Modules</h2>
                            <p>Modules currently available to this company from the selected subscription plan.</p>
                        </div>
                    </div>
                    <div class="module-entitlement-list">
                        @foreach($moduleMatrix as $module)
                            <div class="{{ $module['included'] ? 'is-included' : 'is-excluded' }}">
                                <span>
                                    @if($module['included'])
                                        <x-lucide-check class="w-4 h-4"/>
                                    @else
                                        <x-lucide-lock class="w-4 h-4"/>
                                    @endif
                                </span>
                                <div>
                                    <strong>{{ $module['label'] }}</strong>
                                    <p>{{ $module['description'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection

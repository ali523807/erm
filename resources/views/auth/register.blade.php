@extends('layouts.auth')

@section('title', 'Register')

@section('content')
    @php($money = app(\App\Support\Money::class))
    <div class="auth-card-header">
        <span class="auth-icon">
            <x-lucide-building-2 class="w-5 h-5"/>
        </span>
        <span class="eyebrow">Company Registration</span>
        <h1>Create your rental workspace</h1>
        <p>Register your company, choose a subscription plan, and start setting up equipment, customers, rentals, and billing.</p>
    </div>

    <x-form class="auth-form" method="POST" action="{{ route('register') }}">
        <input type="hidden" name="plan" value="{{ old('plan', $selectedPlan?->slug) }}">

        @if($selectedPlan)
            <div class="selected-plan-card">
                <div>
                    <span class="eyebrow">Selected Plan</span>
                    <strong>{{ $selectedPlan->name }}</strong>
                    <p>{{ $selectedPlan->description }}</p>
                    <div class="selected-plan-meta">
                        <span>{{ $selectedPlan->user_limit ? number_format($selectedPlan->user_limit).' users' : 'Unlimited users' }}</span>
                        <span>{{ $selectedPlan->equipment_limit ? number_format($selectedPlan->equipment_limit).' equipment' : 'Unlimited equipment' }}</span>
                    </div>
                </div>
                <div class="selected-plan-price">
                    <strong>{{ $money->format($selectedPlan->monthly_price, 'USD') }}</strong>
                    <span>/ month</span>
                </div>
            </div>
        @endif

        <div class="auth-form-grid">
            <x-input placeholder="Avery Stone" label="Full Name" name="name" id="name"/>

            <x-input placeholder="Northstar Rentals" label="Company Name" name="company_name" id="company_name"/>
        </div>

        <x-input placeholder="owner@company.com" type="email" label="E-mail Address" name="email" id="email"/>

        <div class="auth-form-grid">
            <x-input placeholder="Password" type="password" label="Password" name="password" id="password"/>

            <x-input label="Confirm Password" placeholder="Confirm New Password" type="password"
                     name="password_confirmation" id="password_confirmation"/>
        </div>

        <x-button type="submit" color="dark" class="w-100 auth-submit">
            <x-lucide-rocket class="w-4 h-4"/>
            Create Workspace
        </x-button>

        <div class="auth-switch">
            <span>Already have an account?</span>
            <a wire:navigate href="{{ route('login') }}">Login</a>
        </div>

        <div class="auth-switch secondary">
            <span>Want another plan?</span>
            <a wire:navigate href="{{ route('landing') }}#pricing">View pricing</a>
        </div>
    </x-form>
@endsection

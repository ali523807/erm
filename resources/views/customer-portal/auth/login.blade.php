@extends('layouts.auth')

@section('title', 'Customer Portal Login')

@section('content')
    <div class="space-y-2 text-center">
        <h1 class="text-xl font-medium">Customer Portal</h1>
        <p class="text-center text-sm text-muted mb-4">View quotes, rentals, invoices, receipts, and shared documents.</p>
    </div>

    <x-form class="space-y-4" method="POST" action="{{ route('customer-portal.login.store') }}">
        <x-input placeholder="customer@example.com" type="email" label="E-mail Address" name="email" id="email" value="{{ old('email') }}"/>
        <x-input placeholder="Password" type="password" label="Password" name="password" id="password"/>
        <x-checkbox name="remember" id="remember" label="Remember Me"/>
        <x-button type="submit" color="dark" class="w-100 mt-3">Log In</x-button>

        <div class="text-center text-sm text-muted mt-4 authentication">
            Company staff?
            <a wire:navigate class="text-decoration-underline text-gray-800" href="{{ route('login') }}">Use company login</a>
        </div>
    </x-form>
@endsection

@extends('layouts.auth')

@section('title', 'Platform Login')

@section('content')
    <div class="space-y-2 text-center">
        <h1 class="text-xl font-medium">Platform Admin Login</h1>
        <p class="text-center text-sm text-muted mb-4">Manage client companies, subscriptions, and billing.</p>
    </div>

    <x-form class="space-y-4" method="POST" action="{{ route('platform.login.store') }}">
        <x-input placeholder="owner@example.com" type="email" label="E-mail Address" name="email" id="email"/>

        <x-input placeholder="Password" type="password" label="Password" name="password" id="password"/>

        <x-checkbox name="remember" id="remember" label="Remember Me"/>

        <x-button type="submit" color="dark" class="w-100 mt-3">Log In</x-button>

        <div class="text-center text-sm text-muted mt-4 authentication">
            Client workspace?
            <a wire:navigate class="text-decoration-underline text-gray-800" href="{{ route('login') }}">
                Use company login
            </a>
        </div>
    </x-form>
@endsection

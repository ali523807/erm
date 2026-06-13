@extends('layouts.auth')

@section('title', 'Log in')

@section('content')

    <div class="auth-card-header">
        <span class="auth-icon">
            <x-lucide-log-in class="w-5 h-5"/>
        </span>
        <span class="eyebrow">Company Login</span>
        <h1>Welcome back</h1>
        <p>Sign in to manage rentals, equipment, customers, billing, reports, and daily operations.</p>
    </div>

    <x-form class="auth-form" method="POST" action="{{ route('login') }}">
        <x-input placeholder="email@example.com" type="email" label="E-mail Address" name="email" id="email"/>

        <x-input placeholder="Password" type="password" label="Password" name="password" id="password"/>

        <div class="auth-form-row">
            <x-checkbox name="remember_me" id="remember_me" label="Remember Me"/>
            <a wire:navigate href="{{ route('password.request') }}">Forgot password?</a>
        </div>

        <x-button type="submit" color="dark" class="w-100 auth-submit">
            <x-lucide-arrow-right class="w-4 h-4"/>
            Login
        </x-button>

        <div class="auth-switch">
            <span>New rental company?</span>
            <a wire:navigate href="{{ route('register') }}">Start free trial</a>
        </div>
    </x-form>
@endsection

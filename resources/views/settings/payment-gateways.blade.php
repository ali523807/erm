@extends('settings.layout')

@section('title', 'Payment gateways')

@section('settings.content')
    <section class="panel">
        <div class="panel-header align-items-start">
            <div>
                <h2>Payment Gateway Settings</h2>
                <p>Choose the active provider for invoice payment links and store gateway credentials for checkout/webhook integration.</p>
            </div>
        </div>

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="row g-3">
            @foreach($gatewaySettings as $setting)
                @php
                    $providerLabel = $providers[$setting->provider] ?? str($setting->provider)->headline();
                    $isConfigured = $setting->provider === 'manual' || ($setting->public_key && $setting->secret_key);
                @endphp
                <div class="col-12">
                    <section class="panel border h-100">
                        <div class="panel-header align-items-start">
                            <div>
                                <h2>{{ $providerLabel }}</h2>
                                <p>
                                    @if($setting->provider === 'manual')
                                        Demo mode records payment against invoices without contacting an external gateway.
                                    @else
                                        Save credentials now. Hosted checkout and webhook handling will use these values when the provider adapter is connected.
                                    @endif
                                </p>
                            </div>
                            <span class="badge {{ $setting->is_active ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                {{ $setting->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <form method="POST" action="{{ route('settings.payment-gateways.update', $setting->provider) }}" class="row g-3">
                            @csrf
                            @method('PUT')

                            <div class="col-md-3">
                                <label for="{{ $setting->provider }}_mode" class="form-label">Mode</label>
                                <select id="{{ $setting->provider }}_mode" name="mode" class="form-select">
                                    <option value="test" @selected(old('mode', $setting->mode) === 'test')>Test</option>
                                    <option value="live" @selected(old('mode', $setting->mode) === 'live')>Live</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label d-block">Status</label>
                                <label class="form-check mt-2">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $setting->is_active))>
                                    <span class="form-check-label">Use for new payment links</span>
                                </label>
                            </div>

                            <div class="col-md-6">
                                <label for="{{ $setting->provider }}_account_reference" class="form-label">Account Reference</label>
                                <input id="{{ $setting->provider }}_account_reference" name="account_reference" class="form-control" value="{{ old('account_reference', $setting->account_reference) }}" placeholder="Merchant ID, account name, or connected account">
                            </div>

                            @if($setting->provider !== 'manual')
                                <div class="col-md-6">
                                    <label for="{{ $setting->provider }}_public_key" class="form-label">Public Key</label>
                                    <input id="{{ $setting->provider }}_public_key" name="public_key" class="form-control" value="{{ old('public_key', $setting->public_key) }}" placeholder="Publishable / key id">
                                </div>

                                <div class="col-md-6">
                                    <label for="{{ $setting->provider }}_secret_key" class="form-label">Secret Key</label>
                                    <input id="{{ $setting->provider }}_secret_key" name="secret_key" type="password" class="form-control" placeholder="{{ $setting->secret_key ? 'Saved. Enter a new value to replace.' : 'Secret / private key' }}">
                                </div>

                                <div class="col-md-6">
                                    <label for="{{ $setting->provider }}_webhook_secret" class="form-label">Webhook Secret</label>
                                    <input id="{{ $setting->provider }}_webhook_secret" name="webhook_secret" type="password" class="form-control" placeholder="{{ $setting->webhook_secret ? 'Saved. Enter a new value to replace.' : 'Webhook signing secret' }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label d-block">Readiness</label>
                                    <span class="badge {{ $isConfigured ? 'badge-soft-success' : 'badge-soft-warning' }}">
                                        {{ $isConfigured ? 'Credentials Saved' : 'Missing Keys' }}
                                    </span>
                                </div>
                            @endif

                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-dark">
                                    <x-lucide-save class="w-4 h-4"/>
                                    Save Gateway
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            @endforeach
        </div>
    </section>
@endsection

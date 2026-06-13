<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\PaymentGatewaySetting;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentGatewayController extends Controller
{
    public function __construct(private PaymentGatewayManager $gateways) {}

    public function index(): View
    {
        $company = auth()->user()->currentCompany;

        return view('settings.payment-gateways', [
            'providers' => $this->gateways->providers(),
            'gatewaySettings' => $this->gateways->settingsFor($company),
        ]);
    }

    public function update(Request $request, string $provider): RedirectResponse
    {
        $providers = array_keys($this->gateways->providers());
        abort_unless(in_array($provider, $providers, true), 404);

        $validated = $request->validate([
            'mode' => ['required', Rule::in(['test', 'live'])],
            'is_active' => ['nullable', 'boolean'],
            'public_key' => ['nullable', 'string', 'max:255'],
            'secret_key' => ['nullable', 'string', 'max:1000'],
            'webhook_secret' => ['nullable', 'string', 'max:1000'],
            'account_reference' => ['nullable', 'string', 'max:255'],
        ]);

        $company = auth()->user()->currentCompany;

        if ($request->boolean('is_active')) {
            PaymentGatewaySetting::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->update(['is_active' => false]);
        }

        $setting = PaymentGatewaySetting::withoutGlobalScopes()->firstOrNew([
            'company_id' => $company->id,
            'provider' => $provider,
        ]);

        $setting->fill([
            'mode' => $validated['mode'],
            'is_active' => $request->boolean('is_active'),
            'public_key' => $validated['public_key'] ?? null,
            'account_reference' => $validated['account_reference'] ?? null,
        ]);

        if ($request->filled('secret_key')) {
            $setting->secret_key = $validated['secret_key'];
        }

        if ($request->filled('webhook_secret')) {
            $setting->webhook_secret = $validated['webhook_secret'];
        }

        $setting->save();

        return back()->with('status', str($this->gateways->providers()[$provider])->headline().' gateway settings updated.');
    }
}

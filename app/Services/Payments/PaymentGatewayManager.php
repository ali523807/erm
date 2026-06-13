<?php

namespace App\Services\Payments;

use App\Models\Company;
use App\Models\PaymentGatewaySetting;
use Illuminate\Support\Collection;

class PaymentGatewayManager
{
    /**
     * @return array<string, string>
     */
    public function providers(): array
    {
        return [
            'manual' => 'Manual / Demo',
            'stripe' => 'Stripe',
            'razorpay' => 'Razorpay',
            'paypal' => 'PayPal',
        ];
    }

    public function gateway(string $provider): PaymentGateway
    {
        return match ($provider) {
            'stripe' => new ConfiguredGatewayPlaceholder('stripe', 'Stripe'),
            'razorpay' => new ConfiguredGatewayPlaceholder('razorpay', 'Razorpay'),
            'paypal' => new ConfiguredGatewayPlaceholder('paypal', 'PayPal'),
            default => new ManualPaymentGateway,
        };
    }

    public function activeSetting(Company $company): PaymentGatewaySetting
    {
        $active = PaymentGatewaySetting::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderByRaw("provider = 'manual' asc")
            ->first();

        if ($active) {
            return $active;
        }

        return PaymentGatewaySetting::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'provider' => 'manual',
            'mode' => 'test',
            'is_active' => true,
        ]);
    }

    /**
     * @return Collection<int, PaymentGatewaySetting>
     */
    public function settingsFor(Company $company): Collection
    {
        $existing = PaymentGatewaySetting::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->get()
            ->keyBy('provider');

        foreach ($this->providers() as $provider => $label) {
            if (! $existing->has($provider)) {
                $existing->put($provider, new PaymentGatewaySetting([
                    'company_id' => $company->id,
                    'provider' => $provider,
                    'mode' => 'test',
                    'is_active' => $provider === 'manual',
                ]));
            }
        }

        return $existing->values();
    }
}

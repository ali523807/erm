<?php

namespace App\Services\Payments;

use App\Models\InvoicePaymentLink;
use App\Models\PaymentGatewaySetting;

class ConfiguredGatewayPlaceholder implements PaymentGateway
{
    public function __construct(
        private readonly string $provider,
        private readonly string $displayName,
    ) {}

    public function provider(): string
    {
        return $this->provider;
    }

    public function displayName(): string
    {
        return $this->displayName;
    }

    public function isConfigured(?PaymentGatewaySetting $setting): bool
    {
        return (bool) ($setting?->is_active && $setting->public_key && $setting->secret_key);
    }

    public function checkoutSession(InvoicePaymentLink $paymentLink, ?PaymentGatewaySetting $setting): array
    {
        return [
            'mode' => $setting?->mode ?? 'test',
            'provider' => $this->provider(),
            'is_live' => false,
            'checkout_url' => null,
            'message' => $this->displayName().' credentials are saved. Hosted checkout and webhooks are not connected yet.',
        ];
    }
}

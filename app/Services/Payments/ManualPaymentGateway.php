<?php

namespace App\Services\Payments;

use App\Models\InvoicePaymentLink;
use App\Models\PaymentGatewaySetting;

class ManualPaymentGateway implements PaymentGateway
{
    public function provider(): string
    {
        return 'manual';
    }

    public function displayName(): string
    {
        return 'Manual / Demo';
    }

    public function isConfigured(?PaymentGatewaySetting $setting): bool
    {
        return true;
    }

    public function checkoutSession(InvoicePaymentLink $paymentLink, ?PaymentGatewaySetting $setting): array
    {
        return [
            'mode' => $setting?->mode ?? 'test',
            'provider' => $this->provider(),
            'is_live' => false,
            'checkout_url' => null,
            'message' => 'Manual demo mode records the payment inside RentalHook without contacting a gateway.',
        ];
    }
}

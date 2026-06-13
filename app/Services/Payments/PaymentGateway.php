<?php

namespace App\Services\Payments;

use App\Models\InvoicePaymentLink;
use App\Models\PaymentGatewaySetting;

interface PaymentGateway
{
    public function provider(): string;

    public function displayName(): string;

    public function isConfigured(?PaymentGatewaySetting $setting): bool;

    /**
     * @return array{mode: string, provider: string, is_live: bool, checkout_url: string|null, message: string}
     */
    public function checkoutSession(InvoicePaymentLink $paymentLink, ?PaymentGatewaySetting $setting): array;
}

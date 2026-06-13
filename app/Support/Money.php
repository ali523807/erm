<?php

namespace App\Support;

class Money
{
    /**
     * @var array<string, string>
     */
    private array $symbols = [
        'USD' => '$',
        'AED' => 'AED',
        'INR' => 'Rs',
        'GBP' => 'GBP',
        'EUR' => 'EUR',
        'CAD' => 'CAD',
        'AUD' => 'AUD',
        'SAR' => 'SAR',
        'SGD' => 'SGD',
        'ZAR' => 'ZAR',
    ];

    public function format(float|int|string|null $amount, ?string $currency = null): string
    {
        $currency = strtoupper($currency ?: auth()->user()?->currentCompany?->currency ?: 'USD');
        $symbol = $this->symbols[$currency] ?? $currency;

        return $symbol.' '.number_format((float) $amount, 2);
    }
}

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay {{ $paymentLink->invoice?->invoice_number }} - {{ config('app.name') }}</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <style>
        body {
            background: #f5f7fb;
        }

        .payment-shell {
            align-items: center;
            display: flex;
            justify-content: center;
            min-height: 100vh;
            padding: 32px 16px;
        }

        .payment-card {
            background: #ffffff;
            border: 1px solid #dde4ee;
            border-radius: 8px;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.08);
            max-width: 820px;
            padding: 32px;
            width: 100%;
        }

        .payment-brand {
            align-items: center;
            background: #111827;
            border-radius: 8px;
            color: #ffffff;
            display: inline-flex;
            height: 44px;
            justify-content: center;
            margin-bottom: 18px;
            width: 44px;
        }

        .payment-summary {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin: 24px 0;
        }

        .payment-summary-item {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
        }

        .payment-summary-item dt {
            color: #53657d;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .payment-summary-item dd {
            color: #020617;
            font-size: 18px;
            font-weight: 800;
            margin: 0;
        }

        .payment-form {
            border-top: 1px solid #e2e8f0;
            padding-top: 24px;
        }

        @media (max-width: 640px) {
            .payment-card {
                padding: 22px;
            }

            .payment-summary {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    @php
        $invoice = $paymentLink->invoice;
        $money = app(\App\Support\Money::class);
        $isPayable = $paymentLink->isPayable();
        $isManualProvider = $paymentLink->provider === 'manual';
    @endphp

    <main class="payment-shell">
        <section class="payment-card">
            <div class="text-center mb-4">
                <span class="payment-brand"><x-lucide-credit-card class="w-5 h-5"/></span>
                <h1 class="h3 mb-1">Invoice Payment</h1>
                <p class="text-muted mb-0">{{ $invoice?->customer?->company_name }} - {{ $invoice?->invoice_number }}</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <dl class="payment-summary">
                <div class="payment-summary-item">
                    <dt>Invoice Total</dt>
                    <dd>{{ $money->format($invoice?->total_amount, $invoice?->currency) }}</dd>
                </div>
                <div class="payment-summary-item">
                    <dt>Balance Due</dt>
                    <dd>{{ $money->format($invoice?->balance_due, $invoice?->currency) }}</dd>
                </div>
                <div class="payment-summary-item">
                    <dt>Payment Amount</dt>
                    <dd>{{ $money->format($paymentLink->amount, $paymentLink->currency) }}</dd>
                </div>
                <div class="payment-summary-item">
                    <dt>Status</dt>
                    <dd>{{ str($paymentLink->status)->headline() }}</dd>
                </div>
                <div class="payment-summary-item">
                    <dt>Provider</dt>
                    <dd>{{ str($paymentLink->provider)->headline() }}</dd>
                </div>
                <div class="payment-summary-item">
                    <dt>Expires</dt>
                    <dd>{{ $paymentLink->expires_at?->format('Y-m-d H:i') ?: 'No expiry' }}</dd>
                </div>
            </dl>

            @if($paymentLink->status === 'paid')
                <div class="alert alert-success">
                    Payment has been recorded.
                    @if($paymentLink->payment)
                        <a href="{{ route('payment-links.receipt', $paymentLink->token) }}">Download receipt</a>.
                    @endif
                </div>
            @elseif(! $isPayable)
                <div class="alert alert-warning">This payment link is no longer available.</div>
            @elseif(! $isManualProvider)
                <div class="alert alert-info">
                    {{ $paymentLink->metadata['gateway_message'] ?? str($paymentLink->provider)->headline().' checkout is not connected yet.' }}
                </div>
            @else
                <form method="POST" action="{{ route('payment-links.pay', $paymentLink->token) }}" class="payment-form text-start">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="payer_name" class="form-label">Payer Name</label>
                            <input id="payer_name" name="payer_name" class="form-control" value="{{ old('payer_name', $invoice?->customer?->contact_person) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="payer_email" class="form-label">Payer Email</label>
                            <input id="payer_email" name="payer_email" type="email" class="form-control" value="{{ old('payer_email', $invoice?->customer?->email) }}" required>
                        </div>
                        <div class="col-12">
                            <label for="reference" class="form-label">Payment Reference</label>
                            <input id="reference" name="reference" class="form-control" value="{{ old('reference') }}" placeholder="Card, transfer, gateway, or approval reference">
                            <div class="form-text">Current demo mode records the payment immediately. A live gateway can replace this step later.</div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-dark w-100 mt-4">
                        <x-lucide-lock-keyhole class="w-4 h-4"/>
                        Record Secure Payment
                    </button>
                </form>
            @endif
        </section>
    </main>
</body>
</html>

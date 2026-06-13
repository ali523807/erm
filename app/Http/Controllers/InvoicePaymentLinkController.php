<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePaymentLink;
use App\Services\ActivityLogger;
use App\Services\Payments\PaymentGatewayManager;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class InvoicePaymentLinkController extends Controller
{
    public function __construct(
        private ActivityLogger $activity,
        private PaymentGatewayManager $gateways,
    ) {}

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        $invoice->recalculateTotals();
        abort_if((float) $invoice->balance_due <= 0, 422, 'This invoice has no balance due.');

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$invoice->balance_due],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'provider' => ['nullable', Rule::in(array_keys($this->gateways->providers()))],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $selectedProvider = $validated['provider'] ?? null;
        $gatewaySetting = $selectedProvider
            ? $invoice->company->paymentGatewaySettings()->withoutGlobalScopes()->where('provider', $selectedProvider)->first()
            : $this->gateways->activeSetting($invoice->company);
        $provider = $selectedProvider ?? $gatewaySetting->provider;
        $gateway = $this->gateways->gateway($provider);
        $checkoutSession = $gateway->checkoutSession(new InvoicePaymentLink([
            'amount' => $validated['amount'],
            'currency' => $invoice->currency,
            'provider' => $provider,
        ]), $gatewaySetting);

        $link = $invoice->paymentLinks()->create([
            'company_id' => $invoice->company_id,
            'created_by' => $request->user()->id,
            'token' => $this->uniqueToken(),
            'amount' => $validated['amount'],
            'currency' => $invoice->currency,
            'status' => 'active',
            'provider' => $provider,
            'provider_reference' => $checkoutSession['checkout_url'],
            'expires_at' => $validated['expires_at'] ?? now()->addDays(7),
            'metadata' => [
                'notes' => $validated['notes'] ?? null,
                'generated_from' => 'invoice_detail',
                'gateway_mode' => $checkoutSession['mode'],
                'gateway_message' => $checkoutSession['message'],
            ],
        ]);

        $this->activity->log('payment_links', 'created', "Generated payment link for invoice {$invoice->invoice_number}.", $link, [
            'invoice_id' => $invoice->id,
            'amount' => $link->amount,
            'provider' => $link->provider,
        ]);

        return back()->with('success', 'Payment link generated successfully.');
    }

    public function cancel(InvoicePaymentLink $paymentLink): RedirectResponse
    {
        abort_if($paymentLink->status !== 'active', 422, 'Only active payment links can be cancelled.');

        $paymentLink->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $this->activity->log('payment_links', 'cancelled', 'Cancelled payment link.', $paymentLink, [
            'invoice_id' => $paymentLink->invoice_id,
        ]);

        return back()->with('success', 'Payment link cancelled.');
    }

    public function portalPay(Request $request, Invoice $invoice): RedirectResponse
    {
        $portalUser = $request->user('customer');

        abort_unless(
            (int) $invoice->company_id === (int) $portalUser->company_id
            && (int) $invoice->customer_id === (int) $portalUser->customer_id,
            404
        );

        $invoice->recalculateTotals();
        abort_if((float) $invoice->balance_due <= 0, 422, 'This invoice has no balance due.');

        $link = $invoice->paymentLinks()
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();

        if (! $link) {
            $gatewaySetting = $this->gateways->activeSetting($invoice->company);
            $link = $invoice->paymentLinks()->create([
                'company_id' => $invoice->company_id,
                'token' => $this->uniqueToken(),
                'amount' => $invoice->balance_due,
                'currency' => $invoice->currency,
                'status' => 'active',
                'provider' => $gatewaySetting->provider,
                'expires_at' => now()->addDays(7),
                'metadata' => [
                    'generated_from' => 'customer_portal',
                    'portal_user_id' => $portalUser->id,
                    'gateway_mode' => $gatewaySetting->mode,
                ],
            ]);
        }

        return redirect()->route('payment-links.show', $link->token);
    }

    public function show(string $token): View
    {
        $paymentLink = $this->findPublicLink($token);
        $paymentLink->invoice->recalculateTotals();
        $paymentLink->refresh()->load(['invoice.customer', 'invoice.rental', 'payment']);

        if ($paymentLink->status === 'active' && $paymentLink->expires_at?->isPast()) {
            $paymentLink->update(['status' => 'expired']);
        }

        return view('payment-links.show', [
            'paymentLink' => $paymentLink,
        ]);
    }

    public function pay(Request $request, string $token): RedirectResponse
    {
        $paymentLink = $this->findPublicLink($token);
        $paymentLink->load('invoice');
        $paymentLink->invoice->recalculateTotals();
        $paymentLink->refresh()->load('invoice');

        abort_unless($paymentLink->isPayable(), 422, 'This payment link is no longer payable.');

        $validated = $request->validate([
            'payer_name' => ['required', 'string', 'max:255'],
            'payer_email' => ['required', 'email', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($paymentLink, $validated): void {
            $invoice = $paymentLink->invoice;
            $amount = min((float) $paymentLink->amount, (float) $invoice->balance_due);

            $payment = $invoice->payments()->create([
                'company_id' => $invoice->company_id,
                'payment_date' => now()->toDateString(),
                'amount' => $amount,
                'method' => 'online',
                'reference' => $validated['reference'] ?: 'PAYLINK-'.$paymentLink->id,
                'notes' => "Online payment link paid by {$validated['payer_name']} ({$validated['payer_email']}).",
            ]);

            $paymentLink->update([
                'status' => 'paid',
                'invoice_payment_id' => $payment->id,
                'paid_at' => now(),
                'provider_reference' => $payment->reference,
                'metadata' => [
                    ...($paymentLink->metadata ?? []),
                    'payer_name' => $validated['payer_name'],
                    'payer_email' => $validated['payer_email'],
                ],
            ]);

            $invoice->recalculateTotals();
        });

        return redirect()
            ->route('payment-links.show', $paymentLink->token)
            ->with('success', 'Payment recorded successfully. Thank you.');
    }

    public function receipt(string $token): Response
    {
        $paymentLink = $this->findPublicLink($token);
        abort_unless($paymentLink->status === 'paid' && $paymentLink->payment, 404);

        $paymentLink->payment->load(['invoice.company', 'invoice.customer', 'invoice.rental']);

        return Pdf::loadView('payments.receipt-pdf', [
            'payment' => $paymentLink->payment,
        ])
            ->setPaper('a4')
            ->download('Receipt-'.$paymentLink->payment->receiptNumber().'.pdf');
    }

    private function findPublicLink(string $token): InvoicePaymentLink
    {
        return InvoicePaymentLink::withoutGlobalScopes()
            ->with(['invoice.customer', 'invoice.rental', 'payment'])
            ->where('token', $token)
            ->firstOrFail();
    }

    private function uniqueToken(): string
    {
        do {
            $token = Str::random(48);
        } while (InvoicePaymentLink::withoutGlobalScopes()->where('token', $token)->exists());

        return $token;
    }
}

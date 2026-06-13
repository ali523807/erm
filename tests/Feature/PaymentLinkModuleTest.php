<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerPortalUser;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\InvoicePaymentLink;
use App\Models\PaymentGatewaySetting;
use App\Models\Product;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('generates and cancels invoice payment links from the tenant app', function () {
    [$user, , , $invoice] = paymentLinkTenant();

    $this->actingAs($user)
        ->post(route('invoices.payment-links.store', $invoice), [
            'amount' => 250,
            'provider' => 'manual',
            'expires_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
            'notes' => 'Customer requested a partial payment link.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $link = InvoicePaymentLink::firstOrFail();

    expect($link->invoice_id)->toBe($invoice->id)
        ->and($link->status)->toBe('active')
        ->and($link->amount)->toBe('250.00')
        ->and($link->token)->not->toBeEmpty();

    $this->actingAs($user)
        ->get(route('invoices.show', $invoice))
        ->assertOk()
        ->assertSee('Online Payment Links')
        ->assertSee($link->token);

    $this->actingAs($user)
        ->patch(route('payment-links.cancel', $link))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($link->refresh()->status)->toBe('cancelled');
});

it('lets a public payment link record an online invoice payment', function () {
    [, , , $invoice] = paymentLinkTenant('public-payment@example.com', 'Public Payment Rentals');

    $link = InvoicePaymentLink::create([
        'company_id' => $invoice->company_id,
        'invoice_id' => $invoice->id,
        'token' => 'public-test-token',
        'amount' => 300,
        'currency' => 'USD',
        'status' => 'active',
        'provider' => 'manual',
        'expires_at' => now()->addDay(),
    ]);

    $this->get(route('payment-links.show', $link->token))
        ->assertOk()
        ->assertSee($invoice->invoice_number)
        ->assertSee('Record Secure Payment');

    $this->post(route('payment-links.pay', $link->token), [
        'payer_name' => 'Sam Carter',
        'payer_email' => 'sam@example.test',
        'reference' => 'ONLINE-001',
    ])
        ->assertRedirect(route('payment-links.show', $link->token))
        ->assertSessionHas('success');

    $payment = InvoicePayment::firstOrFail();

    expect($payment->method)->toBe('online')
        ->and($payment->amount)->toBe('300.00')
        ->and($payment->reference)->toBe('ONLINE-001')
        ->and($link->refresh()->status)->toBe('paid')
        ->and($link->invoice_payment_id)->toBe($payment->id)
        ->and($invoice->refresh()->balance_due)->toBe('300.00')
        ->and($invoice->status)->toBe('partial');

    $this->get(route('payment-links.receipt', $link->token))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('lets customer portal users open a payment link for their own invoice', function () {
    [, $customer, $portalUser, $invoice] = paymentLinkTenant('portal-payment@example.com', 'Portal Payment Rentals');

    $this->actingAs($portalUser, 'customer')
        ->get(route('customer-portal.invoices'))
        ->assertOk()
        ->assertSee($invoice->invoice_number)
        ->assertSee('Pay');

    $this->actingAs($portalUser, 'customer')
        ->post(route('customer-portal.invoices.pay', $invoice))
        ->assertRedirect();

    $link = InvoicePaymentLink::firstOrFail();

    expect($link->invoice_id)->toBe($invoice->id)
        ->and($link->amount)->toBe('600.00')
        ->and($link->metadata['generated_from'])->toBe('customer_portal')
        ->and($customer->id)->toBe($invoice->customer_id);
});

it('manages payment gateway settings and uses the active gateway for new links', function () {
    [$user, , , $invoice] = paymentLinkTenant('gateway-settings@example.com', 'Gateway Settings Rentals');

    $this->actingAs($user)
        ->get(route('settings.payment-gateways'))
        ->assertOk()
        ->assertSee('Payment Gateway Settings')
        ->assertSee('Stripe');

    $this->actingAs($user)
        ->put(route('settings.payment-gateways.update', 'stripe'), [
            'mode' => 'test',
            'is_active' => 1,
            'public_key' => 'pk_test_123',
            'secret_key' => 'sk_test_123',
            'webhook_secret' => 'whsec_123',
            'account_reference' => 'acct_demo',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $stripe = PaymentGatewaySetting::withoutGlobalScopes()->where('provider', 'stripe')->firstOrFail();

    expect($stripe->is_active)->toBeTrue()
        ->and($stripe->public_key)->toBe('pk_test_123')
        ->and($stripe->secret_key)->toBe('sk_test_123');

    $this->actingAs($user)
        ->post(route('invoices.payment-links.store', $invoice), [
            'amount' => 100,
            'expires_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $link = InvoicePaymentLink::firstOrFail();

    expect($link->provider)->toBe('stripe')
        ->and($link->metadata['gateway_mode'])->toBe('test')
        ->and($link->metadata['gateway_message'])->toContain('Stripe credentials are saved');

    $this->get(route('payment-links.show', $link->token))
        ->assertOk()
        ->assertSee('Stripe credentials are saved', false);
});

/**
 * @return array{0: User, 1: Customer, 2: CustomerPortalUser, 3: Invoice}
 */
function paymentLinkTenant(string $email = 'payment-link@example.com', string $companyName = 'Payment Link Rentals'): array
{
    $company = Company::create([
        'name' => $companyName,
        'slug' => str($companyName)->slug().'-'.str()->random(6),
        'email' => $email,
        'country' => 'US',
        'timezone' => 'UTC',
        'currency' => 'USD',
    ]);

    $user = User::factory()->create([
        'email' => $email,
        'current_company_id' => $company->id,
    ]);

    $company->users()->attach($user, [
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    $customer = Customer::create([
        'company_id' => $company->id,
        'company_name' => 'Acme Build Co',
        'contact_person' => 'Sam Carter',
        'email' => 'sam@acme.test',
    ]);

    $portalUser = CustomerPortalUser::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'name' => 'Sam Carter',
        'email' => 'sam@acme.test',
        'password' => Hash::make('Password123!'),
        'is_active' => true,
    ]);

    $category = Category::create([
        'company_id' => $company->id,
        'name' => 'Generators',
    ]);

    $product = Product::create([
        'company_id' => $company->id,
        'name' => 'Silent Generator',
        'description' => 'Portable generator for payment link tests.',
        'category_id' => $category->id,
        'equipment_code' => 'GEN-PAY-001',
        'status' => 'available',
        'ownership_type' => 'owned',
        'unit_of_measure' => 'unit',
    ]);

    $rental = Rental::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'rental_start_date' => '2026-06-01',
        'rental_end_date' => '2026-06-03',
        'status' => 'returned',
    ]);

    $rental->rentalItems()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-03',
        'duration_type' => 'days',
        'no_of_duration' => 3,
        'rate_type' => 'daily',
        'rate' => 200,
        'deposit_amount' => 100,
        'total_days' => 3,
        'total_price' => 600,
        'status' => 'returned',
    ]);

    $invoice = Invoice::create([
        'company_id' => $company->id,
        'rental_id' => $rental->id,
        'customer_id' => $customer->id,
        'invoice_number' => 'INV-PAYLINK-001',
        'currency' => 'USD',
        'base_currency' => 'USD',
        'exchange_rate' => 1,
        'invoice_date' => '2026-06-04',
        'due_date' => '2026-06-20',
        'status' => 'issued',
        'subtotal' => 0,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'damage_amount' => 0,
        'late_fee_amount' => 0,
        'total_amount' => 0,
        'paid_amount' => 0,
        'balance_due' => 0,
    ]);
    $invoice->recalculateTotals();

    return [$user, $customer, $portalUser, $invoice];
}

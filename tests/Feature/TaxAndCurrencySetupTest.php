<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Product;
use App\Models\Rental;
use App\Models\TaxProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('manages tenant tax profiles', function () {
    [$user, $company] = taxCurrencyTenant();

    $this->actingAs($user)
        ->get(route('settings.tax-profiles'))
        ->assertOk()
        ->assertSee('Tax Profiles');

    $this->actingAs($user)
        ->post(route('settings.tax-profiles.store'), [
            'name' => 'VAT 5%',
            'code' => 'VAT',
            'country' => 'AE',
            'rate' => 5,
            'is_default' => 1,
            'is_active' => 1,
            'description' => 'UAE domestic VAT.',
        ])
        ->assertRedirect();

    $profile = TaxProfile::firstOrFail();

    expect($profile->company_id)->toBe($company->id)
        ->and($profile->is_default)->toBeTrue()
        ->and((float) $profile->rate)->toBe(5.0);

    $this->actingAs($user)
        ->put(route('settings.tax-profiles.update', $profile), [
            'name' => 'VAT 7%',
            'code' => 'VAT',
            'country' => 'AE',
            'rate' => 7,
            'is_default' => 1,
            'is_active' => 1,
        ])
        ->assertRedirect();

    expect((float) $profile->refresh()->rate)->toBe(7.0);
});

it('applies default tax profile and currency metadata to invoices', function () {
    [$user, , , $rental] = taxCurrencyTenant();

    $profile = TaxProfile::create([
        'company_id' => $user->current_company_id,
        'name' => 'VAT 5%',
        'code' => 'VAT',
        'country' => 'AE',
        'rate' => 5,
        'is_default' => true,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->post(route('rentals.invoices.store', $rental), [
            'tax_profile_id' => $profile->id,
            'currency' => 'AED',
            'exchange_rate' => 1,
            'discount_amount' => 0,
            'damage_amount' => 0,
            'late_fee_amount' => 0,
        ])
        ->assertRedirect();

    $invoice = Invoice::firstOrFail();

    expect($invoice->tax_profile_id)->toBe($profile->id)
        ->and($invoice->currency)->toBe('AED')
        ->and($invoice->base_currency)->toBe('AED')
        ->and($invoice->tax_amount)->toBe('50.00')
        ->and($invoice->total_amount)->toBe('1050.00')
        ->and($invoice->base_total_amount)->toBe('1050.00');

    $this->actingAs($user)
        ->get(route('invoices.show', $invoice))
        ->assertOk()
        ->assertSee('VAT 5%')
        ->assertSee('AED 1,050.00');
});

it('recalculates tax when the invoice tax profile is changed', function () {
    [$user, , , $rental] = taxCurrencyTenant();

    $profile = TaxProfile::create([
        'company_id' => $user->current_company_id,
        'name' => 'VAT 5%',
        'code' => 'VAT',
        'country' => 'AE',
        'rate' => 5,
        'is_default' => true,
        'is_active' => true,
    ]);

    $invoice = Invoice::create([
        'company_id' => $user->current_company_id,
        'rental_id' => $rental->id,
        'customer_id' => $rental->customer_id,
        'invoice_number' => 'INV-TAX-UPDATE',
        'currency' => 'AED',
        'base_currency' => 'AED',
        'exchange_rate' => 1,
        'invoice_date' => '2026-06-13',
        'due_date' => '2026-06-20',
        'status' => 'issued',
        'subtotal' => 1000,
        'tax_amount' => 0,
        'total_amount' => 1000,
        'balance_due' => 1000,
    ]);

    $this->actingAs($user)
        ->put(route('invoices.update', $invoice), [
            'tax_profile_id' => $profile->id,
            'currency' => 'AED',
            'exchange_rate' => 1,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'damage_amount' => 0,
            'late_fee_amount' => 0,
            'due_date' => '2026-06-20',
        ])
        ->assertRedirect(route('invoices.show', $invoice));

    expect($invoice->refresh()->tax_profile_id)->toBe($profile->id)
        ->and($invoice->tax_amount)->toBe('50.00')
        ->and($invoice->total_amount)->toBe('1050.00')
        ->and($invoice->balance_due)->toBe('1050.00');
});

it('recalculates base totals when invoice exchange rate is changed', function () {
    [$user, $company, , $rental] = taxCurrencyTenant();
    $company->update(['currency' => 'USD']);

    $invoice = Invoice::create([
        'company_id' => $company->id,
        'rental_id' => $rental->id,
        'customer_id' => $rental->customer_id,
        'invoice_number' => 'INV-FX-UPDATE',
        'currency' => 'AED',
        'base_currency' => 'USD',
        'exchange_rate' => 1,
        'invoice_date' => '2026-06-13',
        'due_date' => '2026-06-20',
        'status' => 'issued',
        'subtotal' => 1000,
        'tax_amount' => 0,
        'total_amount' => 1000,
        'balance_due' => 1000,
    ]);

    $this->actingAs($user)
        ->put(route('invoices.update', $invoice), [
            'tax_profile_id' => null,
            'currency' => 'AED',
            'exchange_rate' => 0.27229408,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'damage_amount' => 0,
            'late_fee_amount' => 0,
            'due_date' => '2026-06-20',
        ])
        ->assertRedirect(route('invoices.show', $invoice));

    expect($invoice->refresh()->exchange_rate)->toBe('0.27229408')
        ->and($invoice->base_total_amount)->toBe('272.29')
        ->and($invoice->base_balance_due)->toBe('272.29');

    $this->actingAs($user)
        ->get(route('invoices.show', $invoice))
        ->assertOk()
        ->assertSee('AED 1,000.00')
        ->assertSee('$ 272.29')
        ->assertSee('0.27229408');
});

it('uses current company currency as invoice base currency when exchange rate is updated', function () {
    [$user, $company, , $rental] = taxCurrencyTenant();
    $company->update(['currency' => 'INR']);

    $invoice = Invoice::create([
        'company_id' => $company->id,
        'rental_id' => $rental->id,
        'customer_id' => $rental->customer_id,
        'invoice_number' => 'INV-FX-INR',
        'currency' => 'AED',
        'base_currency' => 'USD',
        'exchange_rate' => 1,
        'invoice_date' => '2026-06-13',
        'due_date' => '2026-06-20',
        'status' => 'issued',
        'subtotal' => 1000,
        'tax_amount' => 0,
        'total_amount' => 1000,
        'balance_due' => 1000,
    ]);

    $this->actingAs($user)
        ->put(route('invoices.update', $invoice), [
            'tax_profile_id' => null,
            'currency' => 'AED',
            'exchange_rate' => 23.25,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'damage_amount' => 0,
            'late_fee_amount' => 0,
            'due_date' => '2026-06-20',
        ])
        ->assertRedirect(route('invoices.show', $invoice));

    expect($invoice->refresh()->base_currency)->toBe('INR')
        ->and($invoice->base_total_amount)->toBe('23250.00')
        ->and($invoice->base_balance_due)->toBe('23250.00');

    $this->actingAs($user)
        ->get(route('invoices.show', $invoice))
        ->assertOk()
        ->assertSee('Rs 23,250.00')
        ->assertSee('1 AED = 23.25000000 INR');
});

it('summarizes payments and credit notes in company base currency', function () {
    [$user, $company, , $rental] = taxCurrencyTenant();
    $company->update(['currency' => 'INR']);

    $invoice = Invoice::create([
        'company_id' => $company->id,
        'rental_id' => $rental->id,
        'customer_id' => $rental->customer_id,
        'invoice_number' => 'INV-FX-SUMMARY',
        'currency' => 'AED',
        'base_currency' => 'INR',
        'exchange_rate' => 23.25,
        'invoice_date' => '2026-06-13',
        'due_date' => '2026-06-20',
        'status' => 'issued',
        'subtotal' => 1000,
        'tax_amount' => 0,
        'total_amount' => 1000,
        'balance_due' => 1000,
    ]);

    InvoicePayment::create([
        'company_id' => $company->id,
        'invoice_id' => $invoice->id,
        'payment_date' => '2026-06-13',
        'amount' => 100,
        'method' => 'cash',
    ]);

    CreditNote::create([
        'company_id' => $company->id,
        'invoice_id' => $invoice->id,
        'customer_id' => $invoice->customer_id,
        'credit_note_number' => 'CRN-FX-001',
        'credit_date' => '2026-06-13',
        'reason' => 'billing_correction',
        'amount' => 50,
        'refund_amount' => 25,
        'refund_method' => 'cash',
        'status' => 'refunded',
    ]);

    $this->actingAs($user)
        ->get(route('payments.index'))
        ->assertOk()
        ->assertSee('Rs 2,325.00')
        ->assertSee('AED 100.00');

    $this->actingAs($user)
        ->get(route('credit-notes.index'))
        ->assertOk()
        ->assertSee('Rs 1,162.50')
        ->assertSee('Rs 581.25')
        ->assertSee('AED 50.00');
});

/**
 * @return array{0: User, 1: Company, 2: Customer, 3: Rental}
 */
function taxCurrencyTenant(): array
{
    $company = Company::create([
        'name' => 'Tax Currency Rentals',
        'slug' => 'tax-currency-rentals-'.str()->random(6),
        'email' => 'tax@example.com',
        'country' => 'AE',
        'timezone' => 'Asia/Dubai',
        'currency' => 'AED',
        'locale' => 'en',
        'date_format' => 'Y-m-d',
        'measurement_system' => 'metric',
        'tax_name' => 'VAT',
        'default_tax_rate' => 5,
    ]);

    $user = User::factory()->create([
        'current_company_id' => $company->id,
    ]);

    $company->users()->attach($user, [
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    $category = Category::create([
        'company_id' => $company->id,
        'name' => 'Generators',
    ]);

    $product = Product::create([
        'company_id' => $company->id,
        'name' => 'Generator',
        'description' => 'Power unit.',
        'category_id' => $category->id,
        'equipment_code' => 'GEN-TAX-001',
        'status' => 'available',
        'ownership_type' => 'owned',
        'unit_of_measure' => 'unit',
    ]);

    $customer = Customer::create([
        'company_id' => $company->id,
        'company_name' => 'Tax Customer',
        'email' => 'tax.customer@example.com',
    ]);

    $rental = Rental::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'rental_start_date' => '2026-06-10',
        'rental_end_date' => '2026-06-11',
        'status' => 'returned',
    ]);

    $rental->rentalItems()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'start_date' => '2026-06-10',
        'end_date' => '2026-06-11',
        'duration_type' => 'days',
        'no_of_duration' => 1,
        'rate_type' => 'daily',
        'rate' => 1000,
        'deposit_amount' => 0,
        'total_days' => 1,
        'total_price' => 1000,
        'status' => 'returned',
    ]);

    return [$user, $company, $customer, $rental];
}

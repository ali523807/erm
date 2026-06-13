<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\DepositTransaction;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Product;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('collects and refunds security deposits from a rental', function () {
    [$user, , $rental] = depositTenant();

    $this->actingAs($user)
        ->post(route('rentals.deposits.collect', $rental), [
            'transaction_date' => '2026-09-01',
            'amount' => 300,
            'payment_method' => 'cash',
            'reference' => 'DEP-CASH-001',
            'notes' => 'Collected at dispatch.',
        ])
        ->assertRedirect(route('rentals.show', $rental));

    expect($rental->fresh()->depositCollectedAmount())->toBe(300.0)
        ->and($rental->fresh()->depositHeldAmount())->toBe(300.0);

    $this->actingAs($user)
        ->post(route('rentals.deposits.refund', $rental), [
            'transaction_date' => '2026-09-05',
            'amount' => 125,
            'payment_method' => 'bank_transfer',
            'reference' => 'DEP-REF-001',
            'notes' => 'Returned after inspection.',
        ])
        ->assertRedirect(route('rentals.show', $rental));

    expect(DepositTransaction::count())->toBe(2)
        ->and($rental->fresh()->depositRefundedAmount())->toBe(125.0)
        ->and($rental->fresh()->depositHeldAmount())->toBe(175.0);

    $this->actingAs($user)
        ->get(route('deposits.index'))
        ->assertOk()
        ->assertSee('Security Deposits')
        ->assertSee('DEP-CASH-001')
        ->assertSee('DEP-REF-001');
});

it('applies held deposit to an invoice balance', function () {
    [$user, , $rental] = depositTenant('deposit-apply@example.com', 'Deposit Apply Co');
    $invoice = depositInvoice($rental);

    $this->actingAs($user)
        ->post(route('rentals.deposits.collect', $rental), [
            'transaction_date' => '2026-09-01',
            'amount' => 400,
            'payment_method' => 'bank_transfer',
            'reference' => 'DEP-BANK-001',
        ])
        ->assertRedirect(route('rentals.show', $rental));

    $this->actingAs($user)
        ->post(route('rentals.deposits.apply', $rental), [
            'transaction_date' => '2026-09-04',
            'amount' => 250,
            'reference' => 'DEP-APPLY-001',
            'notes' => 'Applied to final invoice.',
        ])
        ->assertRedirect(route('rentals.show', $rental));

    $payment = InvoicePayment::where('method', 'deposit')->first();

    expect($payment)->not->toBeNull()
        ->and($payment->amount)->toBe('250.00')
        ->and($payment->reference)->toBe('DEP-APPLY-001')
        ->and($invoice->refresh()->paid_amount)->toBe('250.00')
        ->and($invoice->balance_due)->toBe('350.00')
        ->and($rental->fresh()->depositAppliedAmount())->toBe(250.0)
        ->and($rental->fresh()->depositHeldAmount())->toBe(150.0);
});

it('blocks deposit settlement above available held amount', function () {
    [$user, , $rental] = depositTenant('deposit-limit@example.com', 'Deposit Limit Co');
    depositInvoice($rental);

    $this->actingAs($user)
        ->post(route('rentals.deposits.collect', $rental), [
            'transaction_date' => '2026-09-01',
            'amount' => 100,
            'payment_method' => 'card',
        ])
        ->assertRedirect(route('rentals.show', $rental));

    $this->actingAs($user)
        ->from(route('rentals.show', $rental))
        ->post(route('rentals.deposits.apply', $rental), [
            'transaction_date' => '2026-09-04',
            'amount' => 150,
        ])
        ->assertSessionHasErrors('amount');
});

/**
 * @return array{0: User, 1: Company, 2: Rental}
 */
function depositTenant(string $email = 'deposit-owner@example.com', string $companyName = 'Deposit Rentals'): array
{
    $company = Company::create([
        'name' => $companyName,
        'slug' => str($companyName)->slug().'-'.str()->random(6),
        'email' => $email,
        'country' => 'US',
        'timezone' => 'UTC',
    ]);

    $user = User::factory()->create([
        'email' => $email,
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
        'name' => 'Silent Generator',
        'description' => 'Portable generator.',
        'category_id' => $category->id,
        'equipment_code' => 'GEN-DEP-001',
        'status' => 'available',
        'ownership_type' => 'owned',
        'unit_of_measure' => 'unit',
    ]);

    $customer = Customer::create([
        'company_id' => $company->id,
        'company_name' => 'Acme Build Co',
        'contact_person' => 'Sam Carter',
        'email' => 'sam@acme.test',
    ]);

    $rental = Rental::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'rental_start_date' => '2026-09-01',
        'rental_end_date' => '2026-09-03',
        'status' => 'returned',
    ]);

    $rental->rentalItems()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-03',
        'duration_type' => 'daily',
        'no_of_duration' => 3,
        'rate_type' => 'daily',
        'rate' => 200,
        'deposit_amount' => 400,
        'total_days' => 3,
        'total_price' => 600,
        'status' => 'returned',
    ]);

    return [$user, $company, $rental];
}

function depositInvoice(Rental $rental): Invoice
{
    $invoice = Invoice::create([
        'company_id' => $rental->company_id,
        'rental_id' => $rental->id,
        'customer_id' => $rental->customer_id,
        'invoice_number' => 'INV-DEP-001',
        'currency' => 'USD',
        'base_currency' => 'USD',
        'exchange_rate' => 1,
        'invoice_date' => '2026-09-04',
        'due_date' => '2026-09-18',
        'status' => 'issued',
        'subtotal' => 600,
        'deposit_amount' => 400,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'damage_amount' => 0,
        'late_fee_amount' => 0,
        'total_amount' => 600,
        'base_total_amount' => 600,
        'paid_amount' => 0,
        'balance_due' => 600,
    ]);

    $invoice->recalculateTotals();

    return $invoice;
}

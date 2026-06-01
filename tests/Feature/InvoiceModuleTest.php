<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Product;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates an invoice from a rental', function () {
    [$user, $company, $rental] = invoiceTenant();

    $this->actingAs($user)
        ->post(route('rentals.invoices.store', $rental), [
            'tax_amount' => 50,
            'discount_amount' => 25,
            'damage_amount' => 10,
            'late_fee_amount' => 5,
            'due_date' => '2026-09-20',
            'notes' => 'Customer requested emailed invoice.',
        ])
        ->assertRedirect();

    $invoice = Invoice::first();

    expect($invoice)->not->toBeNull()
        ->and($invoice->company_id)->toBe($company->id)
        ->and($invoice->invoice_number)->toBe('INV-2026-0001')
        ->and($invoice->subtotal)->toBe('600.00')
        ->and($invoice->total_amount)->toBe('640.00')
        ->and($invoice->balance_due)->toBe('640.00')
        ->and($invoice->status)->toBe('issued');

    $this->actingAs($user)
        ->get(route('invoices.show', $invoice))
        ->assertOk()
        ->assertSee($invoice->invoice_number)
        ->assertSee('Silent Generator');
});

it('records payments and marks invoices paid', function () {
    [$user, , $rental] = invoiceTenant('invoice-paid@example.com', 'Invoice Paid Co');

    $this->actingAs($user)
        ->post(route('rentals.invoices.store', $rental))
        ->assertRedirect();

    $invoice = Invoice::first();

    $this->actingAs($user)
        ->post(route('invoices.payments.store', $invoice), [
            'payment_date' => '2026-09-10',
            'amount' => 200,
            'method' => 'cash',
            'reference' => 'RCPT-001',
        ])
        ->assertRedirect(route('invoices.show', $invoice));

    expect($invoice->refresh()->status)->toBe('partial')
        ->and($invoice->paid_amount)->toBe('200.00')
        ->and($invoice->balance_due)->toBe('400.00');

    $this->actingAs($user)
        ->post(route('invoices.payments.store', $invoice), [
            'payment_date' => '2026-09-11',
            'amount' => 400,
            'method' => 'bank_transfer',
            'reference' => 'ACH-002',
        ])
        ->assertRedirect(route('invoices.show', $invoice));

    expect($invoice->refresh()->status)->toBe('paid')
        ->and($invoice->paid_amount)->toBe('600.00')
        ->and($invoice->balance_due)->toBe('0.00')
        ->and($invoice->payments)->toHaveCount(2);

    $this->actingAs($user)
        ->get(route('payments.index'))
        ->assertOk()
        ->assertSee('Payments')
        ->assertSee('RCPT-001')
        ->assertSee('ACH-002')
        ->assertSee($invoice->invoice_number);

    $payment = InvoicePayment::where('reference', 'RCPT-001')->first();

    $this->actingAs($user)
        ->get(route('payments.receipt.print', $payment))
        ->assertOk()
        ->assertSee('Payment Receipt')
        ->assertSee('RCPT-001')
        ->assertSee($payment->receiptNumber());

    $this->actingAs($user)
        ->get(route('payments.receipt.download', $payment))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('refreshes stale invoice subtotals from rental items before display and download', function () {
    [$user, , $rental] = invoiceTenant('invoice-refresh@example.com', 'Invoice Refresh Co');

    $this->actingAs($user)
        ->post(route('rentals.invoices.store', $rental))
        ->assertRedirect();

    $invoice = Invoice::first();
    $invoice->forceFill([
        'subtotal' => 9999,
        'total_amount' => 9999,
        'balance_due' => 9999,
    ])->save();

    $this->actingAs($user)
        ->get(route('invoices.show', $invoice))
        ->assertOk()
        ->assertSee('600.00');

    expect($invoice->refresh()->subtotal)->toBe('600.00')
        ->and($invoice->total_amount)->toBe('600.00')
        ->and($invoice->balance_due)->toBe('600.00');

    $this->actingAs($user)
        ->get(route('invoices.download', $invoice))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

/**
 * @return array{0: User, 1: Company, 2: Rental}
 */
function invoiceTenant(string $email = 'invoice-owner@example.com', string $companyName = 'Invoice Rentals'): array
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
        'equipment_code' => 'GEN-INV-001',
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
        'duration_type' => 'days',
        'no_of_duration' => 3,
        'rate_type' => 'days',
        'rate' => 200,
        'deposit_amount' => 100,
        'total_days' => 3,
        'total_price' => 600,
        'status' => 'returned',
    ]);

    return [$user, $company, $rental];
}

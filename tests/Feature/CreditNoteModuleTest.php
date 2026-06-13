<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('issues credit notes and reduces invoice balance', function () {
    [$user, , $invoice] = creditNoteTenant();

    expect($invoice->balance_due)->toBe('1000.00');

    $this->actingAs($user)
        ->post(route('invoices.credit-notes.store', $invoice), [
            'credit_date' => '2026-06-13',
            'reason' => 'billing_correction',
            'amount' => 250,
            'refund_amount' => 50,
            'refund_method' => 'bank_transfer',
            'refund_reference' => 'REF-250',
            'notes' => 'Corrected billed duration.',
        ])
        ->assertRedirect(route('invoices.show', $invoice));

    $creditNote = CreditNote::firstOrFail();

    expect($creditNote->credit_note_number)->toBe('CRN-2026-0001')
        ->and($creditNote->status)->toBe('refunded')
        ->and($invoice->refresh()->balance_due)->toBe('750.00');

    $this->actingAs($user)
        ->get(route('invoices.show', $invoice))
        ->assertOk()
        ->assertSee('Credit Notes')
        ->assertSee('CRN-2026-0001')
        ->assertSee('750.00');
});

it('shows credit note register documents and customer statement transactions', function () {
    [$user, , $invoice] = creditNoteTenant('credit-docs@example.com', 'Credit Docs Rentals');

    $this->actingAs($user)
        ->post(route('invoices.credit-notes.store', $invoice), [
            'credit_date' => '2026-06-13',
            'reason' => 'discount_adjustment',
            'amount' => 150,
            'refund_amount' => 0,
        ])
        ->assertRedirect();

    $creditNote = CreditNote::firstOrFail();

    $this->actingAs($user)
        ->get(route('credit-notes.index'))
        ->assertOk()
        ->assertSee('Credit Notes')
        ->assertSee($creditNote->credit_note_number)
        ->assertSee('150.00');

    $this->actingAs($user)
        ->get(route('credit-notes.show', $creditNote))
        ->assertOk()
        ->assertSee('Credit Details')
        ->assertSee('Discount Adjustment');

    $this->actingAs($user)
        ->get(route('credit-notes.print', $creditNote))
        ->assertOk()
        ->assertSee('Credit Note');

    $this->actingAs($user)
        ->get(route('credit-notes.download', $creditNote))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs($user)
        ->get(route('customers.statement.show', [
            'customer' => $invoice->customer,
            'as_of' => '2026-06-13',
        ]))
        ->assertOk()
        ->assertSee('Credits')
        ->assertSee($creditNote->credit_note_number)
        ->assertSee('Credit Note');
});

it('edits and voids credit notes while recalculating invoice balance', function () {
    [$user, , $invoice] = creditNoteTenant('credit-edit@example.com', 'Credit Edit Rentals');

    $this->actingAs($user)
        ->post(route('invoices.credit-notes.store', $invoice), [
            'credit_date' => '2026-06-13',
            'reason' => 'billing_correction',
            'amount' => 200,
            'refund_amount' => 0,
            'notes' => 'Initial correction.',
        ])
        ->assertRedirect();

    $creditNote = CreditNote::firstOrFail();

    expect($invoice->refresh()->balance_due)->toBe('800.00');

    $this->actingAs($user)
        ->get(route('credit-notes.edit', $creditNote))
        ->assertOk()
        ->assertSee('Edit '.$creditNote->credit_note_number);

    $this->actingAs($user)
        ->put(route('credit-notes.update', $creditNote), [
            'credit_date' => '2026-06-14',
            'reason' => 'damage_reversal',
            'amount' => 350,
            'refund_amount' => 75,
            'refund_method' => 'cash',
            'refund_reference' => 'CASH-75',
            'notes' => 'Updated after manager review.',
        ])
        ->assertRedirect(route('credit-notes.show', $creditNote));

    expect($creditNote->refresh()->reason)->toBe('damage_reversal')
        ->and($creditNote->status)->toBe('refunded')
        ->and($creditNote->amount)->toBe('350.00')
        ->and($invoice->refresh()->balance_due)->toBe('650.00');

    $this->actingAs($user)
        ->patch(route('credit-notes.void', $creditNote), [
            'void_reason' => 'Duplicate correction entered by accounts.',
        ])
        ->assertRedirect(route('credit-notes.show', $creditNote));

    expect($creditNote->refresh()->status)->toBe('voided')
        ->and($invoice->refresh()->balance_due)->toBe('1000.00');

    $this->actingAs($user)
        ->get(route('customers.statement.show', [
            'customer' => $invoice->customer,
            'as_of' => '2026-06-14',
        ]))
        ->assertOk()
        ->assertDontSee($creditNote->credit_note_number);
});

/**
 * @return array{0: User, 1: Company, 2: Invoice}
 */
function creditNoteTenant(string $email = 'credit-owner@example.com', string $companyName = 'Credit Rentals'): array
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
        'name' => 'Lifts',
    ]);

    $product = Product::create([
        'company_id' => $company->id,
        'name' => 'Scissor Lift',
        'description' => 'Indoor lift.',
        'category_id' => $category->id,
        'equipment_code' => 'LIFT-CRN-001',
        'status' => 'available',
        'ownership_type' => 'owned',
        'unit_of_measure' => 'unit',
    ]);

    $customer = Customer::create([
        'company_id' => $company->id,
        'company_name' => 'Credit Customer Co',
        'contact_person' => 'Casey Account',
        'email' => 'casey@creditcustomer.test',
    ]);

    $rental = Rental::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'rental_start_date' => '2026-06-01',
        'rental_end_date' => '2026-06-05',
        'status' => 'returned',
    ]);

    $rental->rentalItems()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-05',
        'duration_type' => 'days',
        'no_of_duration' => 5,
        'rate_type' => 'daily',
        'rate' => 200,
        'deposit_amount' => 0,
        'total_days' => 5,
        'total_price' => 1000,
        'status' => 'returned',
    ]);

    $invoice = Invoice::create([
        'company_id' => $company->id,
        'rental_id' => $rental->id,
        'customer_id' => $customer->id,
        'invoice_number' => 'INV-CRN-001',
        'invoice_date' => '2026-06-05',
        'due_date' => '2026-06-15',
        'status' => 'issued',
        'subtotal' => 1000,
        'total_amount' => 1000,
        'balance_due' => 1000,
    ]);

    $invoice->recalculateTotals();

    return [$user, $company, $invoice];
}

<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('records and updates operating expenses', function () {
    [$user, $company, $customer, $product, $rental] = expenseTenant();

    $this->actingAs($user)
        ->get(route('expenses.index'))
        ->assertOk()
        ->assertSee('Expenses')
        ->assertSee('Record Expense');

    $this->actingAs($user)
        ->post(route('expenses.store'), [
            'category' => 'fuel',
            'expense_date' => '2026-09-02',
            'vendor_name' => 'Fuel Depot',
            'payment_status' => 'paid',
            'payment_method' => 'card',
            'reference' => 'FUEL-001',
            'currency' => 'USD',
            'amount' => 100,
            'tax_amount' => 8.25,
            'rental_id' => $rental->id,
            'customer_id' => '',
            'product_id' => $product->id,
            'is_billable' => '1',
            'description' => 'Fuel for generator dispatch.',
        ])
        ->assertRedirect(route('expenses.index'));

    $expense = Expense::first();

    expect($expense)->not->toBeNull()
        ->and($expense->company_id)->toBe($company->id)
        ->and($expense->expense_number)->toBe('EXP-2026-0001')
        ->and($expense->customer_id)->toBe($customer->id)
        ->and($expense->total_amount)->toBe('108.25')
        ->and($expense->is_billable)->toBeTrue();

    $this->actingAs($user)
        ->put(route('expenses.update', $expense), [
            'expense_number' => $expense->expense_number,
            'category' => 'transport',
            'expense_date' => '2026-09-03',
            'vendor_name' => 'City Logistics',
            'payment_status' => 'unpaid',
            'payment_method' => 'bank_transfer',
            'reference' => 'TRN-001',
            'currency' => 'USD',
            'amount' => 250,
            'tax_amount' => 0,
            'rental_id' => $rental->id,
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'description' => 'Delivery charge.',
        ])
        ->assertRedirect(route('expenses.index'));

    expect($expense->refresh()->category)->toBe('transport')
        ->and($expense->payment_status)->toBe('unpaid')
        ->and($expense->total_amount)->toBe('250.00')
        ->and($expense->is_billable)->toBeFalse();

    $this->actingAs($user)
        ->get(route('expenses.index'))
        ->assertOk()
        ->assertSee('City Logistics')
        ->assertSee('TRN-001');
});

it('deletes expenses and includes operating costs in reports', function () {
    [$user, , $customer, $product, $rental] = expenseTenant('expense-report@example.com', 'Expense Report Co');

    Expense::create([
        'company_id' => $rental->company_id,
        'rental_id' => $rental->id,
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'created_by' => $user->id,
        'expense_number' => 'EXP-REPORT-001',
        'category' => 'cleaning',
        'expense_date' => '2026-09-05',
        'vendor_name' => 'Clean Crew',
        'payment_status' => 'paid',
        'payment_method' => 'cash',
        'currency' => 'USD',
        'amount' => 75,
        'tax_amount' => 5,
        'total_amount' => 80,
        'is_billable' => true,
    ]);

    $this->actingAs($user)
        ->get(route('reports.index', ['start_date' => '2026-09-01', 'end_date' => '2026-09-30']))
        ->assertOk()
        ->assertSee('Operating Expenses')
        ->assertSee('80.00')
        ->assertSee('Cleaning');

    $expense = Expense::first();

    $this->actingAs($user)
        ->delete(route('expenses.destroy', $expense))
        ->assertRedirect(route('expenses.index'));

    expect(Expense::count())->toBe(0);
});

it('adds billable expenses to the linked rental invoice and marks them recovered when paid', function () {
    [$user, , $customer, $product, $rental] = expenseTenant('expense-recovery@example.com', 'Expense Recovery Co');
    $invoice = expenseInvoice($rental);

    $expense = Expense::create([
        'company_id' => $rental->company_id,
        'rental_id' => $rental->id,
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'created_by' => $user->id,
        'expense_number' => 'EXP-REC-001',
        'category' => 'transport',
        'expense_date' => '2026-09-05',
        'vendor_name' => 'City Logistics',
        'payment_status' => 'paid',
        'payment_method' => 'card',
        'currency' => 'USD',
        'amount' => 250,
        'tax_amount' => 25,
        'total_amount' => 275,
        'is_billable' => true,
        'recovery_status' => 'not_invoiced',
    ]);

    $this->actingAs($user)
        ->post(route('expenses.add-to-invoice', $expense))
        ->assertRedirect(route('invoices.show', $invoice));

    expect($expense->refresh()->invoice_id)->toBe($invoice->id)
        ->and($expense->recovery_status)->toBe('invoiced')
        ->and($invoice->refresh()->billable_expense_amount)->toBe('275.00')
        ->and($invoice->total_amount)->toBe('875.00')
        ->and($invoice->balance_due)->toBe('875.00');

    $this->actingAs($user)
        ->post(route('invoices.payments.store', $invoice), [
            'payment_date' => '2026-09-06',
            'amount' => 875,
            'method' => 'cash',
            'reference' => 'PAID-EXP',
        ])
        ->assertRedirect(route('invoices.show', $invoice));

    expect($invoice->refresh()->status)->toBe('paid')
        ->and($expense->refresh()->recovery_status)->toBe('recovered')
        ->and($expense->recovered_at)->not->toBeNull();
});

/**
 * @return array{0: User, 1: Company, 2: Customer, 3: Product, 4: Rental}
 */
function expenseTenant(string $email = 'expense-owner@example.com', string $companyName = 'Expense Rentals'): array
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

    $category = Category::create([
        'company_id' => $company->id,
        'name' => 'Generators',
    ]);

    $product = Product::create([
        'company_id' => $company->id,
        'name' => 'Silent Generator',
        'description' => 'Portable generator.',
        'category_id' => $category->id,
        'equipment_code' => 'GEN-EXP-001',
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
        'status' => 'active',
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
        'deposit_amount' => 100,
        'total_days' => 3,
        'total_price' => 600,
        'status' => 'on_rent',
    ]);

    return [$user, $company, $customer, $product, $rental];
}

function expenseInvoice(Rental $rental): Invoice
{
    $invoice = Invoice::create([
        'company_id' => $rental->company_id,
        'rental_id' => $rental->id,
        'customer_id' => $rental->customer_id,
        'invoice_number' => 'INV-EXP-001',
        'currency' => 'USD',
        'base_currency' => 'USD',
        'exchange_rate' => 1,
        'invoice_date' => '2026-09-04',
        'due_date' => '2026-09-18',
        'status' => 'issued',
        'subtotal' => 600,
        'deposit_amount' => 100,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'damage_amount' => 0,
        'late_fee_amount' => 0,
        'billable_expense_amount' => 0,
        'total_amount' => 600,
        'base_total_amount' => 600,
        'paid_amount' => 0,
        'balance_due' => 600,
    ]);

    $invoice->recalculateTotals();

    return $invoice;
}

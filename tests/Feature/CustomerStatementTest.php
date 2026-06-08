<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows customer statements with aging and transaction history', function () {
    [$user, , $customer] = customerStatementTenant();

    $this->actingAs($user)
        ->get(route('customers.statement.show', [
            'customer' => $customer,
            'as_of' => '2026-06-09',
        ]))
        ->assertOk()
        ->assertSee('Customer Statement')
        ->assertSee('Aging')
        ->assertSee('1-30 Days')
        ->assertSee('31-60 Days')
        ->assertSee('INV-STMT-001')
        ->assertSee('RCT-000001')
        ->assertSee('700.00')
        ->assertSee('300.00');

    $this->actingAs($user)
        ->get(route('customers.show', $customer))
        ->assertOk()
        ->assertSee(route('customers.statement.show', $customer));
});

it('renders printable and downloadable customer statements', function () {
    [$user, , $customer] = customerStatementTenant('statement-print@example.com', 'Statement Print Rentals');

    $this->actingAs($user)
        ->get(route('customers.statement.print', [
            'customer' => $customer,
            'as_of' => '2026-06-09',
        ]))
        ->assertOk()
        ->assertSee('Customer Statement')
        ->assertSee('Print');

    $this->actingAs($user)
        ->get(route('customers.statement.download', [
            'customer' => $customer,
            'as_of' => '2026-06-09',
        ]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

/**
 * @return array{0: User, 1: Company, 2: Customer}
 */
function customerStatementTenant(string $email = 'statement-owner@example.com', string $companyName = 'Statement Rentals'): array
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

    $customer = Customer::create([
        'company_id' => $company->id,
        'company_name' => 'Northline Projects',
        'contact_person' => 'Mira Client',
        'email' => 'accounts@northline.test',
        'phone' => '+1 555 0144',
    ]);

    $category = Category::create([
        'company_id' => $company->id,
        'name' => 'Access',
    ]);

    $product = Product::create([
        'company_id' => $company->id,
        'name' => 'Boom Lift',
        'description' => 'Access equipment.',
        'category_id' => $category->id,
        'equipment_code' => 'LIFT-STMT-001',
        'status' => 'available',
        'ownership_type' => 'owned',
        'unit_of_measure' => 'unit',
    ]);

    customerStatementInvoice($company, $customer, $product, 'INV-STMT-001', '2026-06-01', '2026-06-08', 1000, 300);
    customerStatementInvoice($company, $customer, $product, 'INV-STMT-002', '2026-04-25', '2026-05-01', 300);

    return [$user, $company, $customer];
}

function customerStatementInvoice(
    Company $company,
    Customer $customer,
    Product $product,
    string $invoiceNumber,
    string $invoiceDate,
    string $dueDate,
    float $amount,
    float $paymentAmount = 0
): Invoice {
    $rental = Rental::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'rental_start_date' => $invoiceDate,
        'rental_end_date' => $invoiceDate,
        'status' => 'returned',
    ]);

    $rental->rentalItems()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'start_date' => $invoiceDate,
        'end_date' => $invoiceDate,
        'duration_type' => 'days',
        'no_of_duration' => 1,
        'rate_type' => 'days',
        'rate' => $amount,
        'deposit_amount' => 0,
        'total_days' => 1,
        'total_price' => $amount,
        'status' => 'returned',
    ]);

    $invoice = Invoice::create([
        'company_id' => $company->id,
        'rental_id' => $rental->id,
        'customer_id' => $customer->id,
        'invoice_number' => $invoiceNumber,
        'invoice_date' => $invoiceDate,
        'due_date' => $dueDate,
        'status' => 'issued',
        'subtotal' => $amount,
        'total_amount' => $amount,
        'balance_due' => $amount,
    ]);

    if ($paymentAmount > 0) {
        $invoice->payments()->create([
            'company_id' => $company->id,
            'payment_date' => '2026-06-05',
            'amount' => $paymentAmount,
            'method' => 'bank_transfer',
            'reference' => 'BANK-STMT-001',
        ]);
    }

    $invoice->recalculateTotals();

    return $invoice;
}

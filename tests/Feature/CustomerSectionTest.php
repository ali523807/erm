<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the redesigned customer directory and profile', function () {
    [$user, $company, $customer] = customerSectionTenant();
    customerSectionActivity($company, $customer);

    $this->actingAs($user)
        ->get(route('customers.index'))
        ->assertOk()
        ->assertSee('Customer Directory')
        ->assertSee('Balance Due')
        ->assertSee($customer->company_name);

    $this->actingAs($user)
        ->get(route('customers.show', $customer))
        ->assertOk()
        ->assertSee('Customer profile')
        ->assertSee('Recent Rentals')
        ->assertSee('Invoices')
        ->assertSee('Portal Access')
        ->assertSee('INV-CUST-001');
});

it('creates and updates customers using page based forms', function () {
    [$user] = customerSectionTenant();

    $this->actingAs($user)
        ->get(route('customers.create'))
        ->assertOk()
        ->assertSee('Create Customer');

    $this->actingAs($user)
        ->post(route('customers.store'), [
            'company_name' => 'New Customer LLC',
            'contact_person' => 'Nora Client',
            'email' => 'nora@example.com',
            'phone' => '+1 555 0101',
            'address' => '100 Rental Road',
        ])
        ->assertRedirect();

    $customer = Customer::where('email', 'nora@example.com')->firstOrFail();

    $this->actingAs($user)
        ->get(route('customers.edit', $customer))
        ->assertOk()
        ->assertSee('Edit Customer');

    $this->actingAs($user)
        ->put(route('customers.update', $customer), [
            'company_name' => 'Updated Customer LLC',
            'contact_person' => 'Nora Updated',
            'email' => 'nora.updated@example.com',
            'phone' => '+1 555 0102',
        ])
        ->assertRedirect(route('customers.show', $customer));

    expect($customer->refresh()->company_name)->toBe('Updated Customer LLC')
        ->and($customer->email)->toBe('nora.updated@example.com');
});

/**
 * @return array{0: User, 1: Company, 2: Customer}
 */
function customerSectionTenant(): array
{
    $company = Company::create([
        'name' => 'Customer Section Rentals',
        'slug' => 'customer-section-rentals-'.str()->random(6),
        'email' => 'customer-section@example.com',
        'country' => 'US',
        'timezone' => 'UTC',
    ]);

    $user = User::factory()->create([
        'current_company_id' => $company->id,
    ]);

    $company->users()->attach($user, [
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    $customer = Customer::create([
        'company_id' => $company->id,
        'company_name' => 'Acme Site Services',
        'contact_person' => 'Avery Client',
        'email' => 'avery.client@example.com',
        'phone' => '+1 555 0199',
        'address' => 'Site 42',
        'trade_license_number' => 'LIC-42',
        'vat_number' => 'VAT-42',
    ]);

    return [$user, $company, $customer];
}

function customerSectionActivity(Company $company, Customer $customer): void
{
    $rental = Rental::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'rental_start_date' => '2026-06-01',
        'rental_end_date' => '2026-06-05',
        'delivery_location' => 'Main site',
        'status' => 'active',
    ]);

    Quote::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'quote_number' => 'QTE-CUST-001',
        'quote_date' => '2026-05-20',
        'rental_start_date' => '2026-06-01',
        'rental_end_date' => '2026-06-05',
        'status' => 'sent',
        'subtotal' => 500,
        'total_amount' => 500,
    ]);

    Invoice::create([
        'company_id' => $company->id,
        'rental_id' => $rental->id,
        'customer_id' => $customer->id,
        'invoice_number' => 'INV-CUST-001',
        'invoice_date' => '2026-06-01',
        'due_date' => '2026-06-15',
        'status' => 'issued',
        'subtotal' => 500,
        'total_amount' => 500,
        'balance_due' => 500,
    ]);
}

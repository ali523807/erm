<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\MaintenanceLog;
use App\Models\Product;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows revenue utilization customer and maintenance reports for the selected range', function () {
    [$user] = createReportsTenant();

    $this->actingAs($user)
        ->get(route('reports.index', [
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
        ]))
        ->assertOk()
        ->assertSee('Reports')
        ->assertSee('Acme Build Co')
        ->assertSee('Silent Generator')
        ->assertSee('2026-09')
        ->assertSee('625.00')
        ->assertSee('250.00')
        ->assertSee('375.00')
        ->assertSee('125.00')
        ->assertSee('Returned');
});

it('validates report date ranges', function () {
    [$user] = createReportsTenant('report-date@example.com', 'Report Date Rentals');

    $this->actingAs($user)
        ->get(route('reports.index', [
            'start_date' => '2026-09-30',
            'end_date' => '2026-09-01',
        ]))
        ->assertSessionHasErrors('end_date');
});

/**
 * @return array{0: User, 1: Company, 2: Rental}
 */
function createReportsTenant(string $email = 'reports-owner@example.com', string $companyName = 'Reports Rentals'): array
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
        'description' => 'Power equipment',
    ]);

    $product = Product::create([
        'company_id' => $company->id,
        'name' => 'Silent Generator',
        'description' => 'Portable generator.',
        'category_id' => $category->id,
        'equipment_code' => 'GEN-RPT-001',
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
        'invoice_number' => 'INV-2026-RPT-0001',
        'invoice_date' => '2026-09-05',
        'due_date' => '2026-09-20',
        'status' => 'partial',
        'subtotal' => 600,
        'damage_amount' => 25,
        'total_amount' => 625,
        'paid_amount' => 250,
        'balance_due' => 375,
    ]);

    InvoicePayment::create([
        'company_id' => $company->id,
        'invoice_id' => $invoice->id,
        'payment_date' => '2026-09-08',
        'amount' => 250,
        'method' => 'bank_transfer',
        'reference' => 'RPT-PAY-001',
    ]);

    MaintenanceLog::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'type' => 'inspection',
        'title' => 'Monthly safety inspection',
        'priority' => 'medium',
        'status' => 'completed',
        'scheduled_at' => '2026-09-10',
        'completed_at' => '2026-09-10',
        'service_date' => '2026-09-10',
        'cost' => 125,
        'downtime_hours' => 2,
        'affects_availability' => true,
    ]);

    return [$user, $company, $rental];
}

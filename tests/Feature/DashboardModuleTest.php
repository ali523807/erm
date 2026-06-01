<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\MaintenanceLog;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Rental;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders actual tenant statistics on the dashboard', function () {
    $this->travelTo('2026-06-02 10:00:00');

    [$user] = createDashboardTenant();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Operations Dashboard')
        ->assertSee('Dashboard Rentals')
        ->assertSee('This Month Invoiced')
        ->assertSee('1,250.00')
        ->assertSee('400.00')
        ->assertSee('850.00')
        ->assertSee('Acme Build Co')
        ->assertSee('Silent Generator')
        ->assertSee('Monthly safety inspection')
        ->assertSee('Business');
});

/**
 * @return array{0: User, 1: Company}
 */
function createDashboardTenant(): array
{
    $company = Company::create([
        'name' => 'Dashboard Rentals',
        'slug' => 'dashboard-rentals-'.str()->random(6),
        'email' => 'dashboard@example.com',
        'country' => 'US',
        'timezone' => 'UTC',
    ]);

    $user = User::factory()->create([
        'email' => 'dashboard-owner@example.com',
        'current_company_id' => $company->id,
    ]);

    $company->users()->attach($user, [
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    $plan = SubscriptionPlan::updateOrCreate(
        ['slug' => 'business'],
        [
            'name' => 'Business',
            'description' => 'Business plan',
            'monthly_price' => 149,
            'yearly_price' => 1490,
            'user_limit' => 15,
            'equipment_limit' => 1000,
            'features' => ['Billing'],
            'is_active' => true,
        ],
    );

    CompanySubscription::create([
        'company_id' => $company->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'active',
        'billing_cycle' => 'monthly',
        'amount' => 149,
        'currency' => 'USD',
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
        'equipment_code' => 'GEN-DASH-001',
        'status' => 'on_rent',
        'ownership_type' => 'owned',
        'unit_of_measure' => 'unit',
    ]);

    Product::create([
        'company_id' => $company->id,
        'name' => 'Compact Light Tower',
        'description' => 'Portable lighting.',
        'category_id' => $category->id,
        'equipment_code' => 'LGT-DASH-001',
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
        'rental_start_date' => '2026-06-01',
        'rental_end_date' => '2026-06-05',
        'status' => 'active',
    ]);

    $rental->rentalItems()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-05',
        'duration_type' => 'days',
        'no_of_duration' => 5,
        'rate_type' => 'daily',
        'rate' => 250,
        'deposit_amount' => 100,
        'total_days' => 5,
        'total_price' => 1250,
        'status' => 'on_rent',
    ]);

    $invoice = Invoice::create([
        'company_id' => $company->id,
        'rental_id' => $rental->id,
        'customer_id' => $customer->id,
        'invoice_number' => 'INV-DASH-0001',
        'invoice_date' => '2026-06-01',
        'due_date' => '2026-06-15',
        'status' => 'partial',
        'subtotal' => 1250,
        'total_amount' => 1250,
        'paid_amount' => 400,
        'balance_due' => 850,
    ]);

    InvoicePayment::create([
        'company_id' => $company->id,
        'invoice_id' => $invoice->id,
        'payment_date' => '2026-06-02',
        'amount' => 400,
        'method' => 'cash',
    ]);

    Quote::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'quote_number' => 'Q-DASH-0001',
        'quote_date' => '2026-06-02',
        'valid_until' => '2026-06-20',
        'rental_start_date' => '2026-06-10',
        'rental_end_date' => '2026-06-12',
        'status' => 'sent',
        'subtotal' => 300,
        'total_amount' => 300,
    ]);

    MaintenanceLog::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'type' => 'inspection',
        'title' => 'Monthly safety inspection',
        'priority' => 'high',
        'status' => 'scheduled',
        'scheduled_at' => '2026-06-04',
        'cost' => 0,
        'affects_availability' => true,
    ]);

    return [$user, $company];
}

<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\MaintenanceLog;
use App\Models\Product;
use App\Models\Rental;
use App\Models\User;
use App\Services\EquipmentAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows availability conflicts for rentals and maintenance', function () {
    [$user, $company, $product, $customer] = createAvailabilityTenant();

    $rental = Rental::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'rental_start_date' => '2026-06-10',
        'rental_end_date' => '2026-06-12',
        'status' => 'active',
    ]);

    $rental->rentalItems()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'start_date' => '2026-06-10',
        'end_date' => '2026-06-12',
        'duration_type' => 'days',
        'no_of_duration' => 3,
        'rate_type' => 'daily',
        'rate' => 100,
        'total_days' => 3,
        'total_price' => 300,
        'status' => 'on_rent',
    ]);

    MaintenanceLog::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'type' => 'inspection',
        'title' => 'Safety inspection',
        'priority' => 'high',
        'status' => 'scheduled',
        'scheduled_at' => '2026-06-15',
        'affects_availability' => true,
    ]);

    $conflicts = app(EquipmentAvailabilityService::class)->conflicts($product, '2026-06-11', '2026-06-15');

    expect($conflicts)->toHaveCount(2)
        ->and($conflicts->pluck('type')->all())->toContain('rental', 'maintenance');

    $this->actingAs($user)
        ->get(route('availability.index', [
            'start_date' => '2026-06-11',
            'end_date' => '2026-06-15',
        ]))
        ->assertOk()
        ->assertSee('Availability Calendar')
        ->assertSee('Booked by Acme Build Co')
        ->assertSee('Safety inspection');
});

it('blocks rental creation when equipment is unavailable', function () {
    [$user, $company, $product, $customer] = createAvailabilityTenant('booking-conflict@example.com', 'Booking Conflict Rentals');

    $existingRental = Rental::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'rental_start_date' => '2026-07-01',
        'rental_end_date' => '2026-07-05',
        'status' => 'active',
    ]);

    $existingRental->rentalItems()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-05',
        'duration_type' => 'days',
        'no_of_duration' => 5,
        'rate_type' => 'daily',
        'rate' => 100,
        'total_days' => 5,
        'total_price' => 500,
    ]);

    $this->actingAs($user)
        ->post(route('rentals.store'), [
            'customer_id' => $customer->id,
            'rental_start_date' => '2026-07-03',
            'rental_end_date' => '2026-07-06',
            'delivery_location' => 'Project Site',
            'status' => 'reserved',
            'items' => [
                [
                    'product_id' => $product->id,
                    'start_date' => '2026-07-03',
                    'end_date' => '2026-07-06',
                    'duration_type' => 'days',
                    'no_of_duration' => 4,
                    'rate' => 100,
                    'deposit_amount' => 0,
                    'status' => 'reserved',
                ],
            ],
        ])
        ->assertSessionHasErrors('items.0.product_id');
});

/**
 * @return array{0: User, 1: Company, 2: Product, 3: Customer}
 */
function createAvailabilityTenant(string $email = 'availability-owner@example.com', string $companyName = 'Availability Rentals'): array
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
        'equipment_code' => 'GEN-AVL-001',
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

    return [$user, $company, $product, $customer];
}

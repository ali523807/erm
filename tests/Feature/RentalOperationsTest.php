<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates rentals from a full page form', function () {
    [$user, $company, $customer, $product] = rentalOpsTenant();

    $this->actingAs($user)
        ->get(route('rentals.create'))
        ->assertOk()
        ->assertSee('Create Rental')
        ->assertSee('Rental Items');

    $this->actingAs($user)
        ->post(route('rentals.store'), rentalOpsPayload($customer, $product))
        ->assertRedirect();

    $rental = Rental::with('rentalItems')->first();

    expect($rental)->not->toBeNull()
        ->and($rental->company_id)->toBe($company->id)
        ->and($rental->status)->toBe('reserved')
        ->and($rental->rentalItems)->toHaveCount(1)
        ->and($rental->rentalItems->first()->company_id)->toBe($company->id)
        ->and((float) $rental->rentalItems->first()->total_price)->toBe(600.0);

    $this->actingAs($user)
        ->get(route('rentals.index'))
        ->assertOk()
        ->assertSee('RTN-'.$rental->id)
        ->assertSee('Acme Build Co');
});

it('moves rentals through the operations status workflow', function () {
    [$user, , $customer, $product] = rentalOpsTenant('rental-status@example.com', 'Rental Status Co');

    $this->actingAs($user)
        ->post(route('rentals.store'), rentalOpsPayload($customer, $product))
        ->assertRedirect();

    $rental = Rental::with('rentalItems')->first();

    $this->actingAs($user)
        ->patch(route('rentals.status.update', $rental), ['status' => 'active'])
        ->assertRedirect(route('rentals.show', $rental));

    expect($rental->refresh()->status)->toBe('active')
        ->and($rental->rentalItems()->first()->status)->toBe('on_rent');

    $this->actingAs($user)
        ->patch(route('rentals.status.update', $rental), ['status' => 'returned'])
        ->assertRedirect(route('rentals.show', $rental));

    expect($rental->refresh()->status)->toBe('returned')
        ->and($rental->rentalItems()->first()->status)->toBe('returned');
});

/**
 * @return array{0: User, 1: Company, 2: Customer, 3: Product}
 */
function rentalOpsTenant(string $email = 'rental-owner@example.com', string $companyName = 'Rental Ops Co'): array
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
        'equipment_code' => 'GEN-RTN-001',
        'status' => 'available',
        'ownership_type' => 'owned',
        'unit_of_measure' => 'unit',
        'default_rate_type' => 'daily',
        'default_rate' => 200,
    ]);

    $customer = Customer::create([
        'company_id' => $company->id,
        'company_name' => 'Acme Build Co',
        'contact_person' => 'Sam Carter',
        'email' => 'sam@acme.test',
    ]);

    return [$user, $company, $customer, $product];
}

/**
 * @return array<string, mixed>
 */
function rentalOpsPayload(Customer $customer, Product $product, array $overrides = []): array
{
    return array_replace_recursive([
        'customer_id' => $customer->id,
        'rental_start_date' => '2026-09-01',
        'rental_end_date' => '2026-09-03',
        'delivery_location' => 'Project Site',
        'delivery_date' => '2026-09-01',
        'pickup_date' => '2026-09-03',
        'status' => 'reserved',
        'notes' => 'Direct booking for site work.',
        'items' => [
            [
                'product_id' => $product->id,
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-03',
                'duration_type' => 'days',
                'no_of_duration' => 3,
                'rate' => 200,
                'deposit_amount' => 100,
                'status' => 'reserved',
            ],
        ],
    ], $overrides);
}

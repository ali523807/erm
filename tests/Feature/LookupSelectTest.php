<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns tenant customer lookup results for searchable selects', function () {
    $user = lookupSelectUser();

    Customer::create([
        'company_id' => $user->current_company_id,
        'company_name' => 'Northstar Construction LLC',
        'contact_person' => 'Avery Stone',
        'email' => 'avery@northstar.test',
    ]);

    $this->actingAs($user)
        ->getJson(route('lookups.customers', ['q' => 'north']))
        ->assertOk()
        ->assertJsonPath('results.0.text', 'Northstar Construction LLC - Avery Stone');
});

it('returns product lookup results with rate metadata', function () {
    $user = lookupSelectUser();
    $category = Category::create([
        'company_id' => $user->current_company_id,
        'name' => 'Generators',
    ]);

    Product::create([
        'company_id' => $user->current_company_id,
        'category_id' => $category->id,
        'name' => 'Silent Generator',
        'description' => 'Lookup test equipment.',
        'equipment_code' => 'GEN-LOOKUP-001',
        'status' => 'available',
        'ownership_type' => 'owned',
        'unit_of_measure' => 'unit',
        'default_rate_type' => 'daily',
        'default_rate' => 250,
        'daily_rate' => 250,
        'weekly_rate' => 1200,
        'default_deposit_amount' => 500,
    ]);

    $this->actingAs($user)
        ->getJson(route('lookups.products', ['q' => 'gen-lookup']))
        ->assertOk()
        ->assertJsonPath('results.0.code', 'GEN-LOOKUP-001')
        ->assertJsonPath('results.0.rateType', 'daily')
        ->assertJsonPath('results.0.rates.daily', 250)
        ->assertJsonPath('results.0.deposit', 500);
});

it('renders large forms without loading every customer and product option', function () {
    $user = lookupSelectUser();
    $category = Category::create([
        'company_id' => $user->current_company_id,
        'name' => 'Bulk Category',
    ]);

    foreach (range(1, 30) as $index) {
        Customer::create([
            'company_id' => $user->current_company_id,
            'company_name' => "Bulk Customer {$index}",
        ]);

        Product::create([
            'company_id' => $user->current_company_id,
            'category_id' => $category->id,
            'name' => "Bulk Equipment {$index}",
            'description' => 'Bulk lookup test equipment.',
            'equipment_code' => 'BULK-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
            'status' => 'available',
            'ownership_type' => 'owned',
            'unit_of_measure' => 'unit',
        ]);
    }

    $this->actingAs($user)
        ->get(route('quotes.create'))
        ->assertOk()
        ->assertDontSee('Bulk Customer 30')
        ->assertDontSee('Bulk Equipment 30')
        ->assertSee('lookups\/customers', false)
        ->assertSee('lookups\/products', false);
});

it('renders rental, expense, and maintenance forms with ajax lookup hooks', function () {
    $user = lookupSelectUser();

    $this->actingAs($user)
        ->get(route('rentals.create'))
        ->assertOk()
        ->assertSee('lookups\/customers', false)
        ->assertSee('lookups\/products', false);

    $this->actingAs($user)
        ->get(route('expenses.index'))
        ->assertOk()
        ->assertSee('js-rental-lookup')
        ->assertSee('js-customer-lookup')
        ->assertSee('js-product-lookup');

    $this->actingAs($user)
        ->get(route('maintenance.index'))
        ->assertOk()
        ->assertSee('js-product-lookup')
        ->assertSee('js-team-lookup');
});

it('returns rental lookup results for expense linking', function () {
    $user = lookupSelectUser();
    $customer = Customer::create([
        'company_id' => $user->current_company_id,
        'company_name' => 'Northstar Construction LLC',
    ]);
    $rental = Rental::create([
        'company_id' => $user->current_company_id,
        'customer_id' => $customer->id,
        'rental_start_date' => now()->toDateString(),
        'rental_end_date' => now()->addDay()->toDateString(),
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->getJson(route('lookups.rentals', ['q' => 'RTN-'.$rental->id]))
        ->assertOk()
        ->assertJsonPath('results.0.id', $rental->id);
});

function lookupSelectUser(): User
{
    $company = Company::create([
        'name' => 'Lookup Select Rentals',
        'slug' => 'lookup-select-rentals-'.fake()->unique()->numberBetween(1000, 9999),
        'email' => fake()->unique()->safeEmail(),
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

    return $user;
}

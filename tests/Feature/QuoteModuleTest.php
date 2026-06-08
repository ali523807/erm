<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a quote with equipment lines and totals', function () {
    [$user, $company, $customer, $product] = createQuoteTenant();

    $this->actingAs($user)
        ->get(route('quotes.create'))
        ->assertOk()
        ->assertSee('Create Quote')
        ->assertDontSee('>Qty<', false);

    $this->actingAs($user)
        ->post(route('quotes.store'), quotePayload($customer, $product))
        ->assertRedirect();

    $quote = Quote::with('items')->first();

    expect($quote)->not->toBeNull()
        ->and($quote->company_id)->toBe($company->id)
        ->and($quote->quote_number)->toBe('QTE-2026-0001')
        ->and((float) $quote->subtotal)->toBe(600.0)
        ->and((float) $quote->total_amount)->toBe(640.0)
        ->and($quote->items)->toHaveCount(1)
        ->and((float) $quote->items->first()->quantity)->toBe(1.0);

    $this->actingAs($user)
        ->get(route('quotes.show', $quote))
        ->assertOk()
        ->assertSee($quote->quote_number)
        ->assertSee('Silent Generator');
});

it('does not allow the same equipment asset twice on one quote', function () {
    [$user, , $customer, $product] = createQuoteTenant('quote-duplicate@example.com', 'Quote Duplicate Rentals');

    $payload = quotePayload($customer, $product);
    $payload['items'][] = [
        'product_id' => $product->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-03',
        'duration_type' => 'days',
        'no_of_duration' => 3,
        'rate' => 200,
        'deposit_amount' => 100,
    ];

    $this->actingAs($user)
        ->post(route('quotes.store'), $payload)
        ->assertSessionHasErrors('items.1.product_id');

    expect(Quote::count())->toBe(0);
});

it('blocks quotes when equipment is unavailable', function () {
    [$user, , $customer, $product] = createQuoteTenant('quote-conflict@example.com', 'Quote Conflict Rentals');

    $existingRental = Rental::create([
        'company_id' => $product->company_id,
        'customer_id' => $customer->id,
        'rental_start_date' => '2026-08-01',
        'rental_end_date' => '2026-08-05',
        'status' => 'active',
    ]);

    $existingRental->rentalItems()->create([
        'company_id' => $product->company_id,
        'product_id' => $product->id,
        'start_date' => '2026-08-01',
        'end_date' => '2026-08-05',
        'duration_type' => 'days',
        'no_of_duration' => 5,
        'rate_type' => 'daily',
        'rate' => 100,
        'total_days' => 5,
        'total_price' => 500,
    ]);

    $this->actingAs($user)
        ->post(route('quotes.store'), quotePayload($customer, $product, [
            'rental_start_date' => '2026-08-03',
            'rental_end_date' => '2026-08-06',
            'items' => [
                [
                    'product_id' => $product->id,
                    'start_date' => '2026-08-03',
                    'end_date' => '2026-08-06',
                    'duration_type' => 'days',
                    'no_of_duration' => 4,
                    'rate' => 100,
                ],
            ],
        ]))
        ->assertSessionHasErrors('items.0.product_id');

    expect(Quote::count())->toBe(0);
});

it('converts an accepted quote to a reserved rental', function () {
    [$user, $company, $customer, $product] = createQuoteTenant('quote-convert@example.com', 'Quote Convert Rentals');

    $this->actingAs($user)
        ->post(route('quotes.store'), quotePayload($customer, $product, ['status' => 'accepted']))
        ->assertRedirect();

    $quote = Quote::first();

    $response = $this->actingAs($user)
        ->post(route('quotes.convert', $quote));

    $quote->refresh();
    $rental = Rental::with('rentalItems')->first();

    $response->assertRedirect(route('rentals.show', $rental));

    expect($quote->status)->toBe('converted')
        ->and($quote->rental_id)->toBe($rental->id)
        ->and($rental->company_id)->toBe($company->id)
        ->and($rental->status)->toBe('reserved')
        ->and($rental->rentalItems)->toHaveCount(1)
        ->and($rental->rentalItems->first()->company_id)->toBe($company->id)
        ->and((float) $rental->rentalItems->first()->total_price)->toBe(600.0);

    $this->actingAs($user)
        ->get(route('rentals.index'))
        ->assertOk()
        ->assertSee('RTN-'.$rental->id);
});

it('does not allow converted status without creating a rental', function () {
    [$user, , $customer, $product] = createQuoteTenant('quote-status@example.com', 'Quote Status Rentals');

    $this->actingAs($user)
        ->post(route('quotes.store'), quotePayload($customer, $product))
        ->assertRedirect();

    $quote = Quote::first();

    $this->actingAs($user)
        ->patch(route('quotes.status.update', $quote), ['status' => 'converted'])
        ->assertSessionHasErrors('status');

    expect($quote->refresh()->status)->toBe('draft')
        ->and($quote->rental_id)->toBeNull();
});

it('can recover a quote marked converted before a rental was created', function () {
    [$user, , $customer, $product] = createQuoteTenant('quote-recover@example.com', 'Quote Recovery Rentals');

    $this->actingAs($user)
        ->post(route('quotes.store'), quotePayload($customer, $product, ['status' => 'accepted']))
        ->assertRedirect();

    $quote = Quote::first();
    $quote->update(['status' => 'converted', 'rental_id' => null]);

    $this->actingAs($user)
        ->get(route('quotes.show', $quote))
        ->assertOk()
        ->assertSee('no rental was created yet')
        ->assertSee('Convert to Rental');

    $response = $this->actingAs($user)
        ->post(route('quotes.convert', $quote));

    $response->assertRedirect(route('rentals.show', Rental::first()));

    expect($quote->refresh()->rental_id)->not->toBeNull()
        ->and(Rental::count())->toBe(1);
});

/**
 * @return array{0: User, 1: Company, 2: Customer, 3: Product}
 */
function createQuoteTenant(string $email = 'quote-owner@example.com', string $companyName = 'Quote Rentals'): array
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
        'equipment_code' => 'GEN-QTE-001',
        'status' => 'available',
        'ownership_type' => 'owned',
        'unit_of_measure' => 'unit',
        'default_rate_type' => 'daily',
        'default_rate' => 200,
        'daily_rate' => 200,
        'weekly_rate' => 950,
        'monthly_rate' => 3200,
        'default_deposit_amount' => 100,
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
function quotePayload(Customer $customer, Product $product, array $overrides = []): array
{
    return array_replace_recursive([
        'customer_id' => $customer->id,
        'quote_date' => '2026-06-01',
        'valid_until' => '2026-06-15',
        'rental_start_date' => '2026-09-01',
        'rental_end_date' => '2026-09-03',
        'delivery_location' => 'Project Site',
        'status' => 'draft',
        'discount_amount' => 10,
        'tax_amount' => 50,
        'terms' => '50% advance payment required.',
        'notes' => 'Customer requested quiet equipment.',
        'items' => [
            [
                'product_id' => $product->id,
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-03',
                'duration_type' => 'daily',
                'no_of_duration' => 3,
                'rate' => 200,
                'deposit_amount' => 100,
                'notes' => 'Include cables.',
            ],
        ],
    ], $overrides);
}

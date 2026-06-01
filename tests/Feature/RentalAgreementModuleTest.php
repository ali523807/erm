<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Rental;
use App\Models\RentalAgreement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates a rental agreement with print and pdf views', function () {
    [$user, $company, $rental] = agreementTenant();

    $this->actingAs($user)
        ->post(route('rentals.agreements.store', $rental))
        ->assertRedirect();

    $agreement = RentalAgreement::first();

    expect($agreement)->not->toBeNull()
        ->and($agreement->company_id)->toBe($company->id)
        ->and($agreement->agreement_number)->toBe('AGR-2026-0001')
        ->and($agreement->status)->toBe('draft');

    $this->actingAs($user)
        ->get(route('agreements.show', $agreement))
        ->assertOk()
        ->assertSee($agreement->agreement_number)
        ->assertSee('Complete Check-Out');

    $this->actingAs($user)
        ->get(route('agreements.print', $agreement))
        ->assertOk()
        ->assertSee('Rental Agreement')
        ->assertSee($agreement->agreement_number);

    $this->actingAs($user)
        ->get(route('agreements.download', $agreement))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('captures checkout and return signoffs and pushes damage to invoice', function () {
    [$user, , $rental] = agreementTenant('agreement-return@example.com', 'Agreement Return Co');

    $this->actingAs($user)
        ->post(route('rentals.agreements.store', $rental))
        ->assertRedirect();

    $agreement = RentalAgreement::first();

    $this->actingAs($user)
        ->post(route('agreements.checkout', $agreement), [
            'checkout_representative' => 'Sam Carter',
            'checkout_id_number' => 'DL-12345',
            'checkout_condition' => 'Generator clean, tested, and running correctly.',
            'checkout_accessories' => 'Cables and grounding rod.',
            'checkout_notes' => 'Customer collected from yard.',
            'customer_accepted_checkout' => '1',
        ])
        ->assertRedirect(route('agreements.show', $agreement));

    expect($agreement->refresh()->status)->toBe('checked_out')
        ->and($agreement->customer_accepted_checkout)->toBeTrue()
        ->and($agreement->rental->refresh()->status)->toBe('active')
        ->and($agreement->rental->rentalItems()->first()->status)->toBe('on_rent');

    $this->actingAs($user)
        ->post(route('agreements.return', $agreement), [
            'return_representative' => 'Sam Carter',
            'return_condition' => 'Returned with cracked panel cover.',
            'return_missing_accessories' => 'No missing accessories.',
            'return_damage_notes' => 'Panel cover replacement required.',
            'damage_amount' => 125,
            'customer_accepted_return' => '1',
        ])
        ->assertRedirect(route('agreements.show', $agreement));

    $invoice = Invoice::first();

    expect($agreement->refresh()->status)->toBe('returned')
        ->and($agreement->customer_accepted_return)->toBeTrue()
        ->and($agreement->damage_amount)->toBe('125.00')
        ->and($agreement->rental->refresh()->status)->toBe('returned')
        ->and($invoice)->not->toBeNull()
        ->and($invoice->damage_amount)->toBe('125.00')
        ->and($invoice->total_amount)->toBe('725.00');
});

/**
 * @return array{0: User, 1: Company, 2: Rental}
 */
function agreementTenant(string $email = 'agreement-owner@example.com', string $companyName = 'Agreement Rentals'): array
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
    ]);

    $product = Product::create([
        'company_id' => $company->id,
        'name' => 'Silent Generator',
        'description' => 'Portable generator.',
        'category_id' => $category->id,
        'equipment_code' => 'GEN-AGR-001',
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
        'status' => 'reserved',
    ]);

    $rental->rentalItems()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-03',
        'duration_type' => 'days',
        'no_of_duration' => 3,
        'rate_type' => 'days',
        'rate' => 200,
        'deposit_amount' => 100,
        'total_days' => 3,
        'total_price' => 600,
        'status' => 'reserved',
    ]);

    return [$user, $company, $rental];
}

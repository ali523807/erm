<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Rental;
use App\Models\RentalAgreement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows delivery pickup schedule and operation queues', function () {
    $this->travelTo('2026-06-09 09:00:00');

    [$user] = createDispatchTenant();

    $this->actingAs($user)
        ->get(route('dispatch-returns.index', ['date' => '2026-06-09']))
        ->assertOk()
        ->assertSee('Delivery &amp; Pickup Schedule', false)
        ->assertSee('Movement Calendar')
        ->assertSee('Schedule Queue')
        ->assertSee('Today\'s Dispatches', false)
        ->assertSee('Upcoming Dispatches')
        ->assertSee('Due Returns')
        ->assertSee('Overdue Returns')
        ->assertSee('Today Dispatch Co')
        ->assertSee('Upcoming Dispatch Co')
        ->assertSee('Due Return Co')
        ->assertSee('Overdue Return Co')
        ->assertSee('Silent Generator')
        ->assertSee('Agreement');
});

it('filters the schedule and updates movement statuses', function () {
    $this->travelTo('2026-06-09 09:00:00');

    [$user] = createDispatchTenant();
    $reservedRental = Rental::where('status', 'reserved')->orderBy('id')->firstOrFail();
    $activeRental = Rental::where('status', 'active')->orderBy('id')->firstOrFail();

    $this->actingAs($user)
        ->get(route('dispatch-returns.index', [
            'date' => '2026-06-09',
            'window' => 14,
            'movement' => 'pickup',
            'status' => 'active',
        ]))
        ->assertOk()
        ->assertSee('Pickup')
        ->assertSee('Due Return Co');

    $this->actingAs($user)
        ->patch(route('dispatch-returns.status.update', $reservedRental), ['action' => 'dispatch'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($reservedRental->fresh()->status)->toBe('active')
        ->and($reservedRental->rentalItems()->first()->status)->toBe('on_rent');

    $this->actingAs($user)
        ->patch(route('dispatch-returns.status.update', $activeRental), ['action' => 'return'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($activeRental->fresh()->status)->toBe('returned')
        ->and($activeRental->rentalItems()->first()->status)->toBe('returned');
});

/**
 * @return array{0: User, 1: Company}
 */
function createDispatchTenant(): array
{
    $company = Company::create([
        'name' => 'Dispatch Rentals',
        'slug' => 'dispatch-rentals-'.str()->random(6),
        'email' => 'dispatch@example.com',
        'country' => 'US',
        'timezone' => 'UTC',
    ]);

    $user = User::factory()->create([
        'email' => 'dispatch-owner@example.com',
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

    $products = collect(['Silent Generator', 'Light Tower', 'Boom Lift', 'Compressor'])
        ->mapWithKeys(fn (string $name, int $index): array => [
            $name => Product::create([
                'company_id' => $company->id,
                'name' => $name,
                'description' => $name,
                'category_id' => $category->id,
                'equipment_code' => 'DSP-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'status' => $index >= 2 ? 'on_rent' : 'available',
                'ownership_type' => 'owned',
                'unit_of_measure' => 'unit',
                'default_rate_type' => 'daily',
                'daily_rate' => 200,
                'default_rate' => 200,
            ]),
        ]);

    createDispatchRental($company, $products['Silent Generator'], 'Today Dispatch Co', 'reserved', '2026-06-09', '2026-06-12', '2026-06-09', '2026-06-12');
    createDispatchRental($company, $products['Light Tower'], 'Upcoming Dispatch Co', 'reserved', '2026-06-11', '2026-06-14', '2026-06-11', '2026-06-14');
    createDispatchRental($company, $products['Boom Lift'], 'Due Return Co', 'active', '2026-06-01', '2026-06-10', '2026-06-01', '2026-06-10', true);
    createDispatchRental($company, $products['Compressor'], 'Overdue Return Co', 'active', '2026-06-01', '2026-06-05', '2026-06-01', '2026-06-05', true);

    return [$user, $company];
}

function createDispatchRental(
    Company $company,
    Product $product,
    string $customerName,
    string $status,
    string $startDate,
    string $endDate,
    string $deliveryDate,
    string $pickupDate,
    bool $withAgreement = false,
): Rental {
    $customer = Customer::create([
        'company_id' => $company->id,
        'company_name' => $customerName,
        'contact_person' => 'Sam Carter',
        'email' => str($customerName)->slug().'-customer@example.com',
    ]);

    $rental = Rental::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'rental_start_date' => $startDate,
        'rental_end_date' => $endDate,
        'delivery_date' => $deliveryDate,
        'pickup_date' => $pickupDate,
        'delivery_location' => 'Project Site',
        'status' => $status,
    ]);

    $rental->rentalItems()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'duration_type' => 'daily',
        'rate_type' => 'daily',
        'no_of_duration' => 3,
        'rate' => 200,
        'deposit_amount' => 100,
        'total_days' => 3,
        'total_price' => 600,
        'status' => $status === 'active' ? 'on_rent' : 'reserved',
    ]);

    if ($withAgreement) {
        RentalAgreement::create([
            'company_id' => $company->id,
            'rental_id' => $rental->id,
            'agreement_number' => 'AGR-DSP-'.$rental->id,
            'status' => $status === 'active' ? 'checked_out' : 'draft',
            'agreement_date' => $startDate,
            'valid_until' => $endDate,
            'signed_by_customer' => 'Sam Carter',
            'terms' => 'Demo agreement terms.',
        ]);
    }

    return $rental;
}

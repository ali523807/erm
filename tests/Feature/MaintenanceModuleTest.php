<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\MaintenanceLog;
use App\Models\Product;
use App\Models\Rental;
use App\Models\RentalAgreement;
use App\Models\ReturnInspection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates and updates maintenance records for equipment', function () {
    [$user, $company, $product] = createMaintenanceTenant();

    $this->actingAs($user)
        ->get(route('maintenance.index'))
        ->assertOk()
        ->assertSee('Maintenance Work Orders')
        ->assertSee('Create Work Order');

    $this->actingAs($user)
        ->post(route('maintenance.store'), [
            'product_id' => $product->id,
            'type' => 'inspection',
            'title' => 'Monthly Safety Inspection',
            'priority' => 'high',
            'status' => 'scheduled',
            'assigned_to' => $user->id,
            'scheduled_at' => '2026-06-15',
            'service_provider' => 'Internal Workshop',
            'description' => 'Inspect cables, load test, and enclosure.',
            'parts_cost' => 25,
            'labor_cost' => 100,
            'downtime_hours' => 2,
            'affects_availability' => '1',
        ])
        ->assertRedirect();

    $log = MaintenanceLog::first();

    expect($log)->not->toBeNull()
        ->and($log->company_id)->toBe($company->id)
        ->and($log->work_order_number)->toBe('WO-2026-0001')
        ->and($log->product_id)->toBe($product->id)
        ->and($log->assigned_to)->toBe($user->id)
        ->and($log->type)->toBe('inspection')
        ->and($log->scheduled_at->format('Y-m-d'))->toBe('2026-06-15')
        ->and($log->cost)->toBe('125.00');

    expect($product->refresh()->status)->toBe('maintenance');

    $this->actingAs($user)
        ->put(route('maintenance.update', $log), [
            'product_id' => $product->id,
            'type' => 'inspection',
            'title' => 'Monthly Safety Inspection',
            'priority' => 'medium',
            'status' => 'completed',
            'assigned_to' => $user->id,
            'scheduled_at' => '2026-06-15',
            'service_date' => '2026-06-15',
            'completed_at' => '2026-06-15',
            'next_service_due' => '2026-07-15',
            'service_provider' => 'Internal Workshop',
            'findings' => 'Passed load test.',
            'recommendations' => 'Renew certificate next month.',
            'parts_cost' => 50,
            'labor_cost' => 100,
            'downtime_hours' => 1.5,
            'completion_notes' => 'Ready for rental.',
            'final_equipment_status' => 'available',
        ])
        ->assertRedirect();

    $log->refresh();

    expect($log->status)->toBe('completed')
        ->and($log->findings)->toBe('Passed load test.')
        ->and($log->next_service_due)->toBe('2026-07-15')
        ->and($log->cost)->toBe('150.00')
        ->and($product->refresh()->status)->toBe('available');
});

it('creates work orders from return inspections', function () {
    [$user, $company, $product] = createMaintenanceTenant('inspection-maintenance@example.com', 'Inspection Maintenance Rentals');
    $inspection = createMaintenanceReturnInspection($company, $product);

    $this->actingAs($user)
        ->post(route('maintenance.store'), [
            'product_id' => $product->id,
            'return_inspection_id' => $inspection->id,
            'type' => 'repair',
            'title' => 'Repair cracked panel',
            'priority' => 'urgent',
            'status' => 'open',
            'scheduled_at' => '2026-06-18',
            'description' => 'Generated from return inspection damage.',
            'parts_cost' => 75,
            'labor_cost' => 125,
            'final_equipment_status' => 'available',
            'affects_availability' => '1',
        ])
        ->assertRedirect();

    $log = MaintenanceLog::firstOrFail();

    expect($log->return_inspection_id)->toBe($inspection->id)
        ->and($log->product_id)->toBe($product->id)
        ->and($log->priority)->toBe('urgent')
        ->and($log->cost)->toBe('200.00')
        ->and($product->refresh()->status)->toBe('maintenance');
});

it('shows maintenance history on the equipment profile', function () {
    [$user, , $product] = createMaintenanceTenant('history-maintenance@example.com', 'History Maintenance Rentals');

    MaintenanceLog::create([
        'company_id' => $product->company_id,
        'product_id' => $product->id,
        'type' => 'repair',
        'title' => 'Replace starter relay',
        'priority' => 'critical',
        'status' => 'in_progress',
        'scheduled_at' => '2026-06-20',
        'description' => 'Starter relay failed during dispatch check.',
        'cost' => 85,
        'affects_availability' => true,
    ]);

    $this->actingAs($user)
        ->get(route('products.show', $product))
        ->assertOk()
        ->assertSee('Maintenance Work Order History')
        ->assertSee('Replace starter relay')
        ->assertSee('Critical');
});

/**
 * @return array{0: User, 1: Company, 2: Product}
 */
function createMaintenanceTenant(string $email = 'maintenance-owner@example.com', string $companyName = 'Maintenance Rentals'): array
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
        'equipment_code' => 'GEN-MAINT-001',
        'status' => 'available',
        'ownership_type' => 'owned',
        'unit_of_measure' => 'unit',
    ]);

    return [$user, $company, $product];
}

function createMaintenanceReturnInspection(Company $company, Product $product): ReturnInspection
{
    $customer = Customer::create([
        'company_id' => $company->id,
        'company_name' => 'Inspection Customer',
        'contact_person' => 'Riley Carter',
        'email' => 'inspection-customer@example.com',
    ]);

    $rental = Rental::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'rental_start_date' => '2026-06-10',
        'rental_end_date' => '2026-06-12',
        'status' => 'returned',
    ]);

    $rentalItem = $rental->rentalItems()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'start_date' => '2026-06-10',
        'end_date' => '2026-06-12',
        'duration_type' => 'daily',
        'rate_type' => 'daily',
        'no_of_duration' => 2,
        'rate' => 100,
        'deposit_amount' => 0,
        'total_days' => 2,
        'total_price' => 200,
        'status' => 'returned',
    ]);

    $agreement = RentalAgreement::create([
        'company_id' => $company->id,
        'rental_id' => $rental->id,
        'agreement_number' => 'AGR-MAINT-001',
        'status' => 'returned',
        'agreement_date' => '2026-06-10',
        'valid_until' => '2026-06-12',
        'signed_by_customer' => 'Riley Carter',
    ]);

    return ReturnInspection::create([
        'company_id' => $company->id,
        'rental_agreement_id' => $agreement->id,
        'rental_id' => $rental->id,
        'rental_item_id' => $rentalItem->id,
        'product_id' => $product->id,
        'condition_status' => 'damaged',
        'condition_notes' => 'Panel cracked.',
        'damage_notes' => 'Needs panel replacement.',
        'damage_amount' => 200,
        'next_equipment_status' => 'maintenance',
        'inspected_by' => 'Operations',
        'inspected_at' => now(),
    ]);
}

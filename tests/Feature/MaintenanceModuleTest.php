<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\MaintenanceLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates and updates maintenance records for equipment', function () {
    [$user, $company, $product] = createMaintenanceTenant();

    $this->actingAs($user)
        ->get(route('maintenance.index'))
        ->assertOk()
        ->assertSee('Maintenance and Inspections')
        ->assertSee('Create Maintenance Record');

    $this->actingAs($user)
        ->post(route('maintenance.store'), [
            'product_id' => $product->id,
            'type' => 'inspection',
            'title' => 'Monthly Safety Inspection',
            'priority' => 'high',
            'status' => 'scheduled',
            'scheduled_at' => '2026-06-15',
            'service_provider' => 'Internal Workshop',
            'description' => 'Inspect cables, load test, and enclosure.',
            'cost' => 125,
            'downtime_hours' => 2,
            'affects_availability' => '1',
        ])
        ->assertRedirect();

    $log = MaintenanceLog::first();

    expect($log)->not->toBeNull()
        ->and($log->company_id)->toBe($company->id)
        ->and($log->product_id)->toBe($product->id)
        ->and($log->type)->toBe('inspection')
        ->and($log->scheduled_at->format('Y-m-d'))->toBe('2026-06-15');

    expect($product->refresh()->status)->toBe('maintenance');

    $this->actingAs($user)
        ->put(route('maintenance.update', $log), [
            'product_id' => $product->id,
            'type' => 'inspection',
            'title' => 'Monthly Safety Inspection',
            'priority' => 'medium',
            'status' => 'completed',
            'scheduled_at' => '2026-06-15',
            'service_date' => '2026-06-15',
            'completed_at' => '2026-06-15',
            'next_service_due' => '2026-07-15',
            'service_provider' => 'Internal Workshop',
            'findings' => 'Passed load test.',
            'recommendations' => 'Renew certificate next month.',
            'cost' => 150,
            'downtime_hours' => 1.5,
        ])
        ->assertRedirect();

    $log->refresh();

    expect($log->status)->toBe('completed')
        ->and($log->findings)->toBe('Passed load test.')
        ->and($log->next_service_due)->toBe('2026-07-15');
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
        ->assertSee('Maintenance and Inspection History')
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

<?php

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\StorageLocation;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('limits tenant modules by subscription plan', function () {
    $starterUser = subscriptionModuleUser('starter-owner@example.com', 'starter');

    expect($starterUser->hasCurrentCompanyPermission('quotes.manage'))->toBeTrue()
        ->and($starterUser->hasCurrentCompanyPermission('payments.manage'))->toBeFalse()
        ->and($starterUser->hasCurrentCompanyPermission('maintenance.manage'))->toBeFalse()
        ->and($starterUser->hasCurrentCompanyPermission('reports.view'))->toBeFalse();

    $this->actingAs($starterUser)
        ->get(route('quotes.index'))
        ->assertOk();

    $this->actingAs($starterUser)
        ->get(route('payments.index'))
        ->assertForbidden();
});

it('allows business plan companies to use operations and finance modules', function () {
    $businessUser = subscriptionModuleUser('business-owner@example.com', 'business');

    expect($businessUser->hasCurrentCompanyPermission('payments.manage'))->toBeTrue()
        ->and($businessUser->hasCurrentCompanyPermission('maintenance.manage'))->toBeTrue()
        ->and($businessUser->hasCurrentCompanyPermission('reports.view'))->toBeTrue();

    $this->actingAs($businessUser)
        ->get(route('payments.index'))
        ->assertOk();
});

it('hides equipment warehouse fields for plans without locations module', function () {
    $starterUser = subscriptionModuleUser('starter-equipment@example.com', 'starter');
    $company = $starterUser->currentCompany;
    $locations = subscriptionModuleLocations($company);

    $this->actingAs($starterUser)
        ->get(route('products.create'))
        ->assertOk()
        ->assertDontSee('Warehouse / Yard')
        ->assertDontSee('Storage Location');

    $category = Category::create([
        'company_id' => $company->id,
        'name' => 'Generators',
    ]);

    $this->actingAs($starterUser)
        ->post(route('products.store'), subscriptionModuleProductPayload($category, [
            'branch_id' => $locations['branch']->id,
            'warehouse_id' => $locations['warehouse']->id,
            'storage_location_id' => $locations['storageLocation']->id,
        ]))
        ->assertRedirect(route('products.index'));

    $product = Product::firstOrFail();

    expect($product->branch_id)->toBeNull()
        ->and($product->warehouse_id)->toBeNull()
        ->and($product->storage_location_id)->toBeNull();
});

it('shows equipment warehouse fields for plans with locations module', function () {
    $businessUser = subscriptionModuleUser('business-equipment@example.com', 'business');

    subscriptionModuleLocations($businessUser->currentCompany);

    $this->actingAs($businessUser)
        ->get(route('products.create'))
        ->assertOk()
        ->assertSee('Warehouse / Yard')
        ->assertSee('Storage Location');
});

function subscriptionModuleUser(string $email, string $planSlug): User
{
    $plan = SubscriptionPlan::where('slug', $planSlug)->firstOrFail();

    $company = Company::create([
        'name' => str($planSlug)->headline().' Module Co',
        'slug' => $planSlug.'-module-co',
        'email' => $email,
        'country' => 'US',
        'timezone' => 'UTC',
    ]);

    $company->subscription()->create([
        'subscription_plan_id' => $plan->id,
        'status' => 'active',
        'billing_cycle' => 'monthly',
        'amount' => $plan->monthly_price,
        'currency' => 'USD',
        'current_period_starts_at' => now()->toDateString(),
        'current_period_ends_at' => now()->addMonth()->toDateString(),
        'next_billing_at' => now()->addMonth()->toDateString(),
    ]);

    $user = User::factory()->create([
        'email' => $email,
        'current_company_id' => $company->id,
    ]);

    $company->users()->attach($user, [
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    return $user;
}

/**
 * @return array{branch: Branch, warehouse: Warehouse, storageLocation: StorageLocation}
 */
function subscriptionModuleLocations(Company $company): array
{
    $branch = Branch::create([
        'company_id' => $company->id,
        'name' => 'Main Branch',
        'code' => 'MAIN',
        'is_active' => true,
    ]);

    $warehouse = Warehouse::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'name' => 'Central Yard',
        'code' => 'YARD',
        'type' => 'yard',
        'is_active' => true,
    ]);

    $storageLocation = StorageLocation::create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'name' => 'Bay 1',
        'code' => 'BAY-1',
        'type' => 'bay',
        'is_active' => true,
    ]);

    return compact('branch', 'warehouse', 'storageLocation');
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function subscriptionModuleProductPayload(Category $category, array $overrides = []): array
{
    return array_merge([
        'category_id' => $category->id,
        'name' => 'Silent Generator',
        'description' => 'Portable generator for construction sites.',
        'equipment_code' => 'GEN-SUB-001',
        'status' => 'available',
        'ownership_type' => 'owned',
        'unit_of_measure' => 'unit',
        'default_rate_type' => 'daily',
        'default_rate' => 200,
        'hourly_rate' => 25,
        'daily_rate' => 200,
        'weekly_rate' => 950,
        'monthly_rate' => 3200,
        'custom_rate' => 0,
        'default_deposit_amount' => 100,
        'acquisition_cost' => 1000,
        'replacement_value' => 1500,
    ], $overrides);
}

<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates tenant-scoped branches warehouses and storage locations', function () {
    [$user, $company] = createTenantUser();

    $this->actingAs($user)
        ->post(route('settings.locations.branches.store'), [
            'name' => 'Dubai Branch',
            'code' => 'DXB',
            'country' => 'AE',
            'is_active' => 1,
        ])
        ->assertRedirect();

    $branch = Branch::where('company_id', $company->id)->first();

    $this->actingAs($user)
        ->post(route('settings.locations.warehouses.store'), [
            'branch_id' => $branch->id,
            'name' => 'Main Yard',
            'code' => 'YARD-1',
            'type' => 'yard',
            'country' => 'AE',
            'is_active' => 1,
        ])
        ->assertRedirect();

    $warehouse = Warehouse::where('company_id', $company->id)->first();

    $this->actingAs($user)
        ->post(route('settings.locations.storage-locations.store'), [
            'warehouse_id' => $warehouse->id,
            'name' => 'Zone A',
            'code' => 'A-01',
            'type' => 'zone',
            'parent_area' => 'North Yard',
            'sort_order' => 1,
            'is_active' => 1,
        ])
        ->assertRedirect();

    expect($branch->company_id)->toBe($company->id)
        ->and($warehouse->company_id)->toBe($company->id)
        ->and($warehouse->branch_id)->toBe($branch->id)
        ->and(StorageLocation::where('company_id', $company->id)->where('code', 'A-01')->exists())->toBeTrue();
});

it('prevents warehouses from being attached to another tenant branch', function () {
    [$user] = createTenantUser();
    [$otherUser, $otherCompany] = createTenantUser('other@example.com', 'Other Rentals');

    $otherBranch = Branch::withoutGlobalScopes()->create([
        'company_id' => $otherCompany->id,
        'name' => 'Other Branch',
        'code' => 'OTHER',
    ]);

    $this->actingAs($user)
        ->post(route('settings.locations.warehouses.store'), [
            'branch_id' => $otherBranch->id,
            'name' => 'Invalid Yard',
            'code' => 'BAD',
            'type' => 'yard',
            'is_active' => 1,
        ])
        ->assertSessionHasErrors('branch_id');

    expect(Warehouse::withoutGlobalScopes()->where('name', 'Invalid Yard')->exists())->toBeFalse()
        ->and($otherUser)->toBeInstanceOf(User::class);
});

it('updates location setup records', function () {
    [$user, $company] = createTenantUser('updates@example.com', 'Update Rentals');

    $branch = Branch::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Old Branch',
        'code' => 'OLD',
    ]);

    $warehouse = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'name' => 'Old Yard',
        'code' => 'OY',
        'type' => 'yard',
    ]);

    $location = StorageLocation::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'name' => 'Old Zone',
        'code' => 'OZ',
        'type' => 'zone',
        'sort_order' => 0,
    ]);

    $this->actingAs($user)
        ->put(route('settings.locations.branches.update', $branch), [
            'name' => 'Updated Branch',
            'code' => 'UPD',
            'country' => 'US',
            'is_active' => 1,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->put(route('settings.locations.warehouses.update', $warehouse), [
            'branch_id' => $branch->id,
            'name' => 'Updated Yard',
            'code' => 'UY',
            'type' => 'warehouse',
            'country' => 'US',
            'is_active' => 1,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->put(route('settings.locations.storage-locations.update', $location), [
            'warehouse_id' => $warehouse->id,
            'name' => 'Updated Bay',
            'code' => 'UB',
            'type' => 'bay',
            'parent_area' => 'South Yard',
            'sort_order' => 5,
            'is_active' => 1,
        ])
        ->assertRedirect();

    expect($branch->refresh()->name)->toBe('Updated Branch')
        ->and($warehouse->refresh()->type)->toBe('warehouse')
        ->and($location->refresh()->name)->toBe('Updated Bay')
        ->and($location->sort_order)->toBe(5);
});

/**
 * @return array{0: User, 1: Company}
 */
function createTenantUser(string $email = 'owner@example.com', string $companyName = 'Test Rentals'): array
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

    return [$user, $company];
}

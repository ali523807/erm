<?php

use App\Models\Branch;
use App\Models\Category;
use App\Models\CategoryAttributeTemplate;
use App\Models\Company;
use App\Models\Product;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates flexible equipment with tenant locations and dynamic attributes', function () {
    [$user, $company] = createEquipmentTenant();

    $category = Category::create([
        'company_id' => $company->id,
        'name' => 'Camera Gear',
        'description' => 'Rentable camera equipment',
    ]);

    $branch = Branch::create([
        'company_id' => $company->id,
        'name' => 'Studio Branch',
        'code' => 'STU',
    ]);

    $warehouse = Warehouse::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'name' => 'Lens Room',
        'code' => 'LR',
        'type' => 'warehouse',
    ]);

    $storageLocation = StorageLocation::create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'name' => 'Shelf A',
        'code' => 'A',
        'type' => 'shelf',
        'sort_order' => 1,
    ]);

    $this->actingAs($user)
        ->post(route('products.store'), [
            'name' => 'Cinema Camera Kit',
            'description' => 'Full-frame cinema camera kit.',
            'category_id' => $category->id,
            'equipment_code' => 'CAM-001',
            'serial_number' => 'SN-CAM-001',
            'brand' => 'Generic',
            'model' => 'Cine Max',
            'status' => 'available',
            'branch_id' => $branch->id,
            'warehouse_id' => $warehouse->id,
            'storage_location_id' => $storageLocation->id,
            'ownership_type' => 'owned',
            'acquisition_date' => '2026-01-15',
            'purchase_date' => '2026-01-15',
            'warranty_expiry' => '2027-01-15',
            'certificate_expires_at' => '2026-12-31',
            'acquisition_cost' => 12000,
            'replacement_value' => 15000,
            'unit_of_measure' => 'kit',
            'default_rate_type' => 'daily',
            'default_rate' => 450,
            'hourly_rate' => 75,
            'daily_rate' => 450,
            'weekly_rate' => 2100,
            'monthly_rate' => 7200,
            'default_deposit_amount' => 1500,
            'condition' => 'Excellent',
            'attributes' => [
                ['key' => 'Lens Mount', 'value' => 'RF'],
                ['key' => 'Resolution', 'value' => '6K'],
            ],
        ])
        ->assertRedirect(route('products.index'));

    $product = Product::where('equipment_code', 'CAM-001')->first();

    expect($product)->not->toBeNull()
        ->and($product->company_id)->toBe($company->id)
        ->and($product->branch_id)->toBe($branch->id)
        ->and($product->warehouse_id)->toBe($warehouse->id)
        ->and($product->storage_location_id)->toBe($storageLocation->id)
        ->and($product->ownership_type)->toBe('owned')
        ->and($product->unit_of_measure)->toBe('kit')
        ->and((float) $product->default_rate)->toBe(450.0)
        ->and((float) $product->daily_rate)->toBe(450.0)
        ->and((float) $product->weekly_rate)->toBe(2100.0)
        ->and((float) $product->default_deposit_amount)->toBe(1500.0)
        ->and($product->attributes()->count())->toBe(2);
});

it('prevents equipment from using another tenants location', function () {
    [$user, $company] = createEquipmentTenant('asset-owner@example.com', 'Asset Owner Rentals');
    [, $otherCompany] = createEquipmentTenant('other-asset@example.com', 'Other Asset Rentals');

    $category = Category::create([
        'company_id' => $company->id,
        'name' => 'Generators',
        'description' => 'Power equipment',
    ]);

    $otherBranch = Branch::withoutGlobalScopes()->create([
        'company_id' => $otherCompany->id,
        'name' => 'Other Branch',
        'code' => 'OTH',
    ]);

    $this->actingAs($user)
        ->post(route('products.store'), [
            'name' => 'Generator',
            'description' => 'Portable generator.',
            'category_id' => $category->id,
            'equipment_code' => 'GEN-001',
            'status' => 'available',
            'branch_id' => $otherBranch->id,
            'ownership_type' => 'owned',
            'unit_of_measure' => 'unit',
        ])
        ->assertSessionHasErrors('branch_id');

    expect(Product::withoutGlobalScopes()->where('equipment_code', 'GEN-001')->exists())->toBeFalse();
});

it('requires required category template attributes when equipment is saved', function () {
    [$user, $company] = createEquipmentTenant('required-template@example.com', 'Required Template Rentals');

    $category = Category::create([
        'company_id' => $company->id,
        'name' => 'Generators',
        'description' => 'Power equipment',
    ]);

    CategoryAttributeTemplate::create([
        'company_id' => $company->id,
        'category_id' => $category->id,
        'name' => 'Fuel Type',
        'key' => 'fuel_type',
        'type' => 'select',
        'options' => ['Diesel', 'Petrol', 'Electric'],
        'is_required' => true,
        'sort_order' => 1,
    ]);

    $this->actingAs($user)
        ->post(route('products.store'), [
            'name' => 'Silent Generator',
            'description' => 'Portable generator.',
            'category_id' => $category->id,
            'equipment_code' => 'GEN-REQ-001',
            'status' => 'available',
            'ownership_type' => 'owned',
            'unit_of_measure' => 'unit',
        ])
        ->assertSessionHasErrors('attributes');

    expect(Product::withoutGlobalScopes()->where('equipment_code', 'GEN-REQ-001')->exists())->toBeFalse();
});

it('shows the full equipment create page', function () {
    [$user, $company] = createEquipmentTenant('create-equipment@example.com', 'Create Equipment Rentals');

    Category::create([
        'company_id' => $company->id,
        'name' => 'General Assets',
        'description' => 'Flexible rentable items',
    ]);

    $this->actingAs($user)
        ->get(route('products.create'))
        ->assertOk()
        ->assertSee('Add Equipment')
        ->assertSee('Basic Identity')
        ->assertSee('Custom Attributes');
});

it('shows the equipment edit page and updates flexible attributes', function () {
    [$user, $company] = createEquipmentTenant('edit-equipment@example.com', 'Edit Equipment Rentals');

    $category = Category::create([
        'company_id' => $company->id,
        'name' => 'Event Furniture',
        'description' => 'Event rental items',
    ]);

    $product = Product::create([
        'company_id' => $company->id,
        'name' => 'Banquet Table',
        'description' => 'Six foot folding table.',
        'category_id' => $category->id,
        'equipment_code' => 'TBL-001',
        'status' => 'available',
        'ownership_type' => 'owned',
        'unit_of_measure' => 'unit',
    ]);

    $this->actingAs($user)
        ->get(route('products.edit', $product))
        ->assertOk()
        ->assertSee('Edit Equipment')
        ->assertSee('Custom Attributes');

    $this->actingAs($user)
        ->put(route('products.update', $product), [
            'name' => 'Banquet Table Set',
            'description' => 'Six foot folding table with cover.',
            'category_id' => $category->id,
            'equipment_code' => 'TBL-001',
            'status' => 'maintenance',
            'ownership_type' => 'owned',
            'unit_of_measure' => 'set',
            'default_rate_type' => 'daily',
            'default_rate' => 25,
            'weekly_rate' => 100,
            'attributes' => [
                ['key' => 'Seats', 'value' => '6'],
            ],
        ])
        ->assertRedirect(route('products.edit', $product));

    $product->refresh();

    expect($product->name)->toBe('Banquet Table Set')
        ->and($product->status)->toBe('maintenance')
        ->and($product->unit_of_measure)->toBe('set')
        ->and((float) $product->daily_rate)->toBe(25.0)
        ->and((float) $product->weekly_rate)->toBe(100.0)
        ->and($product->attributes()->first()->key)->toBe('Seats');
});

/**
 * @return array{0: User, 1: Company}
 */
function createEquipmentTenant(string $email = 'equipment-owner@example.com', string $companyName = 'Equipment Rentals'): array
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

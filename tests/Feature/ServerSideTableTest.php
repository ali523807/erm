<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('serves equipment records through paged server-side datatables', function () {
    $user = serverSideTableUser();
    $category = Category::create([
        'company_id' => $user->current_company_id,
        'name' => 'Generators',
    ]);

    foreach (range(1, 35) as $index) {
        Product::create([
            'company_id' => $user->current_company_id,
            'category_id' => $category->id,
            'name' => "Generator {$index}",
            'description' => 'Server-side paging test equipment.',
            'equipment_code' => 'GEN-TABLE-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
            'status' => 'available',
            'ownership_type' => 'owned',
            'unit_of_measure' => 'unit',
        ]);
    }

    $this->actingAs($user)
        ->getJson(route('products.index', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'columns' => serverSideProductColumns(),
            'order' => [
                ['column' => 0, 'dir' => 'asc'],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonPath('recordsTotal', 35)
        ->assertJsonCount(10, 'data');
});

it('searches equipment tables without loading the full list into the page', function () {
    $user = serverSideTableUser();

    $generatorCategory = Category::create([
        'company_id' => $user->current_company_id,
        'name' => 'Generators',
    ]);
    $cameraCategory = Category::create([
        'company_id' => $user->current_company_id,
        'name' => 'Cameras',
    ]);

    Product::create([
        'company_id' => $user->current_company_id,
        'category_id' => $generatorCategory->id,
        'name' => 'Silent Generator',
        'description' => 'Generator for site rentals.',
        'equipment_code' => 'GEN-SEARCH-001',
        'status' => 'available',
        'ownership_type' => 'owned',
        'unit_of_measure' => 'unit',
    ]);
    Product::create([
        'company_id' => $user->current_company_id,
        'category_id' => $cameraCategory->id,
        'name' => 'Cinema Camera',
        'description' => 'Camera for production rentals.',
        'equipment_code' => 'CAM-SEARCH-001',
        'status' => 'available',
        'ownership_type' => 'owned',
        'unit_of_measure' => 'unit',
    ]);

    $this->actingAs($user)
        ->getJson(route('products.index', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => 'Camera'],
            'columns' => serverSideProductColumns(),
        ]), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonPath('data.0.name', 'Cinema Camera');
});

it('serves categories through paged server-side datatables', function () {
    $user = serverSideTableUser();

    foreach (range(1, 22) as $index) {
        Category::create([
            'company_id' => $user->current_company_id,
            'name' => "Category {$index}",
            'description' => 'Server-side category paging test.',
        ]);
    }

    $this->actingAs($user)
        ->getJson(route('categories.index', [
            'draw' => 1,
            'start' => 0,
            'length' => 5,
            'columns' => serverSideCategoryColumns(),
        ]), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonPath('recordsTotal', 22)
        ->assertJsonCount(5, 'data');
});

function serverSideTableUser(): User
{
    $company = Company::create([
        'name' => 'Server Side Table Rentals',
        'slug' => 'server-side-table-rentals-'.fake()->unique()->numberBetween(1000, 9999),
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

/**
 * @return array<int, array<string, mixed>>
 */
function serverSideProductColumns(): array
{
    return [
        ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'searchable' => 'false', 'orderable' => 'false'],
        ['data' => 'equipment_code', 'name' => 'equipment_code', 'searchable' => 'true', 'orderable' => 'true'],
        ['data' => 'name', 'name' => 'name', 'searchable' => 'true', 'orderable' => 'true'],
        ['data' => 'category_name', 'name' => 'category_name', 'searchable' => 'true', 'orderable' => 'false'],
        ['data' => 'asset_status', 'name' => 'asset_status', 'searchable' => 'true', 'orderable' => 'true'],
        ['data' => 'status', 'name' => 'status', 'searchable' => 'false', 'orderable' => 'false'],
        ['data' => 'action', 'name' => 'action', 'searchable' => 'false', 'orderable' => 'false'],
    ];
}

/**
 * @return array<int, array<string, mixed>>
 */
function serverSideCategoryColumns(): array
{
    return [
        ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'searchable' => 'false', 'orderable' => 'false'],
        ['data' => 'name', 'name' => 'name', 'searchable' => 'true', 'orderable' => 'true'],
        ['data' => 'description', 'name' => 'description', 'searchable' => 'true', 'orderable' => 'true'],
        ['data' => 'action', 'name' => 'action', 'searchable' => 'false', 'orderable' => 'false'],
    ];
}

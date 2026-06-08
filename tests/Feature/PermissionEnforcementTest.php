<?php

use App\Models\Company;
use App\Models\User;
use App\Support\CompanyRoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows sales users into sales modules and blocks finance modules', function () {
    [, $salesUser] = permissionUser('sales');

    $this
        ->actingAs($salesUser)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Quotes')
        ->assertSee('Customers')
        ->assertDontSee('>Invoices<', false)
        ->assertDontSee('>Payments<', false);

    $this
        ->actingAs($salesUser)
        ->get(route('quotes.index'))
        ->assertOk();

    $this
        ->actingAs($salesUser)
        ->get(route('invoices.index'))
        ->assertForbidden();
});

it('allows accounts users into finance and reports but blocks rental desk modules', function () {
    [, $accountsUser] = permissionUser('accounts');

    $this
        ->actingAs($accountsUser)
        ->get(route('invoices.index'))
        ->assertOk();

    $this
        ->actingAs($accountsUser)
        ->get(route('payments.index'))
        ->assertOk();

    $this
        ->actingAs($accountsUser)
        ->get(route('reports.index'))
        ->assertOk();

    $this
        ->actingAs($accountsUser)
        ->get(route('quotes.index'))
        ->assertForbidden();
});

it('allows viewer users to view availability and reports only', function () {
    [, $viewer] = permissionUser('viewer');

    $this
        ->actingAs($viewer)
        ->get(route('availability.index'))
        ->assertOk();

    $this
        ->actingAs($viewer)
        ->get(route('reports.index'))
        ->assertOk();

    $this
        ->actingAs($viewer)
        ->get(route('products.index'))
        ->assertForbidden();
});

it('keeps owners on full access even when role records are changed', function () {
    [, $owner] = permissionUser('owner');

    expect($owner->hasCurrentCompanyPermission('roles.manage'))->toBeTrue()
        ->and($owner->hasCurrentCompanyPermission('invoices.manage'))->toBeTrue()
        ->and($owner->hasCurrentCompanyPermission('maintenance.manage'))->toBeTrue();
});

/**
 * @return array{0: Company, 1: User}
 */
function permissionUser(string $role): array
{
    $company = Company::create([
        'name' => 'Permission Test Rentals',
        'slug' => 'permission-test-rentals-'.fake()->unique()->numberBetween(1000, 9999),
        'email' => 'permissions@example.com',
        'country' => 'US',
        'timezone' => 'UTC',
    ]);

    app(CompanyRoleCatalog::class)->ensureDefaults($company);

    $user = User::factory()->create([
        'current_company_id' => $company->id,
    ]);

    $company->users()->attach($user, [
        'role' => $role,
        'joined_at' => now(),
    ]);

    return [$company, $user];
}

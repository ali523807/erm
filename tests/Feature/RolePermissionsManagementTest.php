<?php

use App\Models\Company;
use App\Models\CompanyRole;
use App\Models\User;
use App\Support\CompanyRoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets owners create custom roles with permissions', function () {
    [$company, $owner] = roleCompanyUser('owner');

    $this
        ->actingAs($owner)
        ->post(route('settings.roles.store'), [
            'name' => 'Branch Manager',
            'description' => 'Can manage branch rental work.',
            'permissions' => ['dashboard.view', 'rentals.manage', 'dispatch.manage'],
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $this->assertDatabaseHas('company_roles', [
        'company_id' => $company->id,
        'slug' => 'branch-manager',
        'is_system' => false,
    ]);

    $role = CompanyRole::where('company_id', $company->id)->where('slug', 'branch-manager')->firstOrFail();

    expect($role->permissions)->toBe(['dashboard.view', 'rentals.manage', 'dispatch.manage']);
});

it('lets owners update permissions for a role', function () {
    [$company, $owner] = roleCompanyUser('owner');
    app(CompanyRoleCatalog::class)->ensureDefaults($company);
    $role = CompanyRole::where('company_id', $company->id)->where('slug', 'sales')->firstOrFail();

    $this
        ->actingAs($owner)
        ->put(route('settings.roles.update', $role), [
            'name' => 'Sales',
            'description' => 'Updated sales role.',
            'permissions' => ['dashboard.view', 'quotes.manage', 'customers.manage'],
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $role->refresh();

    expect($role->description)->toBe('Updated sales role.')
        ->and($role->permissions)->toBe(['dashboard.view', 'quotes.manage', 'customers.manage'])
        ->and($owner->hasCurrentCompanyPermission('roles.manage'))->toBeTrue();
});

it('preserves permission changes when default roles are ensured again', function () {
    [$company, $owner] = roleCompanyUser('owner');
    $catalog = app(CompanyRoleCatalog::class);
    $role = CompanyRole::where('company_id', $company->id)->where('slug', 'sales')->firstOrFail();

    $this
        ->actingAs($owner)
        ->put(route('settings.roles.update', $role), [
            'name' => 'Sales',
            'description' => 'Sales without customer access.',
            'permissions' => ['dashboard.view', 'quotes.manage'],
        ])
        ->assertRedirect();

    $catalog->ensureDefaults($company);
    $role->refresh();

    expect($role->description)->toBe('Sales without customer access.')
        ->and($role->permissions)->toBe(['dashboard.view', 'quotes.manage']);
});

it('applies permission changes after a role is updated', function () {
    [$company, $owner] = roleCompanyUser('owner');
    $salesUser = roleAttachUserToCompany($company, 'sales');
    $role = CompanyRole::where('company_id', $company->id)->where('slug', 'sales')->firstOrFail();

    $this
        ->actingAs($owner)
        ->put(route('settings.roles.update', $role), [
            'name' => 'Sales',
            'description' => 'Sales with quotes only.',
            'permissions' => ['dashboard.view', 'quotes.manage'],
        ])
        ->assertRedirect();

    expect($salesUser->hasCurrentCompanyPermission('customers.manage'))->toBeFalse()
        ->and($salesUser->hasCurrentCompanyPermission('quotes.manage'))->toBeTrue();
});

it('prevents deleting roles assigned to users', function () {
    [$company, $owner] = roleCompanyUser('owner');
    $role = CompanyRole::create([
        'company_id' => $company->id,
        'name' => 'Dispatcher',
        'slug' => 'dispatcher',
        'permissions' => ['dashboard.view', 'dispatch.manage'],
        'is_system' => false,
        'sort_order' => 90,
    ]);

    $member = roleAttachUserToCompany($company, 'dispatcher');

    $this
        ->actingAs($owner)
        ->delete(route('settings.roles.destroy', $role))
        ->assertRedirect()
        ->assertSessionHasErrors('role');

    expect($member->companies()->wherePivot('role', 'dispatcher')->exists())->toBeTrue();
});

/**
 * @return array{0: Company, 1: User}
 */
function roleCompanyUser(string $role): array
{
    $company = Company::create([
        'name' => 'Role Test Rentals',
        'slug' => 'role-test-rentals-'.fake()->unique()->numberBetween(1000, 9999),
        'email' => 'roles@example.com',
        'country' => 'US',
        'timezone' => 'UTC',
    ]);

    app(CompanyRoleCatalog::class)->ensureDefaults($company);

    return [$company, roleAttachUserToCompany($company, $role)];
}

function roleAttachUserToCompany(Company $company, string $role): User
{
    $user = User::factory()->create([
        'current_company_id' => $company->id,
    ]);

    $company->users()->attach($user, [
        'role' => $role,
        'joined_at' => now(),
    ]);

    return $user;
}

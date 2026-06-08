<?php

use App\Models\Company;
use App\Models\CompanyRole;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\CompanyRoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('records activity logs through the logger service', function () {
    [$company, $owner] = activityUser('owner');

    $this->actingAs($owner);

    app(ActivityLogger::class)->log('roles', 'updated', 'Updated role permissions.', null, [
        'role' => 'Sales',
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'company_id' => $company->id,
        'user_id' => $owner->id,
        'module' => 'roles',
        'action' => 'updated',
        'description' => 'Updated role permissions.',
    ]);
});

it('shows activity logs to users with roles management permission', function () {
    [, $owner] = activityUser('owner');
    $this->actingAs($owner);

    app(ActivityLogger::class)->log('team', 'created', 'Added team member.', null, [
        'email' => 'team@example.com',
    ]);

    $this
        ->get(route('activity-logs.index'))
        ->assertOk()
        ->assertSee('Activity Logs')
        ->assertSee('Added team member.');
});

it('filters activity logs by module', function () {
    [, $owner] = activityUser('owner');
    $this->actingAs($owner);

    app(ActivityLogger::class)->log('team', 'created', 'Added team member.');
    app(ActivityLogger::class)->log('payments', 'created', 'Recorded payment.');

    $this
        ->get(route('activity-logs.index', ['module' => 'payments']))
        ->assertOk()
        ->assertSee('Recorded payment.')
        ->assertDontSee('Added team member.');
});

it('blocks users without role management permission from audit logs', function () {
    [, $salesUser] = activityUser('sales');

    $this
        ->actingAs($salesUser)
        ->get(route('activity-logs.index'))
        ->assertForbidden();
});

it('logs role permission updates from the roles screen', function () {
    [$company, $owner] = activityUser('owner');
    $role = CompanyRole::where('company_id', $company->id)->where('slug', 'sales')->firstOrFail();

    $this
        ->actingAs($owner)
        ->put(route('settings.roles.update', $role), [
            'name' => 'Sales',
            'description' => 'Sales role updated.',
            'permissions' => ['dashboard.view', 'quotes.manage'],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('activity_logs', [
        'company_id' => $company->id,
        'user_id' => $owner->id,
        'module' => 'roles',
        'action' => 'updated',
    ]);
});

/**
 * @return array{0: Company, 1: User}
 */
function activityUser(string $role): array
{
    $company = Company::create([
        'name' => 'Activity Test Rentals',
        'slug' => 'activity-test-rentals-'.fake()->unique()->numberBetween(1000, 9999),
        'email' => 'activity@example.com',
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

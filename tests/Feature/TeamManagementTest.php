<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('shows the team management page to company owners', function () {
    [, $owner] = companyUser('owner');

    $this
        ->actingAs($owner)
        ->get(route('settings.team'))
        ->assertOk()
        ->assertSee('Access Control')
        ->assertSee('Add Team Member')
        ->assertSee($owner->email);
});

it('lets company owners add a team member with a role', function () {
    [$company, $owner] = companyUser('owner');

    $response = $this
        ->actingAs($owner)
        ->post(route('settings.team.store'), [
            'name' => 'Priya Operations',
            'email' => 'priya.operations@example.com',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'role' => 'operations',
        ]);

    $response
        ->assertRedirect()
        ->assertSessionHas('status');

    $member = User::where('email', 'priya.operations@example.com')->firstOrFail();

    $this->assertDatabaseHas('company_user', [
        'company_id' => $company->id,
        'user_id' => $member->id,
        'role' => 'operations',
    ]);

    expect(Hash::check('Secret123!', $member->password))->toBeTrue();
});

it('lets company admins update and remove team members', function () {
    [$company, $admin] = companyUser('admin');
    $owner = attachUserToCompany($company, 'owner');
    $member = attachUserToCompany($company, 'sales');

    $this
        ->actingAs($admin)
        ->patch(route('settings.team.update', $member), [
            'role' => 'accounts',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $this->assertDatabaseHas('company_user', [
        'company_id' => $company->id,
        'user_id' => $member->id,
        'role' => 'accounts',
    ]);

    $this
        ->actingAs($admin)
        ->delete(route('settings.team.destroy', $member))
        ->assertRedirect()
        ->assertSessionHas('status');

    $this->assertDatabaseMissing('company_user', [
        'company_id' => $company->id,
        'user_id' => $member->id,
    ]);

    expect($owner->companies()->whereKey($company->id)->exists())->toBeTrue();
});

it('lets admins edit team member profile details on a page', function () {
    [$company, $admin] = companyUser('admin');
    attachUserToCompany($company, 'owner');
    $member = attachUserToCompany($company, 'sales');

    $this
        ->actingAs($admin)
        ->get(route('settings.team.edit', $member))
        ->assertOk()
        ->assertSee('Edit '.$member->name)
        ->assertSee($member->email);

    $this
        ->actingAs($admin)
        ->put(route('settings.team.details.update', $member), [
            'name' => 'Updated Sales User',
            'email' => 'updated.sales@example.com',
            'password' => 'Updated123!',
            'password_confirmation' => 'Updated123!',
            'role' => 'operations',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $member->refresh();

    expect($member->name)->toBe('Updated Sales User')
        ->and($member->email)->toBe('updated.sales@example.com')
        ->and(Hash::check('Updated123!', $member->password))->toBeTrue();

    $this->assertDatabaseHas('company_user', [
        'company_id' => $company->id,
        'user_id' => $member->id,
        'role' => 'operations',
    ]);
});

it('blocks non admin roles from managing team access', function () {
    [$company, $salesUser] = companyUser('sales');

    $this
        ->actingAs($salesUser)
        ->get(route('settings.team'))
        ->assertForbidden();

    $this->assertDatabaseCount('company_user', 1);
    expect($salesUser->companies()->whereKey($company->id)->exists())->toBeTrue();
});

it('prevents removing the last owner from a company', function () {
    [$company, $owner] = companyUser('owner');

    $this
        ->actingAs($owner)
        ->patch(route('settings.team.update', $owner), [
            'role' => 'admin',
        ])
        ->assertStatus(422);

    $this->assertDatabaseHas('company_user', [
        'company_id' => $company->id,
        'user_id' => $owner->id,
        'role' => 'owner',
    ]);
});

/**
 * @return array{0: Company, 1: User}
 */
function companyUser(string $role): array
{
    $company = Company::create([
        'name' => 'Team Test Rentals',
        'slug' => 'team-test-rentals-'.fake()->unique()->numberBetween(1000, 9999),
        'email' => 'team@example.com',
        'country' => 'US',
        'timezone' => 'UTC',
    ]);

    return [$company, attachUserToCompany($company, $role)];
}

function attachUserToCompany(Company $company, string $role): User
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

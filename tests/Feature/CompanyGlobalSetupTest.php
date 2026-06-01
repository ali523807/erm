<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets tenant users update global company setup', function () {
    $company = Company::create([
        'name' => 'Global Test Rentals',
        'slug' => 'global-test-rentals',
        'email' => 'old@example.com',
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

    $response = $this
        ->actingAs($user)
        ->put(route('settings.company.update'), [
            'name' => 'Global Ready Rentals',
            'email' => 'billing@example.com',
            'phone' => '+1-555-0199',
            'country' => 'AE',
            'currency' => 'AED',
            'locale' => 'ar',
            'timezone' => 'Asia/Dubai',
            'date_format' => 'd/m/Y',
            'measurement_system' => 'metric',
            'tax_name' => 'VAT',
            'tax_number' => 'TRN-123',
            'default_tax_rate' => 5,
            'tax_inclusive' => 1,
            'address_line_1' => 'Warehouse 12',
            'address_line_2' => 'Industrial Area',
            'city' => 'Dubai',
            'state_region' => 'Dubai',
            'postal_code' => '00000',
        ]);

    $response->assertRedirect();

    $company->refresh();

    expect($company->name)->toBe('Global Ready Rentals')
        ->and($company->country)->toBe('AE')
        ->and($company->currency)->toBe('AED')
        ->and($company->locale)->toBe('ar')
        ->and($company->timezone)->toBe('Asia/Dubai')
        ->and($company->tax_name)->toBe('VAT')
        ->and((float) $company->default_tax_rate)->toBe(5.0)
        ->and($company->tax_inclusive)->toBeTrue();
});

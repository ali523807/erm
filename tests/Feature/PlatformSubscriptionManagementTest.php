<?php

use App\Models\Company;
use App\Models\PlatformAdmin;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets platform admins update company subscriptions', function () {
    $platformAdmin = PlatformAdmin::create([
        'name' => 'Platform Owner',
        'email' => 'owner-test@example.com',
        'password' => 'Password123!',
        'is_active' => true,
    ]);

    $starterPlan = SubscriptionPlan::updateOrCreate(
        ['slug' => 'starter'],
        [
            'name' => 'Starter',
            'description' => 'Starter plan',
            'monthly_price' => 49,
            'yearly_price' => 490,
            'is_active' => true,
        ],
    );

    $businessPlan = SubscriptionPlan::updateOrCreate(
        ['slug' => 'business'],
        [
            'name' => 'Business',
            'description' => 'Business plan',
            'monthly_price' => 149,
            'yearly_price' => 1490,
            'is_active' => true,
        ],
    );

    $company = Company::create([
        'name' => 'Billing Test Rentals',
        'slug' => 'billing-test-rentals',
        'email' => 'billing@example.com',
    ]);

    $company->subscription()->create([
        'subscription_plan_id' => $starterPlan->id,
        'status' => 'trialing',
        'billing_cycle' => 'monthly',
        'amount' => 49,
        'currency' => 'USD',
    ]);

    $response = $this
        ->actingAs($platformAdmin, 'platform')
        ->patch(route('platform.companies.subscription.update', $company), [
            'subscription_plan_id' => $businessPlan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'amount' => 149,
            'currency' => 'usd',
            'trial_ends_at' => null,
            'current_period_starts_at' => '2026-06-01',
            'current_period_ends_at' => '2026-07-01',
            'next_billing_at' => '2026-07-01',
            'notes' => 'Moved to business plan.',
        ]);

    $response->assertRedirect();

    $company->refresh();

    expect($company->subscription->subscription_plan_id)->toBe($businessPlan->id)
        ->and($company->subscription->status)->toBe('active')
        ->and((float) $company->subscription->amount)->toBe(149.0)
        ->and($company->subscription->currency)->toBe('USD')
        ->and($company->subscription->notes)->toBe('Moved to business plan.');
});

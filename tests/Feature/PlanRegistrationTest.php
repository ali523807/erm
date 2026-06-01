<?php

use App\Models\Company;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a company subscription using the selected registration plan', function () {
    $businessPlan = SubscriptionPlan::updateOrCreate(
        ['slug' => 'business'],
        [
            'name' => 'Business',
            'description' => 'Business plan',
            'monthly_price' => 149,
            'yearly_price' => 1490,
            'user_limit' => 15,
            'equipment_limit' => 1000,
            'is_active' => true,
            'features' => ['Equipment', 'Billing'],
        ],
    );

    $response = $this->post('/register', [
        'name' => 'Rental Owner',
        'company_name' => 'Plan Flow Rentals',
        'email' => 'plan-flow@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'plan' => 'business',
    ]);

    $response->assertRedirect('/home');

    $user = User::where('email', 'plan-flow@example.com')->first();
    $company = Company::where('name', 'Plan Flow Rentals')->first();

    expect($user)->not->toBeNull()
        ->and($company)->not->toBeNull()
        ->and($company->subscription->subscription_plan_id)->toBe($businessPlan->id)
        ->and((float) $company->subscription->amount)->toBe(149.0)
        ->and($company->subscription->status)->toBe('trialing');
});

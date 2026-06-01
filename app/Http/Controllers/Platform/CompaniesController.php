<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\SubscriptionPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompaniesController extends Controller
{
    public function index(): View
    {
        $companies = Company::query()
            ->with(['subscription.plan', 'users'])
            ->withCount(['users', 'products', 'customers', 'rentals'])
            ->latest()
            ->paginate(15);

        return view('platform.companies.index', compact('companies'));
    }

    public function show(Company $company): View
    {
        $company->load(['subscription.plan', 'users']);
        $company->loadCount(['users', 'products', 'customers', 'rentals']);

        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('monthly_price')
            ->get();

        return view('platform.companies.show', compact('company', 'plans'));
    }

    public function updateSubscription(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'subscription_plan_id' => ['required', Rule::exists('subscription_plans', 'id')->where('is_active', true)],
            'status' => ['required', Rule::in(['trialing', 'active', 'past_due', 'cancelled'])],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'trial_ends_at' => ['nullable', 'date'],
            'current_period_starts_at' => ['nullable', 'date'],
            'current_period_ends_at' => ['nullable', 'date', 'after_or_equal:current_period_starts_at'],
            'next_billing_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $existingSubscription = CompanySubscription::where('company_id', $company->id)
            ->latest('id')
            ->first();

        CompanySubscription::updateOrCreate(
            ['company_id' => $company->id],
            [
                ...$validated,
                'currency' => strtoupper($validated['currency']),
                'cancelled_at' => $validated['status'] === 'cancelled'
                    ? ($existingSubscription?->cancelled_at ?? now()->toDateString())
                    : null,
            ],
        );

        return back()->with('status', 'Subscription updated successfully.');
    }
}

<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\SubscriptionPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class PlatformDashboardController extends Controller
{
    public function __invoke(): View
    {
        $companies = Company::query()
            ->with(['subscription.plan', 'users'])
            ->withCount(['users', 'products', 'customers', 'rentals'])
            ->latest()
            ->take(8)
            ->get();

        $subscriptionSummary = CompanySubscription::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $monthlyRecurringRevenue = CompanySubscription::query()
            ->whereIn('status', ['active', 'trialing'])
            ->sum('amount');

        return view('platform.dashboard', [
            'companyCount' => Company::count(),
            'activeCompanyCount' => Company::where('status', 'active')->count(),
            'planCount' => SubscriptionPlan::where('is_active', true)->count(),
            'trialingSubscriptionCount' => (int) ($subscriptionSummary['trialing'] ?? 0),
            'activeSubscriptionCount' => (int) ($subscriptionSummary['active'] ?? 0),
            'monthlyRecurringRevenue' => $monthlyRecurringRevenue,
            'companies' => $companies,
        ]);
    }
}

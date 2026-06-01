<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'For small rental teams starting with core equipment and rental tracking.',
                'monthly_price' => 49,
                'yearly_price' => 490,
                'user_limit' => 3,
                'equipment_limit' => 100,
                'features' => json_encode(['Equipment', 'Customers', 'Rentals']),
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'For growing rental companies that need billing, maintenance, and reporting.',
                'monthly_price' => 149,
                'yearly_price' => 1490,
                'user_limit' => 15,
                'equipment_limit' => 1000,
                'features' => json_encode(['Equipment', 'Customers', 'Rentals', 'Billing', 'Maintenance', 'Reports']),
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'For multi-branch rental operations with advanced controls and integrations.',
                'monthly_price' => 399,
                'yearly_price' => 3990,
                'user_limit' => null,
                'equipment_limit' => null,
                'features' => json_encode(['All modules', 'API', 'Advanced analytics', 'Priority support']),
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('subscription_plans')->updateOrInsert(
                ['slug' => $plan['slug']],
                array_merge($plan, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
            );
        }

        $starterPlanId = DB::table('subscription_plans')->where('slug', 'starter')->value('id');

        DB::table('companies')
            ->orderBy('id')
            ->get()
            ->each(function (object $company) use ($starterPlanId): void {
                $exists = DB::table('company_subscriptions')
                    ->where('company_id', $company->id)
                    ->exists();

                if (! $exists && $starterPlanId) {
                    DB::table('company_subscriptions')->insert([
                        'company_id' => $company->id,
                        'subscription_plan_id' => $starterPlanId,
                        'status' => 'trialing',
                        'billing_cycle' => 'monthly',
                        'amount' => 49,
                        'currency' => 'USD',
                        'trial_ends_at' => now()->addDays(14)->toDateString(),
                        'current_period_starts_at' => now()->toDateString(),
                        'current_period_ends_at' => now()->addMonth()->toDateString(),
                        'next_billing_at' => now()->addDays(14)->toDateString(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('company_subscriptions')->delete();
        DB::table('subscription_plans')->whereIn('slug', ['starter', 'business', 'enterprise'])->delete();
    }
};

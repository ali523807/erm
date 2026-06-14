<?php

namespace App\Support;

use App\Models\Company;
use App\Models\SubscriptionPlan;

class SubscriptionModuleCatalog
{
    /**
     * @return array<string, array{label: string, description: string, permissions: array<int, string>}>
     */
    public function modules(): array
    {
        return [
            'dashboard' => [
                'label' => 'Dashboard',
                'description' => 'Operational overview and daily rental statistics.',
                'permissions' => ['dashboard.view'],
            ],
            'customers' => [
                'label' => 'Customers',
                'description' => 'Customer records, contacts, and statements.',
                'permissions' => ['customers.manage'],
            ],
            'fleet' => [
                'label' => 'Fleet Setup',
                'description' => 'Categories, equipment, rate cards, and asset records.',
                'permissions' => ['categories.manage', 'equipment.manage'],
            ],
            'rental_desk' => [
                'label' => 'Rental Desk',
                'description' => 'Quotes, rentals, agreements, and availability checks.',
                'permissions' => ['quotes.manage', 'rentals.manage', 'availability.view'],
            ],
            'basic_billing' => [
                'label' => 'Basic Billing',
                'description' => 'Invoice generation and customer billing visibility.',
                'permissions' => ['invoices.manage'],
            ],
            'company_setup' => [
                'label' => 'Company Setup',
                'description' => 'Company profile and team member management.',
                'permissions' => ['company.manage', 'team.manage'],
            ],
            'notifications' => [
                'label' => 'Notifications',
                'description' => 'Reminders, alerts, and generated operational follow-ups.',
                'permissions' => ['notifications.manage'],
            ],
            'dispatch' => [
                'label' => 'Dispatch & Returns',
                'description' => 'Dispatch board, returns, movements, and close-out flow.',
                'permissions' => ['dispatch.manage'],
            ],
            'payments' => [
                'label' => 'Payments & Finance',
                'description' => 'Payments, receipts, deposits, expenses, and credit notes.',
                'permissions' => ['payments.manage'],
            ],
            'maintenance' => [
                'label' => 'Maintenance',
                'description' => 'Inspections, repairs, asset health, and work orders.',
                'permissions' => ['maintenance.manage'],
            ],
            'documents' => [
                'label' => 'Documents',
                'description' => 'Document center, delivery logs, and shared files.',
                'permissions' => ['documents.manage'],
            ],
            'reports' => [
                'label' => 'Reports',
                'description' => 'Profitability, utilization, revenue, and operating reports.',
                'permissions' => ['reports.view'],
            ],
            'locations' => [
                'label' => 'Locations',
                'description' => 'Branches, warehouses, and storage locations.',
                'permissions' => ['locations.manage'],
            ],
            'advanced_admin' => [
                'label' => 'Advanced Admin',
                'description' => 'Custom roles, permissions, and activity logs.',
                'permissions' => ['roles.manage'],
            ],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function planModules(): array
    {
        return [
            'starter' => [
                'dashboard',
                'customers',
                'fleet',
                'rental_desk',
                'basic_billing',
                'company_setup',
                'notifications',
            ],
            'business' => [
                'dashboard',
                'customers',
                'fleet',
                'rental_desk',
                'basic_billing',
                'company_setup',
                'notifications',
                'dispatch',
                'payments',
                'maintenance',
                'documents',
                'reports',
                'locations',
            ],
            'enterprise' => array_keys($this->modules()),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function modulesForPlan(?SubscriptionPlan $plan): array
    {
        if (! $plan) {
            return array_keys($this->modules());
        }

        return $this->planModules()[$plan->slug] ?? array_keys($this->modules());
    }

    /**
     * @return array<int, array{key: string, label: string, description: string, included: bool}>
     */
    public function matrixForPlan(?SubscriptionPlan $plan): array
    {
        $includedModules = $this->modulesForPlan($plan);

        return collect($this->modules())
            ->map(fn (array $module, string $key): array => [
                'key' => $key,
                'label' => $module['label'],
                'description' => $module['description'],
                'included' => in_array($key, $includedModules, true),
            ])
            ->values()
            ->all();
    }

    public function allowsPermission(Company $company, string $permission): bool
    {
        $subscription = $company->subscription()->with('plan')->first();

        if (! $subscription) {
            return true;
        }

        if (! in_array($subscription->status, ['trialing', 'active', 'past_due'], true)) {
            return $permission === 'dashboard.view';
        }

        $includedModules = $this->modulesForPlan($subscription->plan);

        return collect($this->modules())
            ->filter(fn (array $module, string $key): bool => in_array($key, $includedModules, true))
            ->flatMap(fn (array $module): array => $module['permissions'])
            ->contains($permission);
    }

    /**
     * @return array<int, string>
     */
    public function featureLabelsForPlan(?SubscriptionPlan $plan): array
    {
        return collect($this->matrixForPlan($plan))
            ->where('included', true)
            ->pluck('label')
            ->values()
            ->all();
    }
}

<?php

namespace App\Support;

use App\Models\Company;
use App\Models\CompanyRole;
use Illuminate\Support\Collection;

class CompanyRoleCatalog
{
    /**
     * @return array<string, array{label: string, permissions: array<string, string>}>
     */
    public function permissionGroups(): array
    {
        return [
            'Dashboard' => [
                'dashboard.view' => 'View dashboard',
                'reports.view' => 'View reports and statistics',
            ],
            'Rental Desk' => [
                'quotes.manage' => 'Create and manage quotes',
                'rentals.manage' => 'Create and manage rentals',
                'dispatch.manage' => 'Manage dispatch and returns',
                'availability.view' => 'View availability calendar',
            ],
            'Fleet' => [
                'categories.manage' => 'Manage categories and templates',
                'equipment.manage' => 'Manage equipment and rate cards',
                'maintenance.manage' => 'Manage maintenance records',
            ],
            'Customers' => [
                'customers.manage' => 'Manage customers',
            ],
            'Finance' => [
                'invoices.manage' => 'Manage invoices',
                'payments.manage' => 'Record payments and receipts',
            ],
            'Administration' => [
                'company.manage' => 'Manage company setup',
                'documents.manage' => 'Manage company document center',
                'locations.manage' => 'Manage branches, warehouses, and storage locations',
                'team.manage' => 'Manage team members',
                'roles.manage' => 'Create roles and permissions',
            ],
        ];
    }

    /**
     * @return array<string, array{name: string, description: string, permissions: array<int, string>, sort_order: int}>
     */
    public function defaultRoles(): array
    {
        $allPermissions = $this->permissionKeys();

        return [
            'owner' => [
                'name' => 'Owner',
                'description' => 'Full control over company setup, team access, billing handoff, and all rental operations.',
                'permissions' => $allPermissions,
                'sort_order' => 10,
            ],
            'admin' => [
                'name' => 'Admin',
                'description' => 'Can manage the team, company configuration, and rental operations while ownership stays with the business owner.',
                'permissions' => $allPermissions,
                'sort_order' => 20,
            ],
            'sales' => [
                'name' => 'Sales',
                'description' => 'Best for users who create customers, prepare quotes, and follow rental opportunities.',
                'permissions' => ['dashboard.view', 'customers.manage', 'quotes.manage', 'availability.view'],
                'sort_order' => 30,
            ],
            'operations' => [
                'name' => 'Operations',
                'description' => 'Best for dispatch, returns, equipment movement, and day-to-day rental execution.',
                'permissions' => ['dashboard.view', 'rentals.manage', 'dispatch.manage', 'availability.view', 'equipment.manage'],
                'sort_order' => 40,
            ],
            'accounts' => [
                'name' => 'Accounts',
                'description' => 'Best for invoices, payments, receipts, and revenue reporting.',
                'permissions' => ['dashboard.view', 'reports.view', 'invoices.manage', 'payments.manage'],
                'sort_order' => 50,
            ],
            'maintenance' => [
                'name' => 'Maintenance',
                'description' => 'Best for equipment service, inspection, repair notes, and workshop tracking.',
                'permissions' => ['dashboard.view', 'equipment.manage', 'maintenance.manage'],
                'sort_order' => 60,
            ],
            'viewer' => [
                'name' => 'Viewer',
                'description' => 'Read-only business visibility for managers, auditors, or external stakeholders.',
                'permissions' => ['dashboard.view', 'reports.view', 'availability.view'],
                'sort_order' => 70,
            ],
        ];
    }

    public function ensureDefaults(Company $company): void
    {
        foreach ($this->defaultRoles() as $slug => $role) {
            $companyRole = CompanyRole::firstOrNew(
                [
                    'company_id' => $company->id,
                    'slug' => $slug,
                ],
            );

            if (! $companyRole->exists) {
                $companyRole->fill([
                    'name' => $role['name'],
                    'description' => $role['description'],
                    'permissions' => $role['permissions'],
                    'is_system' => true,
                    'sort_order' => $role['sort_order'],
                ])->save();

                continue;
            }

            if (! $companyRole->is_system) {
                $companyRole->forceFill(['is_system' => true])->save();
            }
        }
    }

    /**
     * @return Collection<int, CompanyRole>
     */
    public function rolesForCompany(Company $company): Collection
    {
        $this->ensureDefaults($company);

        return $company->roles()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    public function permissionKeys(): array
    {
        return collect($this->permissionGroups())
            ->flatMap(fn (array $permissions): array => array_keys($permissions))
            ->values()
            ->all();
    }
}

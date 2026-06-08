<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\CompanyRole;
use App\Services\ActivityLogger;
use App\Support\CompanyRoleCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function __construct(private CompanyRoleCatalog $roleCatalog, private ActivityLogger $activity) {}

    public function index(Request $request): View
    {
        $company = $request->user()->currentCompany;

        return view('settings.roles', [
            'roles' => $this->roleCatalog->rolesForCompany($company),
            'permissionGroups' => $this->roleCatalog->permissionGroups(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $request->user()->currentCompany;
        $validated = $this->validateRole($request);
        $slug = $this->uniqueRoleSlug($company->id, $validated['name']);

        $role = CompanyRole::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'permissions' => $validated['permissions'] ?? [],
            'is_system' => false,
            'sort_order' => (int) CompanyRole::where('company_id', $company->id)->max('sort_order') + 10,
        ]);

        $this->activity->log('roles', 'created', "Created role {$role->name}.", $role, [
            'permissions' => $role->permissions,
        ]);

        return back()->with('status', 'Role created successfully.');
    }

    public function update(Request $request, CompanyRole $role): RedirectResponse
    {
        $this->abortUnlessCompanyRole($request, $role);
        $validated = $this->validateRole($request);

        $role->update([
            'name' => $role->is_system ? $role->name : $validated['name'],
            'description' => $validated['description'] ?? null,
            'permissions' => $role->slug === 'owner' ? $this->roleCatalog->permissionKeys() : ($validated['permissions'] ?? []),
        ]);

        $this->activity->log('roles', 'updated', "Updated permissions for role {$role->name}.", $role, [
            'changes' => $this->activity->changesFor($role),
        ]);

        return back()->with('status', 'Role permissions updated successfully.');
    }

    public function destroy(Request $request, CompanyRole $role): RedirectResponse
    {
        $this->abortUnlessCompanyRole($request, $role);

        if ($role->is_system) {
            return back()->withErrors(['role' => 'Default roles cannot be deleted. You can adjust their permissions instead.']);
        }

        $isAssigned = DB::table('company_user')
            ->where('company_id', $role->company_id)
            ->where('role', $role->slug)
            ->exists();

        if ($isAssigned) {
            return back()->withErrors(['role' => 'This role is assigned to team members. Move those users to another role before deleting it.']);
        }

        $roleName = $role->name;
        $permissions = $role->permissions;

        $role->delete();

        $this->activity->log('roles', 'deleted', "Deleted role {$roleName}.", null, [
            'role' => $roleName,
            'permissions' => $permissions,
        ]);

        return back()->with('status', 'Role deleted successfully.');
    }

    /**
     * @return array{name: string, description?: string|null, permissions?: array<int, string>}
     */
    private function validateRole(Request $request): array
    {
        $permissionKeys = $this->roleCatalog->permissionKeys();

        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($permissionKeys)],
        ]);
    }

    private function abortUnlessCompanyRole(Request $request, CompanyRole $role): void
    {
        abort_unless((int) $role->company_id === (int) $request->user()->current_company_id, 404);
    }

    private function uniqueRoleSlug(int $companyId, string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'custom-role';
        $slug = $baseSlug;
        $counter = 2;

        while (CompanyRole::where('company_id', $companyId)->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}

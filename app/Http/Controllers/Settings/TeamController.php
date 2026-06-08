<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\CompanyRoleCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    public function __construct(private CompanyRoleCatalog $roleCatalog, private ActivityLogger $activity) {}

    public function index(): View
    {
        $company = auth()->user()->currentCompany;

        return view('settings.team', [
            'company' => $company,
            'members' => $company->users()->orderBy('company_user.role')->orderBy('name')->get(),
            'roles' => $this->roleCatalog->rolesForCompany($company),
        ]);
    }

    public function edit(User $user): View
    {
        $company = auth()->user()->currentCompany;
        $this->abortUnlessCompanyMember($company->id, $user);

        return view('settings.team-edit', [
            'member' => $user,
            'memberRole' => $this->roleForCompany($company->id, $user),
            'roles' => $this->roleCatalog->rolesForCompany($company),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $request->user()->currentCompany;
        $this->roleCatalog->ensureDefaults($company);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::exists('company_roles', 'slug')->where('company_id', $company->id)],
        ]);

        $temporaryPassword = null;
        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            $temporaryPassword = $validated['password'] ?? Str::password(12);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'email_verified_at' => now(),
                'password' => $temporaryPassword,
                'current_company_id' => $company->id,
            ]);
        } else {
            $user->forceFill([
                'name' => $user->name ?: $validated['name'],
                'current_company_id' => $user->current_company_id ?: $company->id,
            ])->save();
        }

        if ($company->users()->whereKey($user->id)->exists()) {
            return back()->withErrors(['email' => 'This user is already part of this company.'])->withInput();
        }

        $company->users()->attach($user, [
            'role' => $validated['role'],
            'joined_at' => now(),
        ]);

        $this->activity->log('team', 'created', "Added team member {$user->name}.", $user, [
            'email' => $user->email,
            'role' => $validated['role'],
            'password_was_set' => ! empty($validated['password']),
        ]);

        return back()
            ->with('status', 'Team member added successfully.')
            ->with('temporary_password', $temporaryPassword);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $company = $request->user()->currentCompany;
        $this->abortUnlessCompanyMember($company->id, $user);
        $this->roleCatalog->ensureDefaults($company);

        $validated = $request->validate([
            'name' => [$request->isMethod('put') ? 'required' : 'sometimes', 'string', 'max:255'],
            'email' => [$request->isMethod('put') ? 'required' : 'sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::exists('company_roles', 'slug')->where('company_id', $company->id)],
        ]);

        $oldRole = $this->roleForCompany($company->id, $user);

        if ($oldRole === 'owner' && $validated['role'] !== 'owner') {
            $this->ensureAnotherOwnerExists($company->id, $user->id);
        }

        $userDetails = collect($validated)->only(['name', 'email'])->all();

        if (! empty($validated['password'])) {
            $userDetails['password'] = $validated['password'];
        }

        $user->forceFill($userDetails)->save();
        $changes = $this->activity->changesFor($user, ['password', 'remember_token']);

        $company->users()->updateExistingPivot($user->id, [
            'role' => $validated['role'],
        ]);

        $this->activity->log('team', 'updated', "Updated team member {$user->name}.", $user, [
            'changes' => $changes,
            'role' => [
                'old' => $oldRole,
                'new' => $validated['role'],
            ],
            'password_reset' => ! empty($validated['password']),
        ]);

        return back()->with('status', 'Team member role updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $company = $request->user()->currentCompany;
        $this->abortUnlessCompanyMember($company->id, $user);

        if ($request->user()->is($user)) {
            return back()->withErrors(['member' => 'You cannot remove your own access. Ask another owner to do it.']);
        }

        if ($this->roleForCompany($company->id, $user) === 'owner') {
            $this->ensureAnotherOwnerExists($company->id, $user->id);
        }

        $company->users()->detach($user->id);

        if ((int) $user->current_company_id === (int) $company->id) {
            $user->forceFill(['current_company_id' => $user->companies()->value('companies.id')])->save();
        }

        $this->activity->log('team', 'deleted', "Removed team member {$user->name}.", $user, [
            'email' => $user->email,
        ]);

        return back()->with('status', 'Team member removed successfully.');
    }

    private function abortUnlessCompanyMember(int $companyId, User $user): void
    {
        abort_unless($user->companies()->whereKey($companyId)->exists(), 404);
    }

    private function roleForCompany(int $companyId, User $user): ?string
    {
        return $user->companies()->whereKey($companyId)->value('company_user.role');
    }

    private function ensureAnotherOwnerExists(int $companyId, int $exceptUserId): void
    {
        $hasAnotherOwner = User::whereHas('companies', function ($query) use ($companyId, $exceptUserId): void {
            $query
                ->whereKey($companyId)
                ->where('company_user.role', 'owner')
                ->where('users.id', '!=', $exceptUserId);
        })->exists();

        abort_unless($hasAnotherOwner, 422, 'At least one owner must remain on the company.');
    }
}

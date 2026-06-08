<?php

namespace App\Http\Middleware;

use App\Support\CompanyRoleCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyPermission
{
    public function __construct(private CompanyRoleCatalog $roleCatalog) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();
        $company = $user?->currentCompany;

        abort_unless($user && $company, 403);

        $this->roleCatalog->ensureDefaults($company);

        $isAllowed = collect($permissions)
            ->contains(fn (string $permission): bool => $user->hasCurrentCompanyPermission($permission));

        abort_unless($isAllowed, 403);

        return $next($request);
    }
}

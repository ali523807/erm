<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyIsSelected
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $company = $user->currentCompany;

        if ($company && $user->companies()->whereKey($company->id)->exists()) {
            return $next($request);
        }

        $company = $user->companies()->first();

        if (! $company) {
            $company = Company::create([
                'name' => $user->name.' Workspace',
                'slug' => $this->uniqueCompanySlug($user->name.' Workspace'),
                'email' => $user->email,
            ]);

            $company->users()->attach($user, [
                'role' => 'owner',
                'joined_at' => now(),
            ]);
        }

        $user->forceFill(['current_company_id' => $company->id])->save();

        return $next($request);
    }

    private function uniqueCompanySlug(string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'company';
        $slug = $baseSlug;
        $counter = 2;

        while (Company::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}

<?php

namespace App\Actions\Fortify;

use App\Mail\NewCompanyRegistrationMail;
use App\Models\Company;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Throwable;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'plan' => ['required', 'string', Rule::exists('subscription_plans', 'slug')->where('is_active', true)],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        $registration = DB::transaction(function () use ($input): array {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
            ]);

            $company = Company::create([
                'name' => $input['company_name'],
                'slug' => $this->uniqueCompanySlug($input['company_name']),
                'email' => $input['email'],
            ]);

            $company->users()->attach($user, [
                'role' => 'owner',
                'joined_at' => now(),
            ]);

            $selectedPlan = SubscriptionPlan::where('slug', $input['plan'])->first();

            if ($selectedPlan) {
                $company->subscription()->create([
                    'subscription_plan_id' => $selectedPlan->id,
                    'status' => 'trialing',
                    'billing_cycle' => 'monthly',
                    'amount' => $selectedPlan->monthly_price,
                    'currency' => 'USD',
                    'trial_ends_at' => now()->addDays(14)->toDateString(),
                    'current_period_starts_at' => now()->toDateString(),
                    'current_period_ends_at' => now()->addMonth()->toDateString(),
                    'next_billing_at' => now()->addDays(14)->toDateString(),
                ]);
            }

            $user->forceFill(['current_company_id' => $company->id])->save();

            return [
                'company' => $company,
                'selectedPlan' => $selectedPlan,
                'user' => $user,
            ];
        });

        try {
            Mail::to(config('mail.admin.address'))->send(new NewCompanyRegistrationMail(
                $registration['user'],
                $registration['company'],
                $registration['selectedPlan'],
            ));
        } catch (Throwable $exception) {
            report($exception);
        }

        return $registration['user'];
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

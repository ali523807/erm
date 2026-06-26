<?php

namespace App\Console\Commands;

use App\Mail\NewCompanyRegistrationMail;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TestAdminRegistrationMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test-admin-registration {--to= : Override the configured admin recipient}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test new-registration email to the configured platform admin address';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $company = Company::with(['users', 'subscription.plan'])->latest()->first();

        if (! $company) {
            $this->error('No company found. Register or seed a company first.');

            return self::FAILURE;
        }

        $user = $company->users->first();

        if (! $user) {
            $this->error("Company {$company->name} has no owner user to include in the email.");

            return self::FAILURE;
        }

        $recipient = $this->option('to') ?: config('mail.admin.address');

        try {
            Mail::to($recipient)->send(new NewCompanyRegistrationMail(
                $user,
                $company,
                $company->subscription?->plan,
            ));
        } catch (Throwable $exception) {
            $this->error('Mail failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Admin registration email sent to {$recipient} using company {$company->name}.");

        return self::SUCCESS;
    }
}

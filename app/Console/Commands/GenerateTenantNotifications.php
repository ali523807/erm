<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\NotificationGenerator;
use Illuminate\Console\Command;

class GenerateTenantNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:generate {--company= : Generate reminders for one company id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate tenant reminder notifications from operational records';

    /**
     * Execute the console command.
     */
    public function handle(NotificationGenerator $generator): int
    {
        $query = Company::query();

        if ($this->option('company')) {
            $query->whereKey($this->option('company'));
        }

        $total = 0;

        $query->each(function (Company $company) use ($generator, &$total): void {
            $count = $generator->generateForCompany($company);
            $total += $count;
            $this->line("{$company->name}: {$count} notifications");
        });

        $this->info("Generated {$total} notifications.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\MaintenanceLog;
use App\Models\ProductDocument;
use App\Models\Quote;
use App\Models\Rental;
use App\Models\TenantNotification;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class NotificationGenerator
{
    /**
     * Generate operational reminders for one company.
     */
    public function generateForCompany(Company $company): int
    {
        return DB::transaction(function () use ($company): int {
            $count = 0;
            $count += $this->invoiceReminders($company);
            $count += $this->rentalReminders($company);
            $count += $this->quoteReminders($company);
            $count += $this->maintenanceReminders($company);
            $count += $this->documentReminders($company);

            return $count;
        });
    }

    private function invoiceReminders(Company $company): int
    {
        $count = 0;

        Invoice::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('balance_due', '>', 0)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', now()->addDays(7)->toDateString())
            ->get()
            ->each(function (Invoice $invoice) use (&$count): void {
                $isOverdue = $invoice->due_date->isPast() && ! $invoice->due_date->isToday();
                $count += $this->createIfMissing([
                    'company_id' => $invoice->company_id,
                    'type' => $isOverdue ? 'invoice_overdue' : 'invoice_due',
                    'severity' => $isOverdue ? 'danger' : 'warning',
                    'title' => $isOverdue ? "Invoice {$invoice->invoice_number} is overdue" : "Invoice {$invoice->invoice_number} is due soon",
                    'body' => 'Outstanding balance: '.app(Money::class)->format($invoice->balance_due, $invoice->currency),
                    'action_label' => 'View Invoice',
                    'action_url' => route('invoices.show', $invoice),
                    'due_at' => $invoice->due_date,
                    'unique_key' => "invoice:{$invoice->id}:due:{$invoice->due_date->toDateString()}",
                    'data' => ['invoice_id' => $invoice->id],
                ]);
            });

        return $count;
    }

    private function rentalReminders(Company $company): int
    {
        $count = 0;

        Rental::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereNotIn('status', ['returned', 'closed', 'cancelled'])
            ->whereNotNull('pickup_date')
            ->whereDate('pickup_date', '<=', now()->addDays(3)->toDateString())
            ->get()
            ->each(function (Rental $rental) use (&$count): void {
                $isOverdue = $rental->pickup_date->isPast() && ! $rental->pickup_date->isToday();
                $count += $this->createIfMissing([
                    'company_id' => $rental->company_id,
                    'type' => $isOverdue ? 'rental_overdue' : 'rental_pickup',
                    'severity' => $isOverdue ? 'danger' : 'warning',
                    'title' => $isOverdue ? "Rental RTN-{$rental->id} return is overdue" : "Rental RTN-{$rental->id} pickup is due soon",
                    'body' => $rental->delivery_location,
                    'action_label' => 'View Rental',
                    'action_url' => route('rentals.show', $rental),
                    'due_at' => $rental->pickup_date,
                    'unique_key' => "rental:{$rental->id}:pickup:{$rental->pickup_date->toDateString()}",
                    'data' => ['rental_id' => $rental->id],
                ]);
            });

        return $count;
    }

    private function quoteReminders(Company $company): int
    {
        $count = 0;

        Quote::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereIn('status', ['draft', 'sent'])
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '<=', now()->addDays(3)->toDateString())
            ->get()
            ->each(function (Quote $quote) use (&$count): void {
                $count += $this->createIfMissing([
                    'company_id' => $quote->company_id,
                    'type' => 'quote_expiring',
                    'severity' => 'warning',
                    'title' => "Quote {$quote->quote_number} is expiring soon",
                    'body' => 'Follow up before the quote validity date passes.',
                    'action_label' => 'View Quote',
                    'action_url' => route('quotes.show', $quote),
                    'due_at' => $quote->valid_until,
                    'unique_key' => "quote:{$quote->id}:valid_until:{$quote->valid_until->toDateString()}",
                    'data' => ['quote_id' => $quote->id],
                ]);
            });

        return $count;
    }

    private function maintenanceReminders(Company $company): int
    {
        $count = 0;

        MaintenanceLog::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('scheduled_at')
            ->whereDate('scheduled_at', '<=', now()->addDays(7)->toDateString())
            ->get()
            ->each(function (MaintenanceLog $maintenance) use (&$count): void {
                $count += $this->createIfMissing([
                    'company_id' => $maintenance->company_id,
                    'type' => 'maintenance_due',
                    'severity' => $maintenance->priority === 'high' ? 'danger' : 'warning',
                    'title' => "Maintenance due: {$maintenance->title}",
                    'body' => $maintenance->product?->name,
                    'action_label' => 'Open Maintenance',
                    'action_url' => route('maintenance.index'),
                    'due_at' => $maintenance->scheduled_at,
                    'unique_key' => "maintenance:{$maintenance->id}:scheduled:{$maintenance->scheduled_at->toDateString()}",
                    'data' => ['maintenance_id' => $maintenance->id],
                ]);
            });

        return $count;
    }

    private function documentReminders(Company $company): int
    {
        $count = 0;

        ProductDocument::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', now()->addDays(30)->toDateString())
            ->get()
            ->each(function (ProductDocument $document) use (&$count): void {
                $count += $this->createIfMissing([
                    'company_id' => $document->company_id,
                    'type' => 'document_expiring',
                    'severity' => 'warning',
                    'title' => "Document expiring: {$document->title}",
                    'body' => $document->product?->name,
                    'action_label' => 'View Equipment',
                    'action_url' => route('products.show', $document->product_id),
                    'due_at' => $document->expires_at,
                    'unique_key' => "document:{$document->id}:expires:{$document->expires_at->toDateString()}",
                    'data' => ['document_id' => $document->id],
                ]);
            });

        Document::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', now()->addDays(30)->toDateString())
            ->get()
            ->each(function (Document $document) use (&$count): void {
                $count += $this->createIfMissing([
                    'company_id' => $document->company_id,
                    'type' => 'document_expiring',
                    'severity' => 'warning',
                    'title' => "Document expiring: {$document->title}",
                    'body' => $document->notes,
                    'action_label' => 'Open Documents',
                    'action_url' => route('documents.index', ['expiry' => 'expiring']),
                    'due_at' => $document->expires_at,
                    'unique_key' => "global-document:{$document->id}:expires:{$document->expires_at->toDateString()}",
                    'data' => ['document_id' => $document->id],
                ]);
            });

        return $count;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createIfMissing(array $attributes): int
    {
        $notification = TenantNotification::withoutGlobalScopes()->firstOrCreate(
            [
                'company_id' => $attributes['company_id'],
                'unique_key' => $attributes['unique_key'],
            ],
            $attributes,
        );

        return $notification->wasRecentlyCreated ? 1 : 0;
    }
}

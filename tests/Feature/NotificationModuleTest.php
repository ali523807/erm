<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Rental;
use App\Models\TenantNotification;
use App\Models\User;
use App\Services\NotificationGenerator;
use App\Support\CompanyRoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates invoice and rental reminders for a company', function () {
    [$company, $owner] = notificationUser('owner');
    $this->actingAs($owner);

    $rental = notificationRental($company);

    Invoice::create([
        'company_id' => $company->id,
        'rental_id' => $rental->id,
        'customer_id' => $rental->customer_id,
        'invoice_number' => 'INV-TEST-001',
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(2)->toDateString(),
        'status' => 'issued',
        'subtotal' => 1000,
        'total_amount' => 1000,
        'paid_amount' => 0,
        'balance_due' => 1000,
    ]);

    $count = app(NotificationGenerator::class)->generateForCompany($company);

    expect($count)->toBe(2);

    $this->assertDatabaseHas('tenant_notifications', [
        'company_id' => $company->id,
        'type' => 'invoice_due',
        'title' => 'Invoice INV-TEST-001 is due soon',
    ]);

    $this->assertDatabaseHas('tenant_notifications', [
        'company_id' => $company->id,
        'type' => 'rental_pickup',
    ]);
});

it('shows and marks notifications as read', function () {
    [$company, $owner] = notificationUser('owner');
    $notification = TenantNotification::create([
        'company_id' => $company->id,
        'type' => 'invoice_due',
        'severity' => 'warning',
        'title' => 'Invoice due soon',
        'body' => 'Outstanding balance',
        'unique_key' => 'test:invoice',
    ]);

    $this
        ->actingAs($owner)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Invoice due soon');

    $this
        ->actingAs($owner)
        ->patch(route('notifications.read', $notification))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('marks all visible notifications as read', function () {
    [$company, $owner] = notificationUser('owner');

    TenantNotification::create([
        'company_id' => $company->id,
        'type' => 'quote_expiring',
        'severity' => 'warning',
        'title' => 'Quote expiring',
        'unique_key' => 'test:quote',
    ]);

    $this
        ->actingAs($owner)
        ->patch(route('notifications.read-all'))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(TenantNotification::whereNull('read_at')->count())->toBe(0);
});

it('runs the notification generator command', function () {
    [$company, $owner] = notificationUser('owner');
    $this->actingAs($owner);

    $rental = notificationRental($company);

    Invoice::create([
        'company_id' => $company->id,
        'rental_id' => $rental->id,
        'customer_id' => $rental->customer_id,
        'invoice_number' => 'INV-CMD-001',
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->toDateString(),
        'status' => 'issued',
        'subtotal' => 200,
        'total_amount' => 200,
        'paid_amount' => 0,
        'balance_due' => 200,
    ]);

    $this
        ->artisan('notifications:generate', ['--company' => $company->id])
        ->assertExitCode(0);

    $this->assertDatabaseHas('tenant_notifications', [
        'company_id' => $company->id,
        'type' => 'invoice_due',
    ]);
});

/**
 * @return array{0: Company, 1: User}
 */
function notificationUser(string $role): array
{
    $company = Company::create([
        'name' => 'Notification Test Rentals',
        'slug' => 'notification-test-rentals-'.fake()->unique()->numberBetween(1000, 9999),
        'email' => 'notifications@example.com',
        'country' => 'US',
        'timezone' => 'UTC',
    ]);

    app(CompanyRoleCatalog::class)->ensureDefaults($company);

    $user = User::factory()->create([
        'current_company_id' => $company->id,
    ]);

    $company->users()->attach($user, [
        'role' => $role,
        'joined_at' => now(),
    ]);

    return [$company, $user];
}

function notificationRental(Company $company): Rental
{
    $customer = Customer::create([
        'company_id' => $company->id,
        'company_name' => 'Reminder Customer',
        'contact_person' => 'Riley Demo',
        'phone' => '+1 555 0100',
        'email' => 'riley@example.com',
    ]);

    return Rental::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'rental_start_date' => now()->subDay()->toDateString(),
        'rental_end_date' => now()->addDay()->toDateString(),
        'pickup_date' => now()->addDay()->toDateString(),
        'status' => 'active',
        'delivery_location' => 'Main site',
    ]);
}

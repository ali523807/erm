<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Document;
use App\Models\User;
use App\Services\NotificationGenerator;
use App\Support\CompanyRoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('lets authorized users upload documents attached to customers', function () {
    Storage::fake('public');
    [$company, $owner] = documentUser('owner');
    $customer = documentCustomer($company);

    $this
        ->actingAs($owner)
        ->post(route('documents.store'), [
            'title' => 'Customer Insurance',
            'type' => 'insurance',
            'owner_type' => 'customer',
            'owner_id' => $customer->id,
            'file' => UploadedFile::fake()->create('insurance.pdf', 120, 'application/pdf'),
            'issued_at' => now()->subMonth()->toDateString(),
            'expires_at' => now()->addDays(20)->toDateString(),
            'notes' => 'Customer insurance policy.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $document = Document::firstOrFail();

    expect($document->title)->toBe('Customer Insurance')
        ->and($document->documentable->is($customer))->toBeTrue();

    Storage::disk('public')->assertExists($document->file_path);

    $this->assertDatabaseHas('activity_logs', [
        'company_id' => $company->id,
        'module' => 'documents',
        'action' => 'created',
    ]);
});

it('shows downloads and deletes documents', function () {
    Storage::fake('public');
    [$company, $owner] = documentUser('owner');
    Storage::disk('public')->put('documents/test.pdf', 'sample');

    $document = Document::create([
        'company_id' => $company->id,
        'uploaded_by' => $owner->id,
        'type' => 'other',
        'title' => 'Sample File',
        'original_name' => 'sample.pdf',
        'file_path' => 'documents/test.pdf',
        'disk' => 'public',
        'mime_type' => 'application/pdf',
        'size' => 6,
    ]);

    $this
        ->actingAs($owner)
        ->get(route('documents.index'))
        ->assertOk()
        ->assertSee('Sample File');

    $this
        ->actingAs($owner)
        ->get(route('documents.download', $document))
        ->assertOk();

    $this
        ->actingAs($owner)
        ->delete(route('documents.destroy', $document))
        ->assertRedirect()
        ->assertSessionHas('success');

    Storage::disk('public')->assertMissing('documents/test.pdf');
    $this->assertDatabaseMissing('documents', ['id' => $document->id]);
});

it('generates reminders for expiring global documents', function () {
    [$company, $owner] = documentUser('owner');
    $this->actingAs($owner);

    Document::create([
        'company_id' => $company->id,
        'uploaded_by' => $owner->id,
        'type' => 'trade_license',
        'title' => 'Expiring Trade License',
        'original_name' => 'license.pdf',
        'file_path' => 'documents/license.pdf',
        'disk' => 'public',
        'mime_type' => 'application/pdf',
        'size' => 100,
        'expires_at' => now()->addDays(12)->toDateString(),
    ]);

    app(NotificationGenerator::class)->generateForCompany($company);

    $this->assertDatabaseHas('tenant_notifications', [
        'company_id' => $company->id,
        'type' => 'document_expiring',
        'title' => 'Document expiring: Expiring Trade License',
    ]);
});

it('blocks users without document permission', function () {
    [, $salesUser] = documentUser('sales');

    $this
        ->actingAs($salesUser)
        ->get(route('documents.index'))
        ->assertForbidden();
});

/**
 * @return array{0: Company, 1: User}
 */
function documentUser(string $role): array
{
    $company = Company::create([
        'name' => 'Document Test Rentals',
        'slug' => 'document-test-rentals-'.fake()->unique()->numberBetween(1000, 9999),
        'email' => 'documents@example.com',
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

function documentCustomer(Company $company): Customer
{
    return Customer::create([
        'company_id' => $company->id,
        'company_name' => 'Document Customer',
        'contact_person' => 'Dana File',
        'phone' => '+1 555 0133',
        'email' => 'dana@example.com',
    ]);
}

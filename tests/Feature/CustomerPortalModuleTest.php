<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerPortalUser;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Rental;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('lets customer portal users login and view their dashboard', function () {
    [$company, $customer, $portalUser] = portalFixture();
    Quote::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'quote_number' => 'QTE-PORTAL-001',
        'quote_date' => now()->toDateString(),
        'rental_start_date' => now()->toDateString(),
        'rental_end_date' => now()->addDay()->toDateString(),
        'status' => 'sent',
        'subtotal' => 100,
        'total_amount' => 100,
    ]);

    $this
        ->post(route('customer-portal.login.store'), [
            'email' => $portalUser->email,
            'password' => 'Password123!',
        ])
        ->assertRedirect(route('customer-portal.dashboard'));

    $this
        ->actingAs($portalUser, 'customer')
        ->get(route('customer-portal.dashboard'))
        ->assertOk()
        ->assertSee($customer->company_name)
        ->assertSee('Quotes');
});

it('only shows records for the logged in customer', function () {
    [$company, $customer, $portalUser] = portalFixture();
    $otherCustomer = portalCustomer($company, 'Other Customer', 'other@example.com');

    Quote::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'quote_number' => 'QTE-MINE',
        'quote_date' => now()->toDateString(),
        'rental_start_date' => now()->toDateString(),
        'rental_end_date' => now()->addDay()->toDateString(),
        'status' => 'sent',
        'subtotal' => 100,
        'total_amount' => 100,
    ]);

    Quote::create([
        'company_id' => $company->id,
        'customer_id' => $otherCustomer->id,
        'quote_number' => 'QTE-OTHER',
        'quote_date' => now()->toDateString(),
        'rental_start_date' => now()->toDateString(),
        'rental_end_date' => now()->addDay()->toDateString(),
        'status' => 'sent',
        'subtotal' => 200,
        'total_amount' => 200,
    ]);

    $this
        ->actingAs($portalUser, 'customer')
        ->get(route('customer-portal.quotes'))
        ->assertOk()
        ->assertSee('QTE-MINE')
        ->assertDontSee('QTE-OTHER');
});

it('lets customers accept their own quote and blocks other customer quotes', function () {
    [$company, $customer, $portalUser] = portalFixture();
    $quote = portalQuote($company, $customer, 'QTE-ACCEPT');
    $otherQuote = portalQuote($company, portalCustomer($company, 'Other Customer', 'other@example.com'), 'QTE-BLOCK');

    $this
        ->actingAs($portalUser, 'customer')
        ->patch(route('customer-portal.quotes.status', $quote), ['status' => 'accepted'])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($quote->fresh()->status)->toBe('accepted');

    $this
        ->actingAs($portalUser, 'customer')
        ->patch(route('customer-portal.quotes.status', $otherQuote), ['status' => 'accepted'])
        ->assertNotFound();
});

it('lets customers upload and download their documents', function () {
    Storage::fake('public');
    [$company, $customer, $portalUser] = portalFixture();

    $this
        ->actingAs($portalUser, 'customer')
        ->post(route('customer-portal.documents.store'), [
            'title' => 'Payment Proof',
            'type' => 'payment_proof',
            'file' => UploadedFile::fake()->create('proof.pdf', 20, 'application/pdf'),
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $document = Document::firstOrFail();

    expect($document->company_id)->toBe($company->id)
        ->and($document->documentable_id)->toBe($customer->id);

    $this
        ->actingAs($portalUser, 'customer')
        ->get(route('customer-portal.documents.download', $document))
        ->assertOk();
});

it('shows and downloads invoice documents for the logged in customer', function () {
    Storage::fake('public');
    [$company, $customer, $portalUser] = portalFixture();
    $invoice = portalInvoice($company, portalRental($company, $customer), 'INV-PORTAL-001');

    Storage::disk('public')->put('portal/invoice.pdf', 'invoice document');

    $document = Document::create([
        'company_id' => $company->id,
        'documentable_type' => Invoice::class,
        'documentable_id' => $invoice->id,
        'type' => 'invoice',
        'title' => 'Invoice PDF',
        'original_name' => 'invoice.pdf',
        'file_path' => 'portal/invoice.pdf',
        'disk' => 'public',
        'mime_type' => 'application/pdf',
        'size' => 16,
    ]);

    $this
        ->actingAs($portalUser, 'customer')
        ->get(route('customer-portal.documents'))
        ->assertOk()
        ->assertSee('Invoice PDF');

    $this
        ->actingAs($portalUser, 'customer')
        ->get(route('customer-portal.documents.download', $document))
        ->assertOk();
});

/**
 * @return array{0: Company, 1: Customer, 2: CustomerPortalUser}
 */
function portalFixture(): array
{
    $company = Company::create([
        'name' => 'Portal Test Rentals',
        'slug' => 'portal-test-rentals-'.fake()->unique()->numberBetween(1000, 9999),
        'email' => 'portal-company@example.com',
        'country' => 'US',
        'timezone' => 'UTC',
    ]);

    $customer = portalCustomer($company, 'Portal Customer', 'portal.customer@example.com');

    $portalUser = CustomerPortalUser::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'name' => 'Portal Contact',
        'email' => 'portal.customer@example.com',
        'password' => Hash::make('Password123!'),
        'is_active' => true,
    ]);

    return [$company, $customer, $portalUser];
}

function portalCustomer(Company $company, string $name, string $email): Customer
{
    return Customer::create([
        'company_id' => $company->id,
        'company_name' => $name,
        'contact_person' => $name.' Contact',
        'phone' => '+1 555 0199',
        'email' => $email,
    ]);
}

function portalQuote(Company $company, Customer $customer, string $number): Quote
{
    return Quote::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'quote_number' => $number,
        'quote_date' => now()->toDateString(),
        'rental_start_date' => now()->toDateString(),
        'rental_end_date' => now()->addDay()->toDateString(),
        'status' => 'sent',
        'subtotal' => 100,
        'total_amount' => 100,
    ]);
}

function portalRental(Company $company, Customer $customer): Rental
{
    return Rental::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'rental_start_date' => now()->toDateString(),
        'rental_end_date' => now()->addDay()->toDateString(),
        'pickup_date' => now()->addDay()->toDateString(),
        'status' => 'active',
        'delivery_location' => 'Portal site',
    ]);
}

function portalInvoice(Company $company, Rental $rental, string $number): Invoice
{
    return Invoice::create([
        'company_id' => $company->id,
        'rental_id' => $rental->id,
        'customer_id' => $rental->customer_id,
        'invoice_number' => $number,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addWeek()->toDateString(),
        'status' => 'issued',
        'subtotal' => 100,
        'total_amount' => 100,
        'paid_amount' => 0,
        'balance_due' => 100,
    ]);
}

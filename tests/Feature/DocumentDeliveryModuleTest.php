<?php

use App\Mail\DocumentDeliveryMail;
use App\Models\Category;
use App\Models\Company;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\DocumentDelivery;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('emails a quote pdf and records the delivery', function () {
    Mail::fake();
    [$user, , , , , , , $quote] = documentDeliveryTenant();

    $this->actingAs($user)
        ->post(route('quotes.send', $quote), [
            'recipient_email' => 'client@example.test',
            'recipient_name' => 'Client Contact',
            'subject' => 'Updated Quote',
            'message' => 'Please review the attached quote.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    Mail::assertSent(DocumentDeliveryMail::class);

    $delivery = DocumentDelivery::firstOrFail();

    expect($delivery->type)->toBe('quote')
        ->and($delivery->status)->toBe('sent')
        ->and($delivery->recipient_email)->toBe('client@example.test')
        ->and($delivery->deliverable_id)->toBe($quote->id);
});

it('emails invoice receipts credit notes and statements', function () {
    Mail::fake();
    [$user, , $customer, , , $invoice, $payment, , $creditNote] = documentDeliveryTenant('document-bundle@example.com', 'Document Bundle Rentals');

    $this->actingAs($user)
        ->post(route('invoices.send', $invoice), [
            'recipient_email' => 'billing@example.test',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->post(route('payments.receipt.send', $payment), [
            'recipient_email' => 'billing@example.test',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->post(route('credit-notes.send', $creditNote), [
            'recipient_email' => 'billing@example.test',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->post(route('customers.statement.send', $customer), [
            'recipient_email' => 'billing@example.test',
            'as_of' => '2026-06-30',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    Mail::assertSent(DocumentDeliveryMail::class, 4);

    expect(DocumentDelivery::pluck('type')->all())->toContain('invoice', 'receipt', 'credit_note', 'statement')
        ->and(DocumentDelivery::where('status', 'sent')->count())->toBe(4);
});

it('shows delivery history in the delivery log', function () {
    [$user, $company, , , , $invoice] = documentDeliveryTenant('document-log@example.com', 'Document Log Rentals');

    DocumentDelivery::create([
        'company_id' => $company->id,
        'sent_by' => $user->id,
        'deliverable_type' => $invoice::class,
        'deliverable_id' => $invoice->id,
        'type' => 'invoice',
        'recipient_email' => 'client@example.test',
        'subject' => 'Invoice '.$invoice->invoice_number,
        'attachment_name' => $invoice->invoice_number.'.pdf',
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('document-deliveries.index'))
        ->assertOk()
        ->assertSee('Delivery Log')
        ->assertSee('client@example.test')
        ->assertSee('Invoice '.$invoice->invoice_number);
});

/**
 * @return array{0: User, 1: Company, 2: Customer, 3: Product, 4: Rental, 5: Invoice, 6: InvoicePayment, 7: Quote, 8: CreditNote}
 */
function documentDeliveryTenant(string $email = 'document-delivery@example.com', string $companyName = 'Document Delivery Rentals'): array
{
    $company = Company::create([
        'name' => $companyName,
        'slug' => str($companyName)->slug().'-'.str()->random(6),
        'email' => $email,
        'country' => 'US',
        'timezone' => 'UTC',
        'currency' => 'USD',
    ]);

    $user = User::factory()->create([
        'email' => $email,
        'current_company_id' => $company->id,
    ]);

    $company->users()->attach($user, [
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    $category = Category::create([
        'company_id' => $company->id,
        'name' => 'Generators',
    ]);

    $product = Product::create([
        'company_id' => $company->id,
        'name' => 'Silent Generator',
        'description' => 'Portable generator.',
        'category_id' => $category->id,
        'equipment_code' => 'GEN-DOC-001',
        'status' => 'available',
        'ownership_type' => 'owned',
        'unit_of_measure' => 'unit',
    ]);

    $customer = Customer::create([
        'company_id' => $company->id,
        'company_name' => 'Acme Build Co',
        'contact_person' => 'Sam Carter',
        'email' => 'sam@acme.test',
        'phone' => '+1 555 0188',
    ]);

    $rental = Rental::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'rental_start_date' => '2026-06-01',
        'rental_end_date' => '2026-06-03',
        'status' => 'returned',
    ]);

    $rental->rentalItems()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-03',
        'duration_type' => 'days',
        'no_of_duration' => 3,
        'rate_type' => 'daily',
        'rate' => 200,
        'deposit_amount' => 100,
        'total_days' => 3,
        'total_price' => 600,
        'status' => 'returned',
    ]);

    $invoice = Invoice::create([
        'company_id' => $company->id,
        'rental_id' => $rental->id,
        'customer_id' => $customer->id,
        'invoice_number' => 'INV-2026-0001',
        'currency' => 'USD',
        'base_currency' => 'USD',
        'exchange_rate' => 1,
        'invoice_date' => '2026-06-04',
        'due_date' => '2026-06-20',
        'status' => 'issued',
        'subtotal' => 0,
        'tax_amount' => 50,
        'discount_amount' => 0,
        'damage_amount' => 0,
        'late_fee_amount' => 0,
        'total_amount' => 0,
        'paid_amount' => 0,
        'balance_due' => 0,
    ]);
    $invoice->recalculateTotals();

    $payment = InvoicePayment::create([
        'company_id' => $company->id,
        'invoice_id' => $invoice->id,
        'payment_date' => '2026-06-05',
        'amount' => 100,
        'method' => 'cash',
        'reference' => 'RCPT-DOC-001',
    ]);

    $quote = Quote::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'quote_number' => 'QTE-2026-0001',
        'currency' => 'USD',
        'base_currency' => 'USD',
        'exchange_rate' => 1,
        'quote_date' => '2026-06-01',
        'valid_until' => '2026-06-15',
        'rental_start_date' => '2026-07-01',
        'rental_end_date' => '2026-07-03',
        'status' => 'sent',
        'subtotal' => 600,
        'discount_amount' => 0,
        'tax_amount' => 50,
        'total_amount' => 650,
        'base_total_amount' => 650,
        'terms' => '50% advance payment required.',
    ]);

    $quote->items()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-03',
        'duration_type' => 'days',
        'quantity' => 1,
        'no_of_duration' => 3,
        'rate' => 200,
        'deposit_amount' => 100,
        'line_total' => 600,
    ]);

    $creditNote = CreditNote::create([
        'company_id' => $company->id,
        'invoice_id' => $invoice->id,
        'customer_id' => $customer->id,
        'credit_note_number' => 'CRN-2026-0001',
        'credit_date' => '2026-06-06',
        'reason' => 'discount_adjustment',
        'amount' => 25,
        'refund_amount' => 0,
        'status' => 'applied',
    ]);

    return [$user, $company, $customer, $product, $rental, $invoice, $payment, $quote, $creditNote];
}

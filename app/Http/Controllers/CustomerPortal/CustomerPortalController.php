<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Rental;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerPortalController extends Controller
{
    public function dashboard(Request $request): View
    {
        $portalUser = $request->user('customer');

        return view('customer-portal.dashboard', [
            'portalUser' => $portalUser,
            'customer' => $portalUser->customer,
            'summary' => [
                'quotes' => $this->quoteQuery($request)->count(),
                'rentals' => $this->rentalQuery($request)->count(),
                'invoices' => $this->invoiceQuery($request)->count(),
                'balance' => (float) $this->invoiceQuery($request)->sum('balance_due'),
            ],
            'recentQuotes' => $this->quoteQuery($request)->latest()->limit(5)->get(),
            'recentRentals' => $this->rentalQuery($request)->latest()->limit(5)->get(),
            'recentInvoices' => $this->invoiceQuery($request)->latest()->limit(5)->get(),
        ]);
    }

    public function quotes(Request $request): View
    {
        return view('customer-portal.quotes', [
            'quotes' => $this->quoteQuery($request)->with('items.product')->latest()->get(),
        ]);
    }

    public function updateQuoteStatus(Request $request, Quote $quote): RedirectResponse
    {
        $this->abortUnlessCustomerRecord($request, $quote);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['accepted', 'declined'])],
        ]);

        abort_if($quote->status === 'converted', 422, 'Converted quotes cannot be changed from the portal.');

        $quote->update(['status' => $validated['status']]);

        return back()->with('status', 'Quote response submitted successfully.');
    }

    public function rentals(Request $request): View
    {
        return view('customer-portal.rentals', [
            'rentals' => $this->rentalQuery($request)->with(['rentalItems.product', 'agreement'])->latest()->get(),
        ]);
    }

    public function invoices(Request $request): View
    {
        return view('customer-portal.invoices', [
            'invoices' => $this->invoiceQuery($request)->with('payments')->latest()->get(),
        ]);
    }

    public function documents(Request $request): View
    {
        return view('customer-portal.documents', [
            'documents' => $this->documentQuery($request)->latest()->get(),
        ]);
    }

    public function storeDocument(Request $request): RedirectResponse
    {
        $portalUser = $request->user('customer');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['trade_license', 'tax_certificate', 'insurance', 'id_document', 'payment_proof', 'other'])],
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $file = $request->file('file');
        $path = $file->store("customer-portal/{$portalUser->company_id}/{$portalUser->customer_id}", 'public');

        Document::create([
            'company_id' => $portalUser->company_id,
            'documentable_type' => Customer::class,
            'documentable_id' => $portalUser->customer_id,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'disk' => 'public',
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize() ?: 0,
            'expires_at' => $validated['expires_at'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('status', 'Document uploaded successfully.');
    }

    public function downloadDocument(Request $request, Document $document): StreamedResponse
    {
        $this->abortUnlessCustomerDocument($request, $document);

        return Storage::disk($document->disk)->download($document->file_path, $document->original_name);
    }

    private function quoteQuery(Request $request)
    {
        $portalUser = $request->user('customer');

        return Quote::withoutGlobalScopes()
            ->where('company_id', $portalUser->company_id)
            ->where('customer_id', $portalUser->customer_id);
    }

    private function rentalQuery(Request $request)
    {
        $portalUser = $request->user('customer');

        return Rental::withoutGlobalScopes()
            ->where('company_id', $portalUser->company_id)
            ->where('customer_id', $portalUser->customer_id);
    }

    private function invoiceQuery(Request $request)
    {
        $portalUser = $request->user('customer');

        return Invoice::withoutGlobalScopes()
            ->where('company_id', $portalUser->company_id)
            ->where('customer_id', $portalUser->customer_id);
    }

    private function documentQuery(Request $request)
    {
        $portalUser = $request->user('customer');

        return Document::withoutGlobalScopes()
            ->where('company_id', $portalUser->company_id)
            ->where(function ($query) use ($portalUser, $request): void {
                $query
                    ->where(function ($subQuery) use ($portalUser): void {
                        $subQuery
                            ->where('documentable_type', Customer::class)
                            ->where('documentable_id', $portalUser->customer_id);
                    })
                    ->orWhere(function ($subQuery) use ($request): void {
                        $subQuery
                            ->where('documentable_type', Invoice::class)
                            ->whereIn('documentable_id', $this->invoiceQuery($request)->pluck('id'));
                    });
            });
    }

    private function abortUnlessCustomerRecord(Request $request, Quote|Rental|Invoice $record): void
    {
        $portalUser = $request->user('customer');

        abort_unless(
            (int) $record->company_id === (int) $portalUser->company_id
            && (int) $record->customer_id === (int) $portalUser->customer_id,
            404
        );
    }

    private function abortUnlessCustomerDocument(Request $request, Document $document): void
    {
        $portalUser = $request->user('customer');

        $isCustomerDocument = $document->documentable_type === Customer::class
            && (int) $document->documentable_id === (int) $portalUser->customer_id;

        $isInvoiceDocument = $document->documentable_type === Invoice::class
            && $this->invoiceQuery($request)->whereKey($document->documentable_id)->exists();

        abort_unless((int) $document->company_id === (int) $portalUser->company_id
            && ($isCustomerDocument || $isInvoiceDocument), 404);
    }
}

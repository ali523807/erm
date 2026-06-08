<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Rental;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /**
     * @var array<string, string>
     */
    private array $documentTypes = [
        'trade_license' => 'Trade License',
        'tax_certificate' => 'Tax Certificate',
        'insurance' => 'Insurance',
        'id_document' => 'ID Document',
        'agreement' => 'Agreement',
        'delivery_note' => 'Delivery Note',
        'return_note' => 'Return Note',
        'invoice_support' => 'Invoice Support',
        'payment_proof' => 'Payment Proof',
        'certificate' => 'Certificate',
        'manual' => 'Manual',
        'photo' => 'Photo',
        'other' => 'Other',
    ];

    public function __construct(private ActivityLogger $activity) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'type' => ['nullable', 'string', 'max:80'],
            'owner_type' => ['nullable', Rule::in(['company', 'customer', 'equipment', 'rental', 'invoice'])],
            'expiry' => ['nullable', Rule::in(['expiring', 'expired', 'none'])],
        ]);

        $documents = Document::with(['documentable', 'uploader'])
            ->when($validated['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->when($validated['owner_type'] ?? null, fn ($query, string $ownerType) => $this->filterOwnerType($query, $ownerType))
            ->when(($validated['expiry'] ?? null) === 'expiring', fn ($query) => $query->whereNotNull('expires_at')->whereDate('expires_at', '>=', now())->whereDate('expires_at', '<=', now()->addDays(30)))
            ->when(($validated['expiry'] ?? null) === 'expired', fn ($query) => $query->whereNotNull('expires_at')->whereDate('expires_at', '<', now()))
            ->when(($validated['expiry'] ?? null) === 'none', fn ($query) => $query->whereNull('expires_at'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('documents.index', [
            'documents' => $documents,
            'documentTypes' => $this->documentTypes,
            'attachableRecords' => $this->attachableRecords($request),
            'filters' => $validated,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys($this->documentTypes))],
            'owner_type' => ['required', Rule::in(['company', 'customer', 'equipment', 'rental', 'invoice'])],
            'owner_id' => [Rule::requiredIf(fn (): bool => $request->input('owner_type') !== 'company'), 'nullable', 'integer'],
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'notes' => ['nullable', 'string'],
        ]);

        $owner = $this->resolveOwner($request, $validated['owner_type'], $validated['owner_id'] ?? null);
        $file = $request->file('file');
        $path = $file->store("documents/{$request->user()->current_company_id}", 'public');

        $document = Document::create([
            'company_id' => $request->user()->current_company_id,
            'uploaded_by' => $request->user()->id,
            'documentable_type' => $owner ? $owner::class : null,
            'documentable_id' => $owner?->getKey(),
            'title' => $validated['title'],
            'type' => $validated['type'],
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'disk' => 'public',
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize() ?: 0,
            'issued_at' => $validated['issued_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->activity->log('documents', 'created', "Uploaded document {$document->title}.", $document, [
            'type' => $document->type,
            'owner_type' => $validated['owner_type'],
            'expires_at' => $document->expires_at?->toDateString(),
        ]);

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function download(Document $document): StreamedResponse
    {
        return Storage::disk($document->disk)->download($document->file_path, $document->original_name);
    }

    public function destroy(Document $document): RedirectResponse
    {
        $title = $document->title;
        Storage::disk($document->disk)->delete($document->file_path);
        $document->delete();

        $this->activity->log('documents', 'deleted', "Deleted document {$title}.", null, [
            'title' => $title,
        ]);

        return back()->with('success', 'Document deleted successfully.');
    }

    private function filterOwnerType($query, string $ownerType): void
    {
        $query->where('documentable_type', match ($ownerType) {
            'company' => Company::class,
            'customer' => Customer::class,
            'equipment' => Product::class,
            'rental' => Rental::class,
            'invoice' => Invoice::class,
        });
    }

    private function resolveOwner(Request $request, string $ownerType, ?int $ownerId)
    {
        $companyId = $request->user()->current_company_id;

        return match ($ownerType) {
            'company' => $request->user()->currentCompany,
            'customer' => Customer::where('company_id', $companyId)->findOrFail($ownerId),
            'equipment' => Product::where('company_id', $companyId)->findOrFail($ownerId),
            'rental' => Rental::where('company_id', $companyId)->findOrFail($ownerId),
            'invoice' => Invoice::where('company_id', $companyId)->findOrFail($ownerId),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function attachableRecords(Request $request): array
    {
        $companyId = $request->user()->current_company_id;

        return [
            'customers' => Customer::where('company_id', $companyId)->orderBy('company_name')->get(['id', 'company_name']),
            'equipment' => Product::where('company_id', $companyId)->orderBy('name')->get(['id', 'name', 'equipment_code']),
            'rentals' => Rental::where('company_id', $companyId)->latest()->limit(50)->get(['id', 'delivery_location', 'status']),
            'invoices' => Invoice::where('company_id', $companyId)->latest()->limit(50)->get(['id', 'invoice_number', 'status']),
        ];
    }
}

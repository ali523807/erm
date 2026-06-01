<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductDocumentController extends Controller
{
    /**
     * @var array<string, string>
     */
    private array $documentTypes = [
        'photo' => 'Photo',
        'certificate' => 'Certificate',
        'manual' => 'Manual',
        'inspection' => 'Inspection',
        'insurance' => 'Insurance',
        'warranty' => 'Warranty',
        'other' => 'Other',
    ];

    public function store(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys($this->documentTypes))],
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $file = $request->file('file');
        $path = $file->store("equipment/{$product->id}", 'public');

        $product->documents()->create([
            'title' => $validated['title'],
            'type' => $validated['type'],
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'disk' => 'public',
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize() ?: 0,
            'expires_at' => $validated['expires_at'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Equipment file uploaded successfully.');
    }

    public function download(Product $product, ProductDocument $document): StreamedResponse
    {
        $this->ensureDocumentBelongsToProduct($product, $document);

        return Storage::disk($document->disk)->download($document->file_path, $document->original_name);
    }

    public function destroy(Product $product, ProductDocument $document): RedirectResponse
    {
        $this->ensureDocumentBelongsToProduct($product, $document);

        Storage::disk($document->disk)->delete($document->file_path);
        $document->delete();

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Equipment file deleted successfully.');
    }

    private function ensureDocumentBelongsToProduct(Product $product, ProductDocument $document): void
    {
        abort_unless($document->product_id === $product->id, 404);
    }
}

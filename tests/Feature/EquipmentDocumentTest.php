<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('shows an equipment profile page', function () {
    [$user, $company] = createDocumentTenant();
    [$product] = createDocumentProduct($company);

    $this->actingAs($user)
        ->get(route('products.show', $product))
        ->assertOk()
        ->assertSee('Equipment profile')
        ->assertSee('Specifications')
        ->assertSee('Rental History')
        ->assertSee('Upload Files');
});

it('uploads downloads and deletes equipment documents', function () {
    Storage::fake('public');

    [$user, $company] = createDocumentTenant('document-upload@example.com', 'Document Upload Rentals');
    [$product] = createDocumentProduct($company);

    $this->actingAs($user)
        ->post(route('products.documents.store', $product), [
            'title' => 'Safety Certificate',
            'type' => 'certificate',
            'expires_at' => '2026-12-31',
            'notes' => 'Annual inspection passed.',
            'file' => UploadedFile::fake()->create('certificate.pdf', 120, 'application/pdf'),
        ])
        ->assertRedirect(route('products.show', $product));

    $document = ProductDocument::first();

    expect($document)->not->toBeNull()
        ->and($document->company_id)->toBe($company->id)
        ->and($document->product_id)->toBe($product->id)
        ->and($document->type)->toBe('certificate')
        ->and($document->expires_at->format('Y-m-d'))->toBe('2026-12-31');

    Storage::disk('public')->assertExists($document->file_path);

    $this->actingAs($user)
        ->get(route('products.documents.download', [$product, $document]))
        ->assertOk();

    $this->actingAs($user)
        ->delete(route('products.documents.destroy', [$product, $document]))
        ->assertRedirect(route('products.show', $product));

    expect(ProductDocument::count())->toBe(0);
    Storage::disk('public')->assertMissing($document->file_path);
});

/**
 * @return array{0: User, 1: Company}
 */
function createDocumentTenant(string $email = 'document-owner@example.com', string $companyName = 'Document Rentals'): array
{
    $company = Company::create([
        'name' => $companyName,
        'slug' => str($companyName)->slug().'-'.str()->random(6),
        'email' => $email,
        'country' => 'US',
        'timezone' => 'UTC',
    ]);

    $user = User::factory()->create([
        'email' => $email,
        'current_company_id' => $company->id,
    ]);

    $company->users()->attach($user, [
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    return [$user, $company];
}

/**
 * @return array{0: Product, 1: Category}
 */
function createDocumentProduct(Company $company): array
{
    $category = Category::create([
        'company_id' => $company->id,
        'name' => 'Generators',
        'description' => 'Power equipment',
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

    $product->attributes()->create([
        'company_id' => $company->id,
        'key' => 'Fuel Type',
        'value' => 'Diesel',
    ]);

    return [$product, $category];
}

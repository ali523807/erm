<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\CategoryAttributeTemplate;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\StorageLocation;
use App\Models\Warehouse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\DataTables;

class ProductsController extends Controller
{
    /**
     * @var array<string, string>
     */
    private array $equipmentStatuses = [
        'available' => 'Available',
        'reserved' => 'Reserved',
        'on_rent' => 'On Rent',
        'maintenance' => 'Maintenance',
        'damaged' => 'Damaged',
        'retired' => 'Retired',
        'lost' => 'Lost',
    ];

    /**
     * @var array<string, string>
     */
    private array $ownershipTypes = [
        'owned' => 'Owned',
        'leased' => 'Leased',
        'consigned' => 'Consigned',
        'customer_owned' => 'Customer Owned',
    ];

    /**
     * @var array<string, string>
     */
    private array $rateTypes = [
        'hourly' => 'Hourly',
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'custom' => 'Custom',
    ];

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $products = Product::with(['category', 'branch', 'warehouse', 'storageLocation'])->latest()->get();

            return DataTables::of($products)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('products._actions', ['product' => $row])->render();
                })
                ->addColumn('status', function ($row) {
                    return view('products._status', ['product' => $row])->render();
                })
                ->addColumn('asset_status', fn ($row) => str($row->status ?: 'available')->headline())
                ->addColumn('location_name', function ($row) {
                    return collect([
                        $row->branch?->name,
                        $row->warehouse?->name,
                        $row->storageLocation?->name,
                    ])->filter()->join(' / ') ?: ($row->location ?: 'Unassigned');
                })
                ->rawColumns(['action', 'status', 'asset_status', 'location_name'])
                ->make(true);
        }

        return view('products.index');
    }

    public function create(Request $request): View
    {
        return view('products.create', [
            ...$this->formData(),
            'product' => new Product([
                'status' => 'available',
                'category_id' => $request->integer('category_id') ?: null,
                'ownership_type' => 'owned',
                'unit_of_measure' => 'unit',
                'default_rate' => 0,
                'acquisition_cost' => 0,
                'replacement_value' => 0,
            ]),
            'attributes' => old('attributes', [['key' => '', 'value' => '']]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $product = Product::create($this->validatedData($request));

        $this->syncAttributes($product, $request->input('attributes', []));

        return redirect()
            ->route('products.index')
            ->with('success', 'Equipment created successfully.');
    }

    public function edit(Product $product): View
    {
        $product->load('attributes');

        return view('products.edit', [
            ...$this->formData(),
            'product' => $product,
            'attributes' => old('attributes', $product->attributes->map(fn (ProductAttribute $attribute): array => [
                'key' => $attribute->key,
                'value' => $attribute->value,
            ])->values()->all() ?: [['key' => '', 'value' => '']]),
        ]);
    }

    public function show(Product $product): View
    {
        $product->load([
            'attributes',
            'category.attributeTemplates',
            'branch',
            'warehouse',
            'storageLocation',
            'documents',
            'maintenanceLogs',
            'rentalItems.rental.customer',
        ]);

        return view('products.show', [
            'product' => $product,
            'documentTypes' => [
                'photo' => 'Photo',
                'certificate' => 'Certificate',
                'manual' => 'Manual',
                'inspection' => 'Inspection',
                'insurance' => 'Insurance',
                'warranty' => 'Warranty',
                'other' => 'Other',
            ],
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validatedData($request, $product));

        $this->syncAttributes($product, $request->input('attributes', []));

        return redirect()
            ->route('products.edit', $product)
            ->with('success', 'Equipment updated successfully.');
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::with('branch')->where('is_active', true)->orderBy('name')->get(),
            'storageLocations' => StorageLocation::with('warehouse.branch')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'categoryTemplates' => CategoryAttributeTemplate::orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->groupBy('category_id')
                ->map(fn ($templates) => $templates->map(fn (CategoryAttributeTemplate $template): array => [
                    'name' => $template->name,
                    'key' => $template->key,
                    'type' => $template->type,
                    'unit' => $template->unit,
                    'value' => $template->default_value ?? '',
                    'placeholder' => $template->placeholder,
                    'helpText' => $template->help_text,
                    'options' => $template->options ?? [],
                    'isRequired' => $template->is_required,
                ])->values()),
            'equipmentStatuses' => $this->equipmentStatuses,
            'ownershipTypes' => $this->ownershipTypes,
            'rateTypes' => $this->rateTypes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Product $product = null): array
    {
        $companyId = auth()->user()->current_company_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category_id' => ['required', Rule::exists('categories', 'id')->where('company_id', $companyId)],
            'equipment_code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'equipment_code')
                    ->where('company_id', $companyId)
                    ->ignore($product),
            ],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys($this->equipmentStatuses))],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('company_id', $companyId)],
            'warehouse_id' => ['nullable', Rule::exists('warehouses', 'id')->where('company_id', $companyId)],
            'storage_location_id' => ['nullable', Rule::exists('storage_locations', 'id')->where('company_id', $companyId)],
            'ownership_type' => ['required', Rule::in(array_keys($this->ownershipTypes))],
            'purchase_date' => ['nullable', 'date'],
            'acquisition_date' => ['nullable', 'date'],
            'warranty_expiry' => ['nullable', 'date'],
            'certificate_expires_at' => ['nullable', 'date'],
            'acquisition_cost' => ['nullable', 'numeric', 'min:0'],
            'replacement_value' => ['nullable', 'numeric', 'min:0'],
            'unit_of_measure' => ['required', 'string', 'max:50'],
            'default_rate_type' => ['nullable', Rule::in(array_keys($this->rateTypes))],
            'default_rate' => ['nullable', 'numeric', 'min:0'],
            'condition' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['acquisition_cost'] = $validated['acquisition_cost'] ?? 0;
        $validated['replacement_value'] = $validated['replacement_value'] ?? 0;
        $validated['default_rate'] = $validated['default_rate'] ?? 0;

        $this->validateRequiredTemplateAttributes((int) $validated['category_id'], $request->input('attributes', []));

        return $validated;
    }

    /**
     * @param  array<int, array{key?: string|null, value?: string|null}>  $attributes
     */
    private function syncAttributes(Product $product, array $attributes): void
    {
        ProductAttribute::where('product_id', $product->id)->delete();

        foreach ($attributes as $attribute) {
            if (($attribute['key'] ?? '') !== '' && ($attribute['value'] ?? null) !== null && $attribute['value'] !== '') {
                ProductAttribute::create([
                    'product_id' => $product->id,
                    'key' => $attribute['key'],
                    'value' => $attribute['value'],
                ]);
            }
        }
    }

    /**
     * @param  array<int, array{key?: string|null, value?: string|null}>  $attributes
     *
     * @throws ValidationException
     */
    private function validateRequiredTemplateAttributes(int $categoryId, array $attributes): void
    {
        $requiredTemplates = CategoryAttributeTemplate::where('category_id', $categoryId)
            ->where('is_required', true)
            ->get();

        $attributeValues = collect($attributes)
            ->mapWithKeys(fn (array $attribute): array => [
                (string) ($attribute['key'] ?? '') => $attribute['value'] ?? null,
            ]);

        $errors = [];

        foreach ($requiredTemplates as $template) {
            $value = $attributeValues->get($template->key, $attributeValues->get($template->name));

            if ($value === null || $value === '') {
                $errors['attributes'][] = "{$template->name} is required for this category.";
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }
}

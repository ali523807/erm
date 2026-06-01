<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\StorageLocation;
use App\Models\Warehouse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationSetupController extends Controller
{
    public function index(): View
    {
        return view('settings.locations', [
            'branches' => Branch::withCount('warehouses')->latest()->get(),
            'warehouses' => Warehouse::with(['branch'])->withCount('storageLocations')->latest()->get(),
            'storageLocations' => StorageLocation::with(['warehouse.branch'])->orderBy('sort_order')->latest()->get(),
        ]);
    }

    public function storeBranch(Request $request): RedirectResponse
    {
        Branch::create($this->validateBranch($request));

        return back()->with('status', 'Branch created successfully.');
    }

    public function updateBranch(Request $request, Branch $branch): RedirectResponse
    {
        $branch->update($this->validateBranch($request, $branch));

        return back()->with('status', 'Branch updated successfully.');
    }

    public function destroyBranch(Branch $branch): RedirectResponse
    {
        $branch->delete();

        return back()->with('status', 'Branch deleted successfully.');
    }

    public function storeWarehouse(Request $request): RedirectResponse
    {
        Warehouse::create($this->validateWarehouse($request));

        return back()->with('status', 'Warehouse created successfully.');
    }

    public function updateWarehouse(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $warehouse->update($this->validateWarehouse($request, $warehouse));

        return back()->with('status', 'Warehouse updated successfully.');
    }

    public function destroyWarehouse(Warehouse $warehouse): RedirectResponse
    {
        $warehouse->delete();

        return back()->with('status', 'Warehouse deleted successfully.');
    }

    public function storeStorageLocation(Request $request): RedirectResponse
    {
        StorageLocation::create($this->validateStorageLocation($request));

        return back()->with('status', 'Storage location created successfully.');
    }

    public function updateStorageLocation(Request $request, StorageLocation $storageLocation): RedirectResponse
    {
        $storageLocation->update($this->validateStorageLocation($request, $storageLocation));

        return back()->with('status', 'Storage location updated successfully.');
    }

    public function destroyStorageLocation(StorageLocation $storageLocation): RedirectResponse
    {
        $storageLocation->delete();

        return back()->with('status', 'Storage location deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateBranch(Request $request, ?Branch $branch = null): array
    {
        $companyId = auth()->user()->current_company_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('branches', 'code')
                    ->where('company_id', $companyId)
                    ->ignore($branch),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state_region' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateWarehouse(Request $request, ?Warehouse $warehouse = null): array
    {
        $companyId = auth()->user()->current_company_id;

        $validated = $request->validate([
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('company_id', $companyId)],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('warehouses', 'code')
                    ->where('company_id', $companyId)
                    ->ignore($warehouse),
            ],
            'type' => ['required', Rule::in(['yard', 'warehouse', 'depot', 'service_bay'])],
            'phone' => ['nullable', 'string', 'max:50'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state_region' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateStorageLocation(Request $request, ?StorageLocation $storageLocation = null): array
    {
        $companyId = auth()->user()->current_company_id;
        $warehouseId = $request->input('warehouse_id');

        $validated = $request->validate([
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', $companyId)],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('storage_locations', 'code')
                    ->where('company_id', $companyId)
                    ->where('warehouse_id', $warehouseId)
                    ->ignore($storageLocation),
            ],
            'type' => ['required', Rule::in(['zone', 'bay', 'bin', 'rack', 'shelf', 'yard_section'])],
            'parent_area' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}

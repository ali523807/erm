<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceLog;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaintenanceController extends Controller
{
    /**
     * @var array<string, string>
     */
    private array $types = [
        'maintenance' => 'Maintenance',
        'inspection' => 'Inspection',
        'repair' => 'Repair',
        'calibration' => 'Calibration',
        'certificate_renewal' => 'Certificate Renewal',
    ];

    /**
     * @var array<string, string>
     */
    private array $statuses = [
        'scheduled' => 'Scheduled',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    /**
     * @var array<string, string>
     */
    private array $priorities = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
    ];

    public function index(): View
    {
        return view('maintenance.index', [
            'logs' => MaintenanceLog::with('product.category')
                ->latest('scheduled_at')
                ->latest()
                ->get(),
            'products' => Product::with('category')->orderBy('name')->get(),
            'types' => $this->types,
            'statuses' => $this->statuses,
            'priorities' => $this->priorities,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedData($request);

        $log = MaintenanceLog::create($validated);
        $this->syncProductStatus($log);

        return redirect()
            ->back()
            ->with('success', 'Maintenance record created successfully.');
    }

    public function update(Request $request, MaintenanceLog $maintenance): RedirectResponse
    {
        $maintenance->update($this->validatedData($request));
        $this->syncProductStatus($maintenance);

        return redirect()
            ->back()
            ->with('success', 'Maintenance record updated successfully.');
    }

    public function destroy(MaintenanceLog $maintenance): RedirectResponse
    {
        $maintenance->delete();

        return redirect()
            ->back()
            ->with('success', 'Maintenance record deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        $companyId = auth()->user()->current_company_id;

        $validated = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'type' => ['required', Rule::in(array_keys($this->types))],
            'title' => ['required', 'string', 'max:255'],
            'priority' => ['required', Rule::in(array_keys($this->priorities))],
            'status' => ['required', Rule::in(array_keys($this->statuses))],
            'scheduled_at' => ['nullable', 'date'],
            'service_date' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'next_service_due' => ['nullable', 'date'],
            'service_provider' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'findings' => ['nullable', 'string'],
            'recommendations' => ['nullable', 'string'],
            'part_used' => ['nullable', 'string'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'downtime_hours' => ['nullable', 'numeric', 'min:0'],
            'affects_availability' => ['nullable', 'boolean'],
        ]);

        $validated['cost'] = $validated['cost'] ?? 0;
        $validated['downtime_hours'] = $validated['downtime_hours'] ?? 0;
        $validated['affects_availability'] = $request->boolean('affects_availability', true);

        return $validated;
    }

    private function syncProductStatus(MaintenanceLog $maintenance): void
    {
        if (! $maintenance->affects_availability) {
            return;
        }

        if (in_array($maintenance->status, ['scheduled', 'in_progress'], true)) {
            $maintenance->product()->update(['status' => 'maintenance']);
        }
    }
}

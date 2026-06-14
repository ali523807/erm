<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceLog;
use App\Models\ReturnInspection;
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
        'open' => 'Open',
        'scheduled' => 'Scheduled',
        'in_progress' => 'In Progress',
        'waiting_parts' => 'Waiting Parts',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    /**
     * @var array<string, string>
     */
    private array $priorities = [
        'low' => 'Low',
        'medium' => 'Medium',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
        'critical' => 'Critical',
    ];

    /**
     * @var array<string, string>
     */
    private array $finalEquipmentStatuses = [
        'available' => 'Available',
        'maintenance' => 'Keep in Maintenance',
        'damaged' => 'Keep Damaged',
        'retired' => 'Retired',
    ];

    public function index(): View
    {
        $companyId = auth()->user()->current_company_id;

        return view('maintenance.index', [
            'logs' => MaintenanceLog::with(['product.category', 'assignee', 'returnInspection.product'])
                ->latest('scheduled_at')
                ->latest()
                ->paginate(25),
            'returnInspections' => ReturnInspection::with(['product', 'rental.customer'])
                ->whereIn('next_equipment_status', ['maintenance', 'damaged'])
                ->latest('inspected_at')
                ->limit(50)
                ->get(),
            'types' => $this->types,
            'statuses' => $this->statuses,
            'priorities' => $this->priorities,
            'finalEquipmentStatuses' => $this->finalEquipmentStatuses,
            'summary' => [
                'open' => MaintenanceLog::whereIn('status', ['open', 'scheduled', 'in_progress', 'waiting_parts'])->count(),
                'urgent' => MaintenanceLog::whereIn('priority', ['urgent', 'critical'])->whereNotIn('status', ['completed', 'cancelled'])->count(),
                'waitingParts' => MaintenanceLog::where('status', 'waiting_parts')->count(),
                'completed' => MaintenanceLog::where('status', 'completed')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedData($request);
        $validated['work_order_number'] = ($validated['work_order_number'] ?? null) ?: $this->nextWorkOrderNumber();

        $log = MaintenanceLog::create($validated);
        $this->syncProductStatus($log);

        return redirect()
            ->back()
            ->with('success', 'Maintenance record created successfully.');
    }

    public function update(Request $request, MaintenanceLog $maintenance): RedirectResponse
    {
        $validated = $this->validatedData($request, $maintenance);
        $validated['work_order_number'] = ($validated['work_order_number'] ?? null) ?: $maintenance->work_order_number;

        $maintenance->update($validated);
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
    private function validatedData(Request $request, ?MaintenanceLog $maintenance = null): array
    {
        $companyId = auth()->user()->current_company_id;

        $validated = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'work_order_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('maintenance_logs', 'work_order_number')->where('company_id', $companyId)->ignore($maintenance),
            ],
            'assigned_to' => ['nullable', Rule::exists('company_user', 'user_id')->where('company_id', $companyId)],
            'return_inspection_id' => ['nullable', Rule::exists('return_inspections', 'id')->where('company_id', $companyId)],
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
            'parts_cost' => ['nullable', 'numeric', 'min:0'],
            'labor_cost' => ['nullable', 'numeric', 'min:0'],
            'vendor_cost' => ['nullable', 'numeric', 'min:0'],
            'downtime_hours' => ['nullable', 'numeric', 'min:0'],
            'completion_notes' => ['nullable', 'string'],
            'final_equipment_status' => ['nullable', Rule::in(array_keys($this->finalEquipmentStatuses))],
            'affects_availability' => ['nullable', 'boolean'],
        ]);

        $validated['parts_cost'] = $validated['parts_cost'] ?? 0;
        $validated['labor_cost'] = $validated['labor_cost'] ?? 0;
        $validated['vendor_cost'] = $validated['vendor_cost'] ?? 0;
        $costBreakdown = (float) $validated['parts_cost'] + (float) $validated['labor_cost'] + (float) $validated['vendor_cost'];
        $validated['cost'] = $costBreakdown > 0 ? $costBreakdown : ($validated['cost'] ?? 0);
        $validated['downtime_hours'] = $validated['downtime_hours'] ?? 0;
        $validated['affects_availability'] = $request->boolean('affects_availability', true);
        $validated['final_equipment_status'] = ($validated['final_equipment_status'] ?? null) ?: 'available';

        if (! empty($validated['return_inspection_id'])) {
            $inspection = ReturnInspection::find($validated['return_inspection_id']);

            if ($inspection) {
                $validated['product_id'] = $inspection->product_id;
            }
        }

        return $validated;
    }

    private function syncProductStatus(MaintenanceLog $maintenance): void
    {
        if (! $maintenance->affects_availability) {
            return;
        }

        if (in_array($maintenance->status, ['open', 'scheduled', 'in_progress', 'waiting_parts'], true)) {
            $maintenance->product()->update(['status' => 'maintenance']);
        }

        if ($maintenance->status === 'completed') {
            $maintenance->product()->update([
                'status' => $maintenance->final_equipment_status ?: 'available',
            ]);
        }
    }

    private function nextWorkOrderNumber(): string
    {
        $companyId = auth()->user()->current_company_id;
        $next = MaintenanceLog::withoutGlobalScopes()->where('company_id', $companyId)->count() + 1;

        return 'WO-'.now()->format('Y').'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\MaintenanceLog;
use App\Models\Rental;
use App\Models\RentalItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = Carbon::parse($validated['start_date'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $endDate = Carbon::parse($validated['end_date'] ?? now()->endOfMonth()->toDateString())->endOfDay();

        $invoices = Invoice::with('customer')
            ->whereBetween('invoice_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();
        $payments = InvoicePayment::with('invoice.customer')
            ->whereBetween('payment_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();
        $rentals = Rental::with(['customer', 'rentalItems.product'])
            ->whereBetween('rental_start_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();
        $rentalItems = RentalItem::with(['rental.customer', 'product'])
            ->whereHas('rental', fn ($query) => $query->whereBetween('rental_start_date', [$startDate->toDateString(), $endDate->toDateString()]))
            ->get();
        $maintenance = MaintenanceLog::with('product')
            ->where(function ($query) use ($startDate, $endDate): void {
                $query->whereBetween('scheduled_at', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhereBetween('completed_at', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhereBetween('service_date', [$startDate->toDateString(), $endDate->toDateString()]);
            })
            ->get();

        return view('reports.index', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'summary' => [
                'invoiced' => (float) $invoices->sum('total_amount'),
                'collected' => (float) $payments->sum('amount'),
                'outstanding' => (float) Invoice::sum('balance_due'),
                'damage' => (float) $invoices->sum('damage_amount'),
                'maintenanceCost' => (float) $maintenance->sum('cost'),
                'activeRentals' => Rental::whereIn('status', ['active', 'on_rent', 'open'])->count(),
                'overdueInvoices' => Invoice::whereNotIn('status', ['paid'])
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->count(),
            ],
            'monthlyRevenue' => $this->monthlyRevenue($invoices),
            'rentalStatus' => $rentals->groupBy(fn (Rental $rental): string => $rental->status ?: 'unknown')
                ->map(fn (Collection $group): int => $group->count())
                ->sortKeys(),
            'topCustomers' => $this->topCustomers($invoices),
            'equipmentUtilization' => $this->equipmentUtilization($rentalItems),
            'maintenanceSummary' => $this->maintenanceSummary($maintenance),
        ]);
    }

    /**
     * @return Collection<int, array{month: string, amount: float}>
     */
    private function monthlyRevenue(Collection $invoices): Collection
    {
        return $invoices
            ->groupBy(fn (Invoice $invoice): string => $invoice->invoice_date?->format('Y-m') ?: 'Unknown')
            ->map(fn (Collection $group, string $month): array => [
                'month' => $month,
                'amount' => (float) $group->sum('total_amount'),
            ])
            ->sortBy('month')
            ->values();
    }

    /**
     * @return Collection<int, array{customer: string, invoiced: float, balance: float}>
     */
    private function topCustomers(Collection $invoices): Collection
    {
        return $invoices
            ->groupBy(fn (Invoice $invoice): string => $invoice->customer?->company_name ?: 'Unknown customer')
            ->map(fn (Collection $group, string $customer): array => [
                'customer' => $customer,
                'invoiced' => (float) $group->sum('total_amount'),
                'balance' => (float) $group->sum('balance_due'),
            ])
            ->sortByDesc('invoiced')
            ->take(10)
            ->values();
    }

    /**
     * @return Collection<int, array{equipment: string, rentals: int, days: float, revenue: float}>
     */
    private function equipmentUtilization(Collection $rentalItems): Collection
    {
        return $rentalItems
            ->groupBy(fn (RentalItem $item): string => $item->product?->name ?: 'Unknown equipment')
            ->map(fn (Collection $group, string $equipment): array => [
                'equipment' => $equipment,
                'rentals' => $group->pluck('rental_id')->unique()->count(),
                'days' => (float) $group->sum('total_days'),
                'revenue' => (float) $group->sum('total_price'),
            ])
            ->sortByDesc('revenue')
            ->take(10)
            ->values();
    }

    /**
     * @return Collection<int, array{equipment: string, count: int, cost: float, downtime: float}>
     */
    private function maintenanceSummary(Collection $maintenance): Collection
    {
        return $maintenance
            ->groupBy(fn (MaintenanceLog $log): string => $log->product?->name ?: 'Unknown equipment')
            ->map(fn (Collection $group, string $equipment): array => [
                'equipment' => $equipment,
                'count' => $group->count(),
                'cost' => (float) $group->sum('cost'),
                'downtime' => (float) $group->sum('downtime_hours'),
            ])
            ->sortByDesc('cost')
            ->take(10)
            ->values();
    }
}

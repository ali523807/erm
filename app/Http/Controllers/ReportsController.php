<?php

namespace App\Http\Controllers;

use App\Models\Expense;
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

        $invoices = Invoice::with(['customer', 'creditNotes'])
            ->whereBetween('invoice_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();
        $payments = InvoicePayment::with('invoice.customer')
            ->whereBetween('payment_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();
        $rentals = Rental::with(['customer', 'invoice.creditNotes', 'expenses', 'rentalItems.product'])
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
        $expenses = Expense::with(['rental.customer', 'customer', 'product'])
            ->where('payment_status', '!=', 'voided')
            ->whereBetween('expense_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();
        $operatingCost = (float) $expenses->sum('total_amount');
        $maintenanceCost = (float) $maintenance->sum('cost');
        $invoiced = (float) $invoices->sum('total_amount');
        $creditTotal = (float) $invoices->sum(fn (Invoice $invoice): float => (float) $invoice->creditNotes->where('status', '!=', 'voided')->sum('amount'));
        $profitability = $this->rentalProfitability($rentals, $maintenance);
        $netProfit = $invoiced - $creditTotal - $operatingCost - $maintenanceCost;

        return view('reports.index', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'summary' => [
                'invoiced' => $invoiced,
                'collected' => (float) $payments->sum('amount'),
                'outstanding' => (float) Invoice::sum('balance_due'),
                'damage' => (float) $invoices->sum('damage_amount'),
                'credits' => $creditTotal,
                'maintenanceCost' => $maintenanceCost,
                'operatingCost' => $operatingCost,
                'netOperating' => $netProfit,
                'netProfit' => $netProfit,
                'marginPercent' => $invoiced > 0 ? round(($netProfit / $invoiced) * 100, 2) : 0,
                'lossRentals' => $profitability->where('net', '<', 0)->count(),
                'unrecoveredBillable' => (float) $expenses
                    ->where('is_billable', true)
                    ->whereNotIn('recovery_status', ['recovered'])
                    ->sum('total_amount'),
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
            'expenseSummary' => $this->expenseSummary($expenses),
            'rentalProfitability' => $profitability->sortBy('net')->take(10)->values(),
            'customerProfitability' => $this->customerProfitability($profitability),
            'equipmentProfitability' => $this->equipmentProfitability($rentalItems, $expenses, $maintenance),
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

    /**
     * @return Collection<int, array{category: string, count: int, cost: float, billable: float}>
     */
    private function expenseSummary(Collection $expenses): Collection
    {
        return $expenses
            ->groupBy(fn (Expense $expense): string => $expense->category ?: 'other')
            ->map(fn (Collection $group, string $category): array => [
                'category' => $category,
                'count' => $group->count(),
                'cost' => (float) $group->sum('total_amount'),
                'billable' => (float) $group->where('is_billable', true)->sum('total_amount'),
            ])
            ->sortByDesc('cost')
            ->take(10)
            ->values();
    }

    /**
     * @return Collection<int, array{rental_id: int, customer: string, revenue: float, credits: float, expenses: float, maintenance: float, cost: float, net: float, margin: float, status: string}>
     */
    private function rentalProfitability(Collection $rentals, Collection $maintenance): Collection
    {
        $maintenanceByProduct = $maintenance
            ->groupBy('product_id')
            ->map(fn (Collection $group): float => (float) $group->sum('cost'));

        return $rentals->map(function (Rental $rental) use ($maintenanceByProduct): array {
            $invoice = $rental->invoice;
            $revenue = $invoice ? (float) $invoice->total_amount : (float) $rental->rentalItems->sum('total_price');
            $credits = $invoice ? (float) $invoice->creditNotes->where('status', '!=', 'voided')->sum('amount') : 0.0;
            $expenses = (float) $rental->expenses->where('payment_status', '!=', 'voided')->sum('total_amount');
            $productIds = $rental->rentalItems->pluck('product_id')->filter()->unique();
            $maintenanceCost = (float) $productIds->sum(fn (int $productId): float => (float) ($maintenanceByProduct[$productId] ?? 0));
            $cost = $expenses + $maintenanceCost;
            $netRevenue = max(0, $revenue - $credits);
            $net = $netRevenue - $cost;

            return [
                'rental_id' => $rental->id,
                'customer' => $rental->customer?->company_name ?: 'Unknown customer',
                'revenue' => $revenue,
                'credits' => $credits,
                'expenses' => $expenses,
                'maintenance' => $maintenanceCost,
                'cost' => $cost,
                'net' => $net,
                'margin' => $netRevenue > 0 ? round(($net / $netRevenue) * 100, 2) : 0.0,
                'status' => $net < 0 ? 'Loss' : ($netRevenue > 0 && ($net / $netRevenue) < 0.15 ? 'Low Margin' : 'Healthy'),
            ];
        })->values();
    }

    /**
     * @return Collection<int, array{customer: string, rentals: int, revenue: float, cost: float, net: float, margin: float}>
     */
    private function customerProfitability(Collection $profitability): Collection
    {
        return $profitability
            ->groupBy('customer')
            ->map(function (Collection $group, string $customer): array {
                $revenue = (float) $group->sum(fn (array $row): float => max(0, $row['revenue'] - $row['credits']));
                $cost = (float) $group->sum('cost');
                $net = $revenue - $cost;

                return [
                    'customer' => $customer,
                    'rentals' => $group->count(),
                    'revenue' => $revenue,
                    'cost' => $cost,
                    'net' => $net,
                    'margin' => $revenue > 0 ? round(($net / $revenue) * 100, 2) : 0.0,
                ];
            })
            ->sortByDesc('net')
            ->take(10)
            ->values();
    }

    /**
     * @return Collection<int, array{equipment: string, rentals: int, revenue: float, expenses: float, maintenance: float, net: float, margin: float}>
     */
    private function equipmentProfitability(Collection $rentalItems, Collection $expenses, Collection $maintenance): Collection
    {
        $expensesByProduct = $expenses
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->map(fn (Collection $group): float => (float) $group->where('payment_status', '!=', 'voided')->sum('total_amount'));
        $maintenanceByProduct = $maintenance
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->map(fn (Collection $group): float => (float) $group->sum('cost'));

        return $rentalItems
            ->groupBy('product_id')
            ->map(function (Collection $group, int|string $productId) use ($expensesByProduct, $maintenanceByProduct): array {
                $equipment = $group->first()?->product?->name ?: 'Unknown equipment';
                $revenue = (float) $group->sum('total_price');
                $expenses = (float) ($expensesByProduct[$productId] ?? 0);
                $maintenance = (float) ($maintenanceByProduct[$productId] ?? 0);
                $net = $revenue - $expenses - $maintenance;

                return [
                    'equipment' => $equipment,
                    'rentals' => $group->pluck('rental_id')->unique()->count(),
                    'revenue' => $revenue,
                    'expenses' => $expenses,
                    'maintenance' => $maintenance,
                    'net' => $net,
                    'margin' => $revenue > 0 ? round(($net / $revenue) * 100, 2) : 0.0,
                ];
            })
            ->sortByDesc('net')
            ->take(10)
            ->values();
    }
}

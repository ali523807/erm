<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\MaintenanceLog;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Rental;
use App\Models\RentalItem;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $companyCurrency = auth()->user()->currentCompany?->currency ?? 'USD';

        $equipmentCount = Product::count();
        $availableEquipmentCount = Product::where('status', 'available')->count();
        $onRentEquipmentCount = Product::where('status', 'on_rent')->count();
        $maintenanceEquipmentCount = Product::where('status', 'maintenance')->count();
        $activeRentalStatuses = ['active', 'on_rent', 'checked_out', 'open'];

        return view('home', [
            'summary' => [
                'currency' => $companyCurrency,
                'monthRevenue' => (float) Invoice::whereBetween('invoice_date', [$monthStart, $monthEnd])
                    ->get()
                    ->sum(fn (Invoice $invoice): float => $this->baseInvoiceAmount($invoice, 'total')),
                'monthCollected' => (float) InvoicePayment::with('invoice')
                    ->whereBetween('payment_date', [$monthStart, $monthEnd])
                    ->get()
                    ->sum(fn (InvoicePayment $payment): float => (float) $payment->amount * (float) ($payment->invoice?->exchange_rate ?: 1)),
                'outstandingBalance' => (float) Invoice::get()
                    ->sum(fn (Invoice $invoice): float => $this->baseInvoiceAmount($invoice, 'balance')),
                'activeRentals' => Rental::whereIn('status', $activeRentalStatuses)->count(),
                'dueReturns' => Rental::whereIn('status', $activeRentalStatuses)
                    ->whereNotNull('rental_end_date')
                    ->whereBetween('rental_end_date', [$today, now()->addDays(7)->toDateString()])
                    ->count(),
                'openQuotes' => Quote::whereIn('status', ['draft', 'sent', 'approved'])->count(),
                'pendingMaintenance' => MaintenanceLog::whereIn('status', ['scheduled', 'in_progress'])->count(),
                'customers' => Customer::count(),
            ],
            'fleet' => [
                'total' => $equipmentCount,
                'available' => $availableEquipmentCount,
                'onRent' => $onRentEquipmentCount,
                'maintenance' => $maintenanceEquipmentCount,
                'utilizationRate' => $equipmentCount > 0 ? round(($onRentEquipmentCount / $equipmentCount) * 100) : 0,
            ],
            'recentRentals' => Rental::with('customer')
                ->latest()
                ->limit(5)
                ->get(),
            'recentInvoices' => Invoice::with('customer')
                ->latest('invoice_date')
                ->limit(5)
                ->get(),
            'dueReturns' => Rental::with(['customer', 'rentalItems.product'])
                ->whereIn('status', $activeRentalStatuses)
                ->whereNotNull('rental_end_date')
                ->whereBetween('rental_end_date', [$today, now()->addDays(7)->toDateString()])
                ->orderBy('rental_end_date')
                ->limit(5)
                ->get(),
            'maintenanceAlerts' => MaintenanceLog::with('product')
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->orderByRaw('scheduled_at is null, scheduled_at asc')
                ->limit(5)
                ->get(),
            'subscription' => auth()->user()->currentCompany?->subscription()->with('plan')->first(),
            'rentalItemCount' => RentalItem::count(),
        ]);
    }

    private function baseInvoiceAmount(Invoice $invoice, string $amountType): float
    {
        $baseColumn = $amountType === 'balance' ? 'base_balance_due' : 'base_total_amount';
        $sourceColumn = $amountType === 'balance' ? 'balance_due' : 'total_amount';

        $baseAmount = (float) $invoice->{$baseColumn};

        if ($baseAmount > 0 || (float) $invoice->{$sourceColumn} <= 0) {
            return $baseAmount;
        }

        return round((float) $invoice->{$sourceColumn} * (float) ($invoice->exchange_rate ?: 1), 2);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Rental;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    /**
     * @var array<string, string>
     */
    private array $categories = [
        'fuel' => 'Fuel',
        'transport' => 'Transport',
        'labor' => 'Labor / Crew',
        'cleaning' => 'Cleaning',
        'repair_parts' => 'Repair Parts',
        'insurance' => 'Insurance',
        'permits' => 'Permits',
        'tolls_parking' => 'Tolls & Parking',
        'site_expense' => 'Site Expense',
        'office' => 'Office',
        'other' => 'Other',
    ];

    /**
     * @var array<string, string>
     */
    private array $paymentStatuses = [
        'paid' => 'Paid',
        'unpaid' => 'Unpaid',
        'reimbursable' => 'Reimbursable',
        'voided' => 'Voided',
    ];

    /**
     * @var array<string, string>
     */
    private array $paymentMethods = [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'card' => 'Card',
        'cheque' => 'Cheque',
        'online' => 'Online',
        'company_account' => 'Company Account',
        'other' => 'Other',
    ];

    public function __construct(private ActivityLogger $activity) {}

    public function index(): View
    {
        $expenses = Expense::with(['rental.customer', 'rental.invoice', 'customer', 'product', 'creator', 'invoice'])
            ->latest('expense_date')
            ->latest()
            ->get();

        return view('expenses.index', [
            'expenses' => $expenses,
            'customers' => Customer::orderBy('company_name')->get(),
            'rentals' => Rental::with('customer')->latest()->limit(100)->get(),
            'products' => Product::orderBy('name')->get(),
            'categories' => $this->categories,
            'paymentStatuses' => $this->paymentStatuses,
            'paymentMethods' => $this->paymentMethods,
            'summary' => [
                'total' => (float) $expenses->where('payment_status', '!=', 'voided')->sum('total_amount'),
                'paid' => (float) $expenses->where('payment_status', 'paid')->sum('total_amount'),
                'unpaid' => (float) $expenses->where('payment_status', 'unpaid')->sum('total_amount'),
                'billable' => (float) $expenses->where('is_billable', true)->where('payment_status', '!=', 'voided')->sum('total_amount'),
                'uninvoicedBillable' => (float) $expenses->where('is_billable', true)->where('recovery_status', 'not_invoiced')->sum('total_amount'),
                'recovered' => (float) $expenses->where('recovery_status', 'recovered')->sum('total_amount'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedData($request);
        $validated['expense_number'] = ($validated['expense_number'] ?? null) ?: $this->nextExpenseNumber();
        $validated['created_by'] = auth()->id();
        $validated['total_amount'] = $this->totalAmount($validated);

        $expense = Expense::create($validated);

        $this->activity->log('expenses', 'created', "Recorded expense {$expense->expense_number}.", $expense, [
            'category' => $expense->category,
            'total_amount' => $expense->total_amount,
            'rental_id' => $expense->rental_id,
            'product_id' => $expense->product_id,
        ]);

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Expense recorded successfully.');
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $previousInvoice = $expense->invoice;
        $validated = $this->validatedData($request, $expense);
        $validated['expense_number'] = ($validated['expense_number'] ?? null) ?: $expense->expense_number;
        $validated['total_amount'] = $this->totalAmount($validated);

        $expense->update($validated);
        $expense->invoice?->recalculateTotals();
        if ($previousInvoice && (! $expense->invoice || $previousInvoice->isNot($expense->invoice))) {
            $previousInvoice->recalculateTotals();
        }

        $this->activity->log('expenses', 'updated', "Updated expense {$expense->expense_number}.", $expense, [
            'category' => $expense->category,
            'total_amount' => $expense->total_amount,
        ]);

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Expense updated successfully.');
    }

    public function addToInvoice(Expense $expense): RedirectResponse
    {
        abort_unless($expense->is_billable, 422, 'Only billable expenses can be added to an invoice.');
        abort_if($expense->invoice_id, 422, 'This expense is already linked to an invoice.');
        abort_unless($expense->rental?->invoice, 422, 'Generate an invoice for the linked rental before adding this expense.');

        $invoice = $expense->rental->invoice;
        abort_if(strtoupper($expense->currency) !== strtoupper($invoice->currency), 422, 'Expense currency must match the invoice currency before it can be added.');

        $expense->update([
            'invoice_id' => $invoice->id,
            'customer_id' => $expense->customer_id ?: $invoice->customer_id,
            'recovery_status' => 'invoiced',
            'invoiced_at' => now(),
        ]);

        $invoice->recalculateTotals();

        $this->activity->log('expenses', 'added_to_invoice', "Added expense {$expense->expense_number} to invoice {$invoice->invoice_number}.", $expense, [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'amount' => $expense->total_amount,
        ]);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', "Expense {$expense->expense_number} added to invoice {$invoice->invoice_number}.");
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $invoice = $expense->invoice;
        $expenseNumber = $expense->expense_number;
        $expense->delete();
        $invoice?->recalculateTotals();

        $this->activity->log('expenses', 'deleted', "Deleted expense {$expenseNumber}.", null, [
            'expense_id' => $expense->id,
            'expense_number' => $expenseNumber,
        ]);

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Expense deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Expense $expense = null): array
    {
        $companyId = auth()->user()->current_company_id;

        $validated = $request->validate([
            'expense_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('expenses', 'expense_number')->where('company_id', $companyId)->ignore($expense),
            ],
            'category' => ['required', Rule::in(array_keys($this->categories))],
            'expense_date' => ['required', 'date'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'payment_status' => ['required', Rule::in(array_keys($this->paymentStatuses))],
            'payment_method' => ['nullable', Rule::in(array_keys($this->paymentMethods))],
            'reference' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'amount' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'rental_id' => ['nullable', Rule::exists('rentals', 'id')->where('company_id', $companyId)],
            'customer_id' => ['nullable', Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'product_id' => ['nullable', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'is_billable' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['tax_amount'] = $validated['tax_amount'] ?? 0;
        $validated['is_billable'] = $request->boolean('is_billable');
        $validated['currency'] = strtoupper($validated['currency']);
        if (! $validated['is_billable']) {
            $validated['invoice_id'] = null;
            $validated['recovery_status'] = 'not_invoiced';
            $validated['invoiced_at'] = null;
            $validated['recovered_at'] = null;
        }

        if (! empty($validated['rental_id'])) {
            $rental = Rental::with('rentalItems')->find($validated['rental_id']);

            if ($rental) {
                $validated['customer_id'] = ($validated['customer_id'] ?? null) ?: $rental->customer_id;
            }
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function totalAmount(array $attributes): float
    {
        return round((float) $attributes['amount'] + (float) ($attributes['tax_amount'] ?? 0), 2);
    }

    private function nextExpenseNumber(): string
    {
        $companyId = auth()->user()->current_company_id;
        $next = Expense::withoutGlobalScopes()->where('company_id', $companyId)->count() + 1;

        return 'EXP-'.now()->format('Y').'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}

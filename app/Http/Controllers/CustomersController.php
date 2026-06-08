<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Document;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomersController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status', 'all');

        $customers = Customer::withCount(['quotes', 'rentals', 'invoices'])
            ->withSum('invoices as balance_due_sum', 'balance_due')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('company_name', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->has('rentals'))
            ->when($status === 'balance', fn ($query) => $query->whereHas('invoices', fn ($query) => $query->where('balance_due', '>', 0)))
            ->latest()
            ->get();

        $allCustomers = Customer::withCount(['rentals', 'invoices'])
            ->withSum('invoices as balance_due_sum', 'balance_due')
            ->get();

        return view('customers.index', [
            'customers' => $customers,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'summary' => [
                'total' => $allCustomers->count(),
                'active' => $allCustomers->where('rentals_count', '>', 0)->count(),
                'withBalance' => $allCustomers->filter(fn (Customer $customer): bool => (float) $customer->balance_due_sum > 0)->count(),
                'balanceDue' => (float) $allCustomers->sum('balance_due_sum'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('customers.create', [
            'customer' => new Customer,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = Customer::create($this->validatedData($request));

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer): View
    {
        $customer->load([
            'quotes' => fn ($query) => $query->latest()->limit(10),
            'rentals.rentalItems.product',
            'invoices.payments',
            'portalUsers',
        ]);

        $documents = Document::where('documentable_type', Customer::class)
            ->where('documentable_id', $customer->id)
            ->latest()
            ->get();

        return view('customers.show', [
            'customer' => $customer,
            'documents' => $documents,
            'summary' => [
                'quotes' => $customer->quotes->count(),
                'rentals' => $customer->rentals->count(),
                'invoices' => $customer->invoices->count(),
                'balanceDue' => (float) $customer->invoices->sum('balance_due'),
            ],
        ]);
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', [
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validatedData($request, $customer));

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->rentals()->exists() || $customer->quotes()->exists() || $customer->invoices()->exists()) {
            return redirect()
                ->route('customers.index')
                ->withErrors('Customers with quotes, rentals, or invoices cannot be deleted.');
        }

        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Customer $customer = null): array
    {
        $companyId = auth()->user()->current_company_id;

        return $request->validate([
            'company_name' => ['required', 'string', 'min:2', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers', 'email')
                    ->where('company_id', $companyId)
                    ->ignore($customer),
            ],
            'address' => ['nullable', 'string'],
            'trade_license_number' => ['nullable', 'string', 'max:255'],
            'vat_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}

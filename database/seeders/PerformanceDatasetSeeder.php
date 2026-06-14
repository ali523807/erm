<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Rental;
use App\Models\RentalItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PerformanceDatasetSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'global-demo-rentals')->firstOrFail();

        DB::transaction(function () use ($company): void {
            $categories = $this->categories($company);
            $customers = $this->customers($company);
            $products = $this->products($company, $categories);

            $this->quotes($company, $customers, $products);
            $rentals = $this->rentals($company, $customers, $products);
            $this->invoices($company, $rentals);
        });
    }

    /**
     * @return array<int, Category>
     */
    private function categories(Company $company): array
    {
        return collect([
            'Performance Generators',
            'Performance Lifts',
            'Performance Vehicles',
            'Performance Cameras',
            'Performance Tools',
            'Performance HVAC',
        ])
            ->map(fn (string $name): Category => Category::firstOrCreate(
                ['company_id' => $company->id, 'name' => $name],
                ['description' => 'Large dataset category for performance testing.'],
            ))
            ->values()
            ->all();
    }

    /**
     * @return array<int, Customer>
     */
    private function customers(Company $company): array
    {
        if (Customer::where('company_id', $company->id)->where('email', 'like', 'perf-customer-%@globaldemo.test')->count() < 1200) {
            collect(range(1, 1200))
                ->map(fn (int $index): array => [
                    'company_id' => $company->id,
                    'company_name' => 'Performance Customer '.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                    'contact_person' => 'Contact '.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                    'email' => 'perf-customer-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT).'@globaldemo.test',
                    'phone' => '+1 555 '.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                    'address' => $index.' Performance Avenue, Houston, US',
                    'vat_number' => 'PERF-TAX-'.$index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->chunk(250)
                ->each(fn ($chunk) => Customer::upsert(
                    $chunk->all(),
                    ['company_id', 'email'],
                    ['company_name', 'contact_person', 'phone', 'address', 'vat_number', 'updated_at'],
                ));
        }

        return Customer::where('company_id', $company->id)
            ->where('email', 'like', 'perf-customer-%@globaldemo.test')
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * @param  array<int, Category>  $categories
     * @return array<int, Product>
     */
    private function products(Company $company, array $categories): array
    {
        if (Product::where('company_id', $company->id)->where('equipment_code', 'like', 'PERF-%')->count() < 1500) {
            $statuses = ['available', 'reserved', 'on_rent', 'maintenance', 'damaged'];

            collect(range(1, 1500))
                ->map(function (int $index) use ($categories, $company, $statuses): array {
                    $category = $categories[($index - 1) % count($categories)];
                    $dailyRate = 75 + ($index % 12) * 25;

                    return [
                        'company_id' => $company->id,
                        'category_id' => $category->id,
                        'name' => 'Performance Equipment '.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                        'description' => 'Generated asset for performance testing.',
                        'equipment_code' => 'PERF-'.str_pad((string) $index, 5, '0', STR_PAD_LEFT),
                        'serial_number' => 'SN-PERF-'.str_pad((string) $index, 5, '0', STR_PAD_LEFT),
                        'status' => $statuses[$index % count($statuses)],
                        'ownership_type' => 'owned',
                        'unit_of_measure' => 'unit',
                        'default_rate_type' => 'daily',
                        'default_rate' => $dailyRate,
                        'hourly_rate' => round($dailyRate / 8, 2),
                        'daily_rate' => $dailyRate,
                        'weekly_rate' => $dailyRate * 5,
                        'monthly_rate' => $dailyRate * 18,
                        'custom_rate' => $dailyRate,
                        'default_deposit_amount' => $dailyRate * 2,
                        'replacement_value' => $dailyRate * 80,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })
                ->chunk(250)
                ->each(fn ($chunk) => Product::upsert(
                    $chunk->all(),
                    ['company_id', 'equipment_code'],
                    [
                        'category_id',
                        'name',
                        'description',
                        'serial_number',
                        'status',
                        'ownership_type',
                        'unit_of_measure',
                        'default_rate_type',
                        'default_rate',
                        'hourly_rate',
                        'daily_rate',
                        'weekly_rate',
                        'monthly_rate',
                        'custom_rate',
                        'default_deposit_amount',
                        'replacement_value',
                        'updated_at',
                    ],
                ));
        }

        return Product::where('company_id', $company->id)
            ->where('equipment_code', 'like', 'PERF-%')
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * @param  array<int, Customer>  $customers
     * @param  array<int, Product>  $products
     */
    private function quotes(Company $company, array $customers, array $products): void
    {
        if (Quote::where('company_id', $company->id)->where('quote_number', 'like', 'Q-PERF-%')->count() >= 500) {
            return;
        }

        foreach (range(1, 500) as $index) {
            $customer = $customers[($index - 1) % count($customers)];
            $product = $products[($index * 3) % count($products)];
            $duration = ($index % 10) + 1;
            $rate = (float) ($product->daily_rate ?: $product->default_rate ?: 100);
            $subtotal = $duration * $rate;
            $tax = round($subtotal * 0.0825, 2);
            $total = $subtotal + $tax;

            $quote = Quote::updateOrCreate(
                ['company_id' => $company->id, 'quote_number' => 'Q-PERF-'.str_pad((string) $index, 5, '0', STR_PAD_LEFT)],
                [
                    'customer_id' => $customer->id,
                    'quote_date' => now()->subDays($index % 60)->toDateString(),
                    'valid_until' => now()->addDays(($index % 30) + 1)->toDateString(),
                    'rental_start_date' => now()->addDays($index % 20)->toDateString(),
                    'rental_end_date' => now()->addDays(($index % 20) + $duration)->toDateString(),
                    'status' => ['draft', 'sent', 'accepted', 'declined'][($index - 1) % 4],
                    'currency' => $company->currency,
                    'base_currency' => $company->currency,
                    'exchange_rate' => 1,
                    'subtotal' => $subtotal,
                    'tax_amount' => $tax,
                    'discount_amount' => 0,
                    'total_amount' => $total,
                    'base_total_amount' => $total,
                    'delivery_location' => 'Performance Site '.$index,
                    'terms' => 'Performance test terms.',
                ],
            );

            QuoteItem::updateOrCreate(
                ['quote_id' => $quote->id, 'product_id' => $product->id],
                [
                    'company_id' => $company->id,
                    'start_date' => $quote->rental_start_date,
                    'end_date' => $quote->rental_end_date,
                    'duration_type' => 'daily',
                    'no_of_duration' => $duration,
                    'rate' => $rate,
                    'deposit_amount' => (float) $product->default_deposit_amount,
                    'line_total' => $subtotal,
                    'notes' => 'Performance quote line.',
                ],
            );
        }
    }

    /**
     * @param  array<int, Customer>  $customers
     * @param  array<int, Product>  $products
     * @return array<int, Rental>
     */
    private function rentals(Company $company, array $customers, array $products): array
    {
        if (Rental::where('company_id', $company->id)->where('delivery_location', 'like', 'Performance Rental Site %')->count() < 700) {
            foreach (range(1, 700) as $index) {
                $customer = $customers[($index * 2) % count($customers)];
                $product = $products[($index * 5) % count($products)];
                $duration = ($index % 14) + 1;
                $rate = (float) ($product->daily_rate ?: $product->default_rate ?: 100);

                $rental = Rental::updateOrCreate(
                    ['company_id' => $company->id, 'delivery_location' => 'Performance Rental Site '.$index],
                    [
                        'customer_id' => $customer->id,
                        'rental_start_date' => now()->subDays($index % 45)->toDateString(),
                        'rental_end_date' => now()->addDays($index % 30)->toDateString(),
                        'delivery_date' => now()->subDays($index % 45)->toDateString(),
                        'pickup_date' => now()->addDays($index % 30)->toDateString(),
                        'status' => ['reserved', 'active', 'returned', 'closed'][($index - 1) % 4],
                        'notes' => 'Performance rental record '.Str::random(8),
                    ],
                );

                RentalItem::updateOrCreate(
                    ['rental_id' => $rental->id, 'product_id' => $product->id],
                    [
                        'company_id' => $company->id,
                        'start_date' => $rental->rental_start_date,
                        'end_date' => $rental->rental_end_date,
                        'duration_type' => 'daily',
                        'no_of_duration' => $duration,
                        'rate_type' => 'daily',
                        'rate' => $rate,
                        'deposit_amount' => (float) $product->default_deposit_amount,
                        'total_days' => $duration,
                        'total_price' => $duration * $rate,
                        'status' => $rental->status === 'active' ? 'on_rent' : 'reserved',
                    ],
                );
            }
        }

        return Rental::where('company_id', $company->id)
            ->where('delivery_location', 'like', 'Performance Rental Site %')
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * @param  array<int, Rental>  $rentals
     */
    private function invoices(Company $company, array $rentals): void
    {
        if (Invoice::where('company_id', $company->id)->where('invoice_number', 'like', 'INV-PERF-%')->count() >= 700) {
            return;
        }

        foreach ($rentals as $index => $rental) {
            $subtotal = (float) RentalItem::where('rental_id', $rental->id)->sum('total_price');
            $tax = round($subtotal * 0.0825, 2);
            $total = $subtotal + $tax;
            $paid = $index % 3 === 0 ? $total : ($index % 3 === 1 ? round($total / 2, 2) : 0);
            $balance = max(0, $total - $paid);

            $invoice = Invoice::updateOrCreate(
                ['company_id' => $company->id, 'invoice_number' => 'INV-PERF-'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT)],
                [
                    'rental_id' => $rental->id,
                    'customer_id' => $rental->customer_id,
                    'invoice_date' => now()->subDays($index % 40)->toDateString(),
                    'due_date' => now()->addDays(($index % 30) - 10)->toDateString(),
                    'status' => $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'issued'),
                    'currency' => $company->currency,
                    'base_currency' => $company->currency,
                    'exchange_rate' => 1,
                    'subtotal' => $subtotal,
                    'tax_amount' => $tax,
                    'discount_amount' => 0,
                    'damage_amount' => 0,
                    'late_fee_amount' => 0,
                    'billable_expense_amount' => 0,
                    'total_amount' => $total,
                    'base_total_amount' => $total,
                    'paid_amount' => $paid,
                    'balance_due' => $balance,
                    'base_balance_due' => $balance,
                    'notes' => 'Performance invoice.',
                ],
            );

            if ($paid > 0) {
                InvoicePayment::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'invoice_id' => $invoice->id,
                        'reference' => 'PERF-PAY-'.$invoice->invoice_number,
                    ],
                    [
                        'payment_date' => $invoice->invoice_date,
                        'amount' => $paid,
                        'method' => $index % 2 === 0 ? 'cash' : 'bank_transfer',
                        'notes' => 'Performance payment.',
                    ],
                );
            }
        }
    }
}

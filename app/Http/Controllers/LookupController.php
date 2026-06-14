<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    public function customers(Request $request): JsonResponse
    {
        $term = trim((string) $request->input('q'));

        $customers = Customer::query()
            ->when($term !== '', function (Builder $query) use ($term): void {
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('company_name', 'like', "%{$term}%")
                        ->orWhere('contact_person', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
            })
            ->orderBy('company_name')
            ->limit(20)
            ->get();

        return $this->selectResponse($customers->map(fn (Customer $customer): array => [
            'id' => $customer->id,
            'text' => collect([$customer->company_name, $customer->contact_person])->filter()->join(' - '),
        ])->all());
    }

    public function products(Request $request): JsonResponse
    {
        $term = trim((string) $request->input('q'));

        $products = Product::with('category')
            ->when($term !== '', function (Builder $query) use ($term): void {
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('equipment_code', 'like', "%{$term}%")
                        ->orWhere('serial_number', 'like', "%{$term}%")
                        ->orWhereHas('category', function (Builder $categoryQuery) use ($term): void {
                            $categoryQuery->where('name', 'like', "%{$term}%");
                        });
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        return $this->selectResponse($products->map(fn (Product $product): array => $this->productOption($product))->all());
    }

    public function rentals(Request $request): JsonResponse
    {
        $term = trim((string) $request->input('q'));

        $rentals = Rental::with('customer')
            ->when($term !== '', function (Builder $query) use ($term): void {
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('id', preg_replace('/\D+/', '', $term) ?: -1)
                        ->orWhere('delivery_location', 'like', "%{$term}%")
                        ->orWhereHas('customer', function (Builder $customerQuery) use ($term): void {
                            $customerQuery->where('company_name', 'like', "%{$term}%")
                                ->orWhere('contact_person', 'like', "%{$term}%");
                        });
                });
            })
            ->latest()
            ->limit(20)
            ->get();

        return $this->selectResponse($rentals->map(fn (Rental $rental): array => [
            'id' => $rental->id,
            'text' => 'RTN-'.$rental->id.' - '.($rental->customer?->company_name ?? 'Unknown customer').' - '.str($rental->status ?? 'draft')->headline(),
        ])->all());
    }

    public function teamMembers(Request $request): JsonResponse
    {
        $term = trim((string) $request->input('q'));
        $companyId = $request->user()->current_company_id;

        $members = User::whereHas('companies', fn (Builder $query) => $query->where('companies.id', $companyId))
            ->when($term !== '', function (Builder $query) use ($term): void {
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        return $this->selectResponse($members->map(fn (User $user): array => [
            'id' => $user->id,
            'text' => $user->name.' - '.$user->email,
        ])->all());
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     */
    private function selectResponse(array $results): JsonResponse
    {
        return response()->json([
            'results' => $results,
            'pagination' => ['more' => false],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function productOption(Product $product): array
    {
        return [
            'id' => $product->id,
            'text' => collect([
                $product->equipment_code,
                $product->name,
                $product->category?->name,
                str($product->status ?? 'available')->headline(),
            ])->filter()->join(' - '),
            'name' => $product->name,
            'code' => $product->equipment_code,
            'status' => $product->status,
            'rate' => (float) $product->default_rate,
            'rateType' => $product->default_rate_type,
            'deposit' => (float) $product->default_deposit_amount,
            'rates' => [
                'hourly' => (float) $product->hourly_rate,
                'daily' => (float) $product->daily_rate,
                'weekly' => (float) $product->weekly_rate,
                'monthly' => (float) $product->monthly_rate,
                'custom' => (float) $product->custom_rate,
            ],
        ];
    }
}

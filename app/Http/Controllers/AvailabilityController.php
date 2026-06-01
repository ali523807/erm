<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\EquipmentAvailabilityService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AvailabilityController extends Controller
{
    public function __invoke(Request $request, EquipmentAvailabilityService $availability): View
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'category_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string'],
        ]);

        $startDate = $validated['start_date'] ?? now()->toDateString();
        $endDate = $validated['end_date'] ?? Carbon::parse($startDate)->addDays(7)->toDateString();

        $products = Product::with(['category', 'branch', 'warehouse', 'storageLocation'])
            ->when($validated['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('name')
            ->get();

        return view('availability.index', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'rows' => $availability->matrix($products, $startDate, $endDate),
            'categories' => Product::with('category')->get()->pluck('category')->filter()->unique('id')->sortBy('name')->values(),
            'statuses' => ['available', 'reserved', 'on_rent', 'maintenance', 'damaged', 'retired', 'lost'],
        ]);
    }
}

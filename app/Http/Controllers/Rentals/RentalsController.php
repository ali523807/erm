<?php

namespace App\Http\Controllers\Rentals;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Rental;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class RentalsController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $rentals = Rental::query();
            $rentals = $rentals->with('customer');

            $sortCol = null;
            $sortDir = null;

            if($request->has('order') && $request->get('order')) {
                $sortCol = $request->get('order')[0]['name'];
                $sortDir = $request->get('order')[0]['dir'];

                if($sortCol == 'DT_RowIndex') {
                    $sortCol = null;
                    $sortDir = null;
                }
            }

            if($sortCol) {
                $rentals = $rentals->orderBy($sortCol, $sortDir ?? 'asc');
            }


            $filterCount = $rentals->clone()->count();
            $totalCount = Rental::count();

            $rentals = $rentals->skip($request->start ?? 0)
                ->take($request->length ?? 10);

            $rentals = $rentals->get();

            return DataTables::of($rentals)
                ->with([
                    "recordsTotal" => $totalCount,
                    "recordsFiltered" => $filterCount,
                ])
                ->skipPaging()
                ->addIndexColumn()
                ->addColumn('rental_id', function ($row) {
                    return 'RTN-'.$row->id;
                })
                ->addColumn('action', function ($row) {
                    return view('rentals._actions', ['rental' => $row])->render();
                })
                ->rawColumns(['rental_id','action'])
                ->make(true);
        }
        $customers = Customer::all();
        $equipments = Product::all();
        return view('rentals.index',compact('customers','equipments'));
    }

    public function storeOrUpdate(Request $request)
    {
        // Step 1: Validate the main rental and item data
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'rental_start_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:rental_start_date',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.start_date' => 'required|date',
            'items.*.end_date' => 'required|date|after_or_equal:items.*.start_date',
            'items.*.duration_type' => 'required|in:days,weeks,months',
            'items.*.no_of_duration' => 'required|numeric|min:1',
        ]);

        // Step 2: Clean up items to exclude empty ones
        $items = collect($request->input('items', []))
            ->filter(fn($item) => is_array($item) && !empty($item['product_id']))
            ->values()
            ->all();

        $data = $request->except('items');

        // Step 3: Create or update the rental record
        if ($request->id) {
            $rental = Rental::findOrFail($request->id);
            $rental->update($data);
        } else {
            $rental = Rental::create($data);
        }

        // Step 4: Handle rental items
        if (!empty($items)) {
            $rental->rentalItems()->delete(); // optional: clear existing items
            foreach ($items as $item) {
                $rental->rentalItems()->create($item);
            }
        }

        return response()->json(['message' => 'Rental saved successfully.']);
    }


    public function edit(Request $request, Rental $rental)
    {
        $rental->load('rentalItems'); // assuming relation name is `rentalItems`
        return response()->json($rental);
    }

    public function destroy(Request $request, Rental $rental)
    {
        $rental->delete();

        return response()->json(['message' => 'Rental Deleted Successfully!']);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Rental;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class RentalsController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $rentals = Rental::query();

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
                ->addColumn('action', function ($row) {
                    return view('rentals._actions', ['rental' => $row])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        $customers = Customer::all();
        return view('rentals.index',compact('customers'));
    }

    public function storeOrUpdate(Request $request)
    {
        $request->validate([
            'company_name' => 'required|min:2'
        ]);
        if ($request->id) {
            Rental::find($request->id)->update($request->all());
        } else {
            Rental::create($request->all());
        }

        return response()->json(['message' => 'Rental Created Successfully!']);

    }

    public function edit(Request $request, Rental $rental)
    {
        return response()->json($rental);
    }

    public function destroy(Request $request, Rental $rental)
    {
        $rental->delete();

        return response()->json(['message' => 'Rental Deleted Successfully!']);
    }
}

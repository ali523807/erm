<?php

namespace App\Http\Controllers\Rentals;

use App\Http\Controllers\Controller;
use App\Models\RentalItem;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class RentalItemsController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $rentalItems = RentalItem::query();
            $rentalItems = $rentalItems->with('rental','product');

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
                $rentalItems = $rentalItems->orderBy($sortCol, $sortDir ?? 'asc');
            }


            $filterCount = $rentalItems->clone()->count();
            $totalCount = RentalItem::count();

            $rentalItems = $rentalItems->skip($request->start ?? 0)
                ->take($request->length ?? 10);

            $rentalItems = $rentalItems->get();

            return DataTables::of($rentalItems)
                ->with([
                    "recordsTotal" => $totalCount,
                    "recordsFiltered" => $filterCount,
                ])
                ->skipPaging()
                ->addIndexColumn()
                ->addColumn('rental_id', function ($row) {
                    return 'RTN-'.$row->id;
                })
                ->addColumn('customer', function ($row) {
                    return $row->rental->customer->company_name;
                })
                ->addColumn('duration', function ($row) {
                    $duration = floatval($row->no_of_duration);
                    $formatted = $duration == intval($duration) ? intval($duration) : rtrim(rtrim(number_format($duration, 2, '.', ''), '0'), '.');
                    return $formatted . ' ' . ucfirst($row->duration_type);
                })
                ->addColumn('status', function ($row) {
                    return view('rentals.rental-items._status', ['item' => $row])->render();
                })
                ->rawColumns(['rental_id','customer','duration','status'])
                ->make(true);
        }

        return view('rentals.rental-items.index');
    }

}

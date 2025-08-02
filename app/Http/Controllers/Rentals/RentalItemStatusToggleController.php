<?php

namespace App\Http\Controllers\Rentals;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\RentalItem;
use Illuminate\Http\Request;

class RentalItemStatusToggleController extends Controller
{
    public function __invoke(Request $request, RentalItem $item)
    {
        $request->validate([
            'newStatus' => 'required|string|in:Pending,Dispatched,On Rent,Returned,Damaged,Missing,Under Maintenance',
        ]);
        $item->update(['status' => $request->newStatus]);

        return response()->json(['message' => 'Status updated successfully!']);
    }

}

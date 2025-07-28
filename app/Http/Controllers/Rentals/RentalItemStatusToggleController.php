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
        $item->update(['active' => $request->status]);

        return response()->json(['message' => 'Status updated']);
    }

}

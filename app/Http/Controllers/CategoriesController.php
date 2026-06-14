<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CategoriesController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::eloquent(Category::query()->latest())
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('categories._actions', ['category' => $row])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('categories.index');
    }

    public function storeOrUpdate(Request $request)
    {
        $request->validate([
            'name' => 'required|min:2',
        ]);
        if ($request->id) {
            Category::find($request->id)->update($request->all());
        } else {
            Category::create($request->all());
        }

        return response()->json(['message' => 'Category Created Successfully!']);

    }

    public function edit(Request $request, Category $category)
    {
        return response()->json($category);
    }

    public function destroy(Request $request, Category $category)
    {
        $category->delete();

        return response()->json(['message' => 'Category Deleted Successfully!']);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FaqCategory;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class FaqCategoryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of(FaqCategory::withCount('faqs')->orderBy('sort_order')->get())
                ->addIndexColumn()
                ->addColumn('count', fn ($r) => $r->faqs_count)
                ->addColumn('status', fn ($r) => '<div class="form-check form-switch"><input type="checkbox" class="form-check-input toggle-status" data-id="'.$r->id.'" '.($r->status ? 'checked' : '').'></div>')
                ->addColumn('action', fn ($r) => '<button class="btn btn-sm btn-soft-secondary editBtn" data-id="'.$r->id.'"><i class="ri-pencil-fill"></i> Edit</button> <button class="btn btn-sm btn-soft-danger deleteBtn" data-delete-url="'.route('faq-categories.delete', $r->id).'" data-method="DELETE" data-table="#catTable"><i class="ri-delete-bin-fill"></i></button>')
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('admin.faq-categories.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:faq_categories,name',
        ]);
        $slug = $this->uniqueSlug($request->name, FaqCategory::class);
        FaqCategory::create([
            'name' => $request->name, 'slug' => $slug,
            'sort_order' => (int) (FaqCategory::max('sort_order') ?? 0) + 1,
        ]);

        return response()->json(['message' => 'FAQ category added']);
    }

    public function edit($id)
    {
        return response()->json(FaqCategory::findOrFail($id));
    }

    public function update(Request $request)
    {
        $cat = FaqCategory::findOrFail($request->id);
        $request->validate([
            'name' => 'required|string|max:255|unique:faq_categories,name,'.$cat->id,
        ]);
        // Slug always follows the latest name.
        $cat->slug = $this->uniqueSlug($request->name, FaqCategory::class, $cat->id);
        $cat->name = $request->name;
        $cat->save();

        return response()->json(['message' => 'FAQ category updated']);
    }

    public function destroy($id)
    {
        FaqCategory::findOrFail($id)->delete();

        return response()->json(['message' => 'FAQ category deleted']);
    }

    public function toggleStatus(Request $request)
    {
        $c = FaqCategory::findOrFail($request->id);
        $c->update(['status' => ! $c->status]);

        return response()->json(['message' => 'Status updated']);
    }
}

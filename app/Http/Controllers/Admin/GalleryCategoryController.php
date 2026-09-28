<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryCategory;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class GalleryCategoryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of(GalleryCategory::withCount('galleries')->orderBy('sort_order')->get())
                ->addIndexColumn()
                ->addColumn('count', fn ($r) => $r->galleries_count)
                ->addColumn('status', fn ($r) => '<div class="form-check form-switch"><input type="checkbox" class="form-check-input toggle-status" data-id="'.$r->id.'" '.($r->status ? 'checked' : '').'></div>')
                ->addColumn('action', fn ($r) => '<button class="btn btn-sm btn-soft-secondary editBtn" data-id="'.$r->id.'"><i class="ri-pencil-fill"></i> Edit</button> <button class="btn btn-sm btn-soft-danger deleteBtn" data-delete-url="'.route('gallery-categories.delete', $r->id).'" data-method="DELETE" data-table="#catTable"><i class="ri-delete-bin-fill"></i></button>')
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('admin.gallery-categories.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:gallery_categories,name',
        ]);
        $slug = $this->uniqueSlug($request->name, GalleryCategory::class);
        GalleryCategory::create([
            'name' => $request->name, 'slug' => $slug,
            'sort_order' => (int) (GalleryCategory::max('sort_order') ?? 0) + 1,
        ]);

        return response()->json(['message' => 'Gallery category added']);
    }

    public function edit($id)
    {
        return response()->json(GalleryCategory::findOrFail($id));
    }

    public function update(Request $request)
    {
        $cat = GalleryCategory::findOrFail($request->id);
        $request->validate([
            'name' => 'required|string|max:255|unique:gallery_categories,name,'.$cat->id,
        ]);
        // Slug always follows the latest name.
        $cat->slug = $this->uniqueSlug($request->name, GalleryCategory::class, $cat->id);
        $cat->name = $request->name;
        $cat->save();

        return response()->json(['message' => 'Gallery category updated']);
    }

    public function destroy($id)
    {
        GalleryCategory::findOrFail($id)->delete();

        return response()->json(['message' => 'Gallery category deleted']);
    }

    public function toggleStatus(Request $request)
    {
        $c = GalleryCategory::findOrFail($request->id);
        $c->update(['status' => ! $c->status]);

        return response()->json(['message' => 'Status updated']);
    }
}

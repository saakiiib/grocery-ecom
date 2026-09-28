<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\GalleryCategory;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use Yajra\DataTables\Facades\DataTables;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $q = Gallery::with('category:id,name')->orderBy('sort_order');
            if ($request->filled('gallery_category_id')) {
                $q->where('gallery_category_id', $request->gallery_category_id);
            }

            return DataTables::of($q->get())
                ->addIndexColumn()
                ->addColumn('image', fn ($r) => '<img src="'.url($r->image).'" class="img-thumbnail" style="max-width:100px;">')
                ->addColumn('category', fn ($r) => $r->category?->name ?? '-')
                ->addColumn('status', fn ($r) => '<div class="form-check form-switch"><input type="checkbox" class="form-check-input toggle-status" data-id="'.$r->id.'" '.($r->status ? 'checked' : '').'></div>')
                ->addColumn('action', fn ($r) => '<button class="btn btn-sm btn-soft-secondary editBtn" data-id="'.$r->id.'"><i class="ri-pencil-fill"></i> Edit</button> <button class="btn btn-sm btn-soft-danger deleteBtn" data-delete-url="'.route('galleries.delete', $r->id).'" data-method="DELETE" data-table="#galleryTable"><i class="ri-delete-bin-fill"></i></button>')
                ->rawColumns(['image', 'status', 'action'])
                ->make(true);
        }

        $categories = GalleryCategory::where('status', 1)->orderBy('sort_order')->get(['id', 'name']);

        return view('admin.galleries.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'gallery_category_id' => 'nullable|exists:gallery_categories,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);
        $path = public_path('uploads/gallery/');
        if (! file_exists($path)) {
            mkdir($path, 0755, true);
        }
        $name = mt_rand(10000000, 99999999).'.webp';
        Image::make($request->file('image'))->resize(1600, null, function ($c) {
            $c->aspectRatio();
            $c->upsize();
        })->encode('webp', 75)->save($path.$name);

        Gallery::create([
            'gallery_category_id' => $request->gallery_category_id,
            'image' => '/uploads/gallery/'.$name,
            'caption' => $request->caption,
            'sort_order' => (int) (Gallery::max('sort_order') ?? 0) + 1,
        ]);

        return response()->json(['message' => 'Gallery image added']);
    }

    public function edit($id)
    {
        $g = Gallery::findOrFail($id);

        return response()->json([...$g->toArray(), 'preview' => url($g->image)]);
    }

    public function update(Request $request)
    {
        $g = Gallery::findOrFail($request->id);
        $request->validate(['gallery_category_id' => 'nullable|exists:gallery_categories,id']);
        $data = $request->only(['gallery_category_id', 'caption']);
        if ($request->hasFile('image')) {
            if ($g->image && file_exists(public_path($g->image))) {
                @unlink(public_path($g->image));
            }
            $path = public_path('uploads/gallery/');
            if (! file_exists($path)) {
                mkdir($path, 0755, true);
            }
            $name = mt_rand(10000000, 99999999).'.webp';
            Image::make($request->file('image'))->resize(1600, null, fn ($c) => $c->aspectRatio())->encode('webp', 75)->save($path.$name);
            $data['image'] = '/uploads/gallery/'.$name;
        } elseif ($request->boolean('remove_image')) {
            if ($g->image && file_exists(public_path($g->image))) {
                @unlink(public_path($g->image));
            }
            $data['image'] = null;
        }
        $g->update($data);

        return response()->json(['message' => 'Gallery image updated']);
    }

    public function destroy($id)
    {
        $g = Gallery::findOrFail($id);
        if ($g->image && file_exists(public_path($g->image))) {
            @unlink(public_path($g->image));
        }
        $g->delete();

        return response()->json(['message' => 'Gallery image deleted']);
    }

    public function toggleStatus(Request $request)
    {
        $g = Gallery::findOrFail($request->id);
        $g->update(['status' => ! $g->status]);

        return response()->json(['message' => 'Status updated']);
    }
}

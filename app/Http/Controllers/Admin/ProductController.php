<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Product::with('category:id,name')
                ->select(['id', 'category_id', 'name', 'slug', 'model_code', 'hero_image', 'base_price', 'is_featured', 'status', 'sort_order'])
                ->orderBy('sort_order')
                ->orderByDesc('id');

            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('image', fn ($row) => $row->hero_image
                    ? '<img src="'.url($row->hero_image).'" class="img-thumbnail" style="max-width:80px;">'
                    : '<span class="text-muted">-</span>')
                ->addColumn('category', fn ($row) => $row->category?->name ?? '<span class="text-muted">-</span>')
                ->addColumn('price', fn ($row) => $row->base_price ? '£'.number_format((float) $row->base_price) : '<span class="text-muted">On request</span>')
                ->addColumn('featured', function ($row) {
                    $checked = $row->is_featured ? 'checked' : '';

                    return '<div class="form-check form-switch" dir="ltr"><input type="checkbox" class="form-check-input toggle-featured" data-id="'.$row->id.'" '.$checked.'></div>';
                })
                ->addColumn('status', function ($row) {
                    $checked = $row->status ? 'checked' : '';

                    return '<div class="form-check form-switch" dir="ltr"><input type="checkbox" class="form-check-input toggle-status" data-id="'.$row->id.'" '.$checked.'></div>';
                })
                ->addColumn('action', function ($row) {
                    return '
                        <div class="dropdown">
                            <button class="btn btn-soft-secondary btn-sm" type="button" data-bs-toggle="dropdown"><i class="ri-more-fill align-middle"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="'.route('products.manage', $row->id).'"><i class="ri-settings-3-line align-bottom me-2 text-muted"></i> Manage Details</a></li>
                                <li><button class="dropdown-item editBtn" data-id="'.$row->id.'"><i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Quick Edit</button></li>
                                <li class="dropdown-divider"></li>
                                <li><button class="dropdown-item deleteBtn" data-delete-url="'.route('products.delete', $row->id).'" data-method="DELETE" data-table="#productTable"><i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> Delete</button></li>
                            </ul>
                        </div>';
                })
                ->rawColumns(['image', 'category', 'price', 'featured', 'status', 'action'])
                ->make(true);
        }

        $categories = Category::where('status', 1)->orderBy('sort_order')->get(['id', 'name']);

        return view('admin.products.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'model_code' => 'required|string|max:100|unique:products,model_code',
            'category_id' => 'nullable|exists:categories,id',
            'base_price' => 'nullable|numeric|min:0',
            'hero_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'meta_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'model_3d' => 'nullable|file|max:51200',
        ]);

        $product = new Product($request->only([
            'category_id', 'name', 'tagline', 'description', 'dimensions',
            'lead_time', 'warranty', 'base_price', 'video_url',
            'meta_title', 'meta_description', 'meta_keywords',
        ]));
        $product->slug = $this->uniqueSlug($request->name.'-'.$request->model_code, Product::class);
        $product->model_code = $request->model_code;
        $product->show_3d = $request->boolean('show_3d', true);
        $product->is_featured = $request->boolean('is_featured');
        $product->status = true;
        $product->sort_order = (int) (Product::max('sort_order') ?? 0) + 1;

        if ($request->hasFile('hero_image')) {
            $product->hero_image = $this->storeWebp($request->file('hero_image'), 'uploads/products/', 1600, 75);
        }
        if ($request->hasFile('meta_image')) {
            $product->meta_image = $this->storeWebp($request->file('meta_image'), 'uploads/products/', 1200, 80);
        }
        if ($request->hasFile('model_3d')) {
            $product->model_3d = $this->storeModel3d($request->file('model_3d'));
        }

        $product->save();

        return response()->json(['message' => 'Product created successfully', 'id' => $product->id]);
    }

    public function edit($id)
    {
        return response()->json(Product::findOrFail($id));
    }

    public function update(Request $request)
    {
        $product = Product::findOrFail($request->codeid);
        $request->validate([
            'name' => 'required|string|max:255',
            'model_code' => 'required|string|max:100|unique:products,model_code,'.$product->id,
            'category_id' => 'nullable|exists:categories,id',
            'base_price' => 'nullable|numeric|min:0',
            'hero_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'meta_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'model_3d' => 'nullable|file|max:51200',
        ]);

        $product->fill($request->only([
            'category_id', 'name', 'tagline', 'description', 'dimensions',
            'lead_time', 'warranty', 'base_price', 'video_url',
            'meta_title', 'meta_description', 'meta_keywords',
        ]));
        $product->model_code = $request->model_code;
        // Slug follows the latest name + code.
        $product->slug = $this->uniqueSlug($request->name.'-'.$request->model_code, Product::class, $product->id);
        if ($request->has('show_3d')) {
            $product->show_3d = $request->boolean('show_3d');
        }
        if ($request->has('is_featured')) {
            $product->is_featured = $request->boolean('is_featured');
        }

        if ($request->hasFile('hero_image')) {
            $this->deleteFile($product->hero_image);
            $product->hero_image = $this->storeWebp($request->file('hero_image'), 'uploads/products/', 1600, 75);
        } elseif ($request->boolean('remove_hero_image')) {
            $this->deleteFile($product->hero_image);
            $product->hero_image = null;
        }
        if ($request->hasFile('meta_image')) {
            $this->deleteFile($product->meta_image);
            $product->meta_image = $this->storeWebp($request->file('meta_image'), 'uploads/products/', 1200, 80);
        } elseif ($request->boolean('remove_meta_image')) {
            $this->deleteFile($product->meta_image);
            $product->meta_image = null;
        }
        if ($request->hasFile('model_3d')) {
            $this->deleteFile($product->model_3d);
            $product->model_3d = $this->storeModel3d($request->file('model_3d'));
        } elseif ($request->boolean('remove_model_3d')) {
            $this->deleteFile($product->model_3d);
            $product->model_3d = null;
        }

        $product->save();

        return response()->json(['message' => 'Product updated successfully']);
    }

    /** Full workspace with tabs (Basic | Images | SEO). */
    public function manage($id)
    {
        $product = Product::with(['category', 'images'])
            ->findOrFail($id);
        $categories = Category::where('status', 1)->orderBy('sort_order')->get(['id', 'name']);

        return view('admin.products.manage', compact('product', 'categories'));
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $this->deleteFile($product->hero_image);
        $this->deleteFile($product->meta_image);
        $this->deleteFile($product->model_3d);
        foreach ($product->images as $img) {
            $this->deleteFile($img->image);
        }
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function toggleStatus(Request $request)
    {
        $product = Product::findOrFail($request->id);
        $product->update(['status' => ! $product->status]);

        return response()->json(['message' => 'Status updated successfully']);
    }

    public function toggleFeatured(Request $request)
    {
        $product = Product::findOrFail($request->id);
        $product->update(['is_featured' => ! $product->is_featured]);

        return response()->json(['message' => 'Featured flag updated successfully']);
    }

    public function sortList()
    {
        return response()->json(
            Product::select(['id', 'name', 'model_code', 'hero_image', 'sort_order'])
                ->orderBy('sort_order')->orderByDesc('id')->get()
                ->map(fn ($p) => [...$p->toArray(), 'image' => $p->hero_image ? url($p->hero_image) : null])
        );
    }

    public function sortUpdate(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        foreach ($request->ids as $index => $id) {
            Product::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['message' => 'Sort order updated successfully']);
    }

    /** Store an uploaded .glb/.gltf model as-is (no image conversion). */
    private function storeModel3d($file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['glb', 'gltf'])) {
            abort(422, '3D model must be a .glb or .gltf file.');
        }
        $dir = public_path('uploads/products/3d/');
        if (! file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = mt_rand(10000000, 99999999).'.'.$ext;
        $file->move($dir, $name);

        return '/uploads/products/3d/'.$name;
    }

    private function storeWebp($file, string $dir, int $width, int $quality): string
    {
        $path = public_path($dir);
        if (! file_exists($path)) {
            mkdir($path, 0755, true);
        }
        $name = mt_rand(10000000, 99999999).'.webp';
        Image::make($file)->resize($width, null, function ($c) {
            $c->aspectRatio();
            $c->upsize();
        })->encode('webp', $quality)->save($path.$name);

        return '/'.$dir.$name;
    }

    private function deleteFile(?string $path): void
    {
        if ($path && $path !== 'placeholder.webp' && file_exists(public_path($path))) {
            @unlink(public_path($path));
        }
    }
}

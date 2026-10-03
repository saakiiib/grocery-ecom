<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Allergen;
use App\Models\Category;
use App\Models\OptionGroup;
use App\Models\Product;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Product::with(['category:id,name', 'variants'])
                ->select(['id', 'category_id', 'name', 'slug', 'hero_image', 'is_featured', 'status', 'sort_order'])
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
                ->addColumn('sku', fn ($row) => $row->defaultVariant()?->sku ?? '<span class="text-muted">-</span>')
                ->addColumn('price', fn ($row) => $row->priceRange() ?? '<span class="text-muted">No variants</span>')
                ->addColumn('stock', function ($row) {
                    $total = $row->variants->count();
                    if ($total === 0) {
                        return '<span class="text-muted">-</span>';
                    }
                    $out = $row->variants->where('in_stock', false)->count();

                    return $out === 0
                        ? '<span class="badge bg-success">In stock</span>'
                        : '<span class="badge bg-warning text-dark">'.$out.' of '.$total.' out</span>';
                })
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
                ->rawColumns(['image', 'category', 'sku', 'price', 'stock', 'featured', 'status', 'action'])
                ->make(true);
        }

        $categories = Category::where('status', 1)->orderBy('sort_order')->get(['id', 'name']);

        return view('admin.products.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'hero_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'meta_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'category_id.required' => 'Category is required — every product must belong to a category',
        ]);

        $product = new Product($request->only([
            'category_id', 'name', 'tagline', 'highlights', 'description',
            'meta_title', 'meta_description', 'meta_keywords',
        ]));
        $product->slug = $this->uniqueSlug($request->name, Product::class);
        $product->is_featured = $request->boolean('is_featured');
        $product->status = true;
        $product->sort_order = (int) (Product::max('sort_order') ?? 0) + 1;

        if ($request->hasFile('hero_image')) {
            $product->hero_image = $this->storeWebp($request->file('hero_image'), 'uploads/products/', 1600, 75);
        }
        if ($request->hasFile('meta_image')) {
            $product->meta_image = $this->storeOriginal($request->file('meta_image'), 'uploads/products/', 1200);
        }

        $product->save();

        // Invariant: every product has at least one (default) variant.
        $product->variants()->create([
            'mrp' => 0,
            'is_default' => true,
            'in_stock' => true,
            'status' => true,
            'sort_order' => 0,
        ]);

        return response()->json(['message' => 'Product created successfully. Set its prices in the Variants tab.', 'id' => $product->id]);
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
            'category_id' => 'required|exists:categories,id',
            'hero_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'meta_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'origin_country' => 'nullable|string|max:100',
            'nutrition_per' => 'nullable|string|max:50',
            'energy_kcal' => 'nullable|numeric|min:0',
            'fat_g' => 'nullable|numeric|min:0',
            'saturates_g' => 'nullable|numeric|min:0',
            'carbs_g' => 'nullable|numeric|min:0',
            'sugars_g' => 'nullable|numeric|min:0',
            'fibre_g' => 'nullable|numeric|min:0',
            'protein_g' => 'nullable|numeric|min:0',
            'salt_g' => 'nullable|numeric|min:0',
            'allergens' => 'nullable|array',
            'allergens.*' => 'integer|exists:allergens,id',
        ], [
            'category_id.required' => 'Category is required — every product must belong to a category',
        ]);

        $product->fill($request->only([
            'category_id', 'name', 'tagline', 'highlights', 'description',
            'meta_title', 'meta_description', 'meta_keywords',
            'origin_country', 'nutrition_per',
            'energy_kcal', 'fat_g', 'saturates_g', 'carbs_g',
            'sugars_g', 'fibre_g', 'protein_g', 'salt_g',
        ]));
        foreach (['is_vegetarian', 'is_vegan', 'is_halal', 'is_organic', 'is_gluten_free'] as $flag) {
            $product->$flag = $request->boolean($flag);
        }
        // Slug follows the latest name.
        $product->slug = $this->uniqueSlug($request->name, Product::class, $product->id);
        if ($request->has('is_featured')) {
            $product->is_featured = $request->boolean('is_featured');
        }

        if ($request->hasFile('hero_image')) {
            $this->deleteFile($product->hero_image);
            $product->hero_image = $this->storeWebp($request->file('hero_image'), 'uploads/products/', 1600, 75);
        }
        if ($request->hasFile('meta_image')) {
            $this->deleteFile($product->meta_image);
            $product->meta_image = $this->storeOriginal($request->file('meta_image'), 'uploads/products/', 1200);
        }

        $product->save();

        $product->allergens()->sync($request->input('allergens', []));

        return response()->json(['message' => 'Product updated successfully']);
    }

    /** Full workspace with tabs (Basic | Images | Variants | SEO is inside Basic). */
    public function manage($id)
    {
        $product = Product::with(['category', 'images', 'extraAttributes', 'variants.values.group', 'optionGroups.values', 'category.optionGroups.values'])
            ->findOrFail($id);
        $categories = Category::where('status', 1)->orderBy('sort_order')->get(['id', 'name']);
        $allGroups = OptionGroup::with('values')->where('status', true)->orderBy('sort_order')->get();
        $allergens = Allergen::orderBy('sort_order')->get(['id', 'name']);

        return view('admin.products.manage', compact('product', 'categories', 'allGroups', 'allergens'));
    }

    /** Replace-all sync of the free-form extra details rows. */
    public function attributesSync(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $request->validate([
            'attributes' => 'nullable|array',
            'attributes.*.label' => 'required|string|max:100',
            'attributes.*.value' => 'required|string',
        ]);

        $product->extraAttributes()->delete();
        foreach (array_values($request->attributes ?? []) as $i => $row) {
            $product->extraAttributes()->create([
                'label' => $row['label'],
                'value' => $row['value'],
                'sort_order' => $i,
            ]);
        }

        return response()->json(['message' => 'Extra details saved']);
    }

    /** Remove a single file (hero_image | meta_image) via dedicated route. */
    public function removeFile(Request $request, $id)
    {
        $request->validate([
            'field' => 'required|in:hero_image,meta_image',
        ]);

        $product = Product::findOrFail($id);
        $field = $request->field;
        $this->deleteFile($product->{$field});
        $product->{$field} = null;
        $product->save();

        return response()->json(['message' => 'File removed successfully']);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $this->deleteFile($product->hero_image);
        $this->deleteFile($product->meta_image);
        foreach ($product->images as $img) {
            $this->deleteFile($img->image);
        }
        foreach ($product->variants as $variant) {
            $this->deleteFile($variant->image);
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
            Product::with('variants')
                ->select(['id', 'name', 'hero_image', 'sort_order'])
                ->orderBy('sort_order')->orderByDesc('id')->get()
                ->map(fn ($p) => [...$p->toArray(), 'image' => $p->hero_image ? url($p->hero_image) : null, 'sku' => $p->defaultVariant()?->sku])
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

    /** Store a meta/OG image in its original format (jpeg/png) — never webp. */
    private function storeOriginal($file, string $dir, int $width): string
    {
        $path = public_path($dir);
        if (! file_exists($path)) {
            mkdir($path, 0755, true);
        }
        $ext = strtolower($file->getClientOriginalExtension());
        $ext = in_array($ext, ['jpg', 'jpeg', 'png']) ? $ext : 'jpg';
        $name = mt_rand(10000000, 99999999).'.'.$ext;
        Image::make($file)->resize($width, null, function ($c) {
            $c->aspectRatio();
            $c->upsize();
        })->save($path.$name, 85);

        return '/'.$dir.$name;
    }

    private function deleteFile(?string $path): void
    {
        if ($path && $path !== 'placeholder.webp' && file_exists(public_path($path))) {
            @unlink(public_path($path));
        }
    }
}

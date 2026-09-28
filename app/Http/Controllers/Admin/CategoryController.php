<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use DataTables;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Category::with('parent')
                ->select(['id', 'name', 'slug', 'image', 'parent_id', 'status', 'sort_order'])
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'desc');

            if ($request->type === 'parent') {
                $query->whereNull('parent_id');
            } elseif ($request->type === 'child') {
                $query->whereNotNull('parent_id');
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('image', function ($row) {
                    return '<img src="'.url($row->image).'" class="img-thumbnail" style="max-width: 80px;">';
                })
                ->addColumn('parent_category', function ($row) {
                    return $row->parent ? $row->parent->name : '<span class="text-muted">'.'None'.'</span>';
                })
                ->addColumn('status', function ($row) {
                    $checked = $row->status == 1 ? 'checked' : '';

                    return '<div class="form-check form-switch" dir="ltr">
                                <input type="checkbox" class="form-check-input toggle-status" 
                                      id="customSwitchStatus'.$row->id.'" data-id="'.$row->id.'" '.$checked.'>
                                <label class="form-check-label" for="customSwitchStatus'.$row->id.'"></label>
                            </div>';
                })
                ->addColumn('action', function ($row) {
                    return '
                        <div class="dropdown">
                            <button class="btn btn-soft-secondary btn-sm dropdown" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ri-more-fill align-middle"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button class="dropdown-item" id="EditBtn" rid="'.$row->id.'">
                                        <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> '.'Edit'.'
                                    </button>
                                </li>
                                '.((! $row->children()->exists() && ! $row->products()->exists()) ? '
                                <li class="dropdown-divider"></li>
                                <li>
                                    <button class="dropdown-item deleteBtn" 
                                        data-delete-url="'.route('category.delete', $row->id).'" 
                                        data-method="DELETE" 
                                        data-table="#parentCategoryTable,#childCategoryTable">
                                        <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> '.'Delete'.'
                                    </button>
                                </li>
                                ' : '').'
                            </ul>
                        </div>
                    ';
                })
                ->rawColumns(['image', 'parent_category', 'status', 'action'])
                ->make(true);
        }

        $parentCategories = Category::whereNull('parent_id')->where('status', 1)->get();

        return view('admin.category.index', compact('parentCategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:categories,name',
            'parent_id' => 'nullable|exists:categories,id',
        ], [
            'name.required' => 'Category name is required',
            'name.unique' => 'This category already exists',
            'parent_id.exists' => 'Parent category does not exist',
        ]);

        $data = new Category;
        $data->name = $request->name;
        $data->description = $request->description;
        $data->slug = $this->uniqueSlug($request->name, Category::class);
        $data->parent_id = $request->parent_id;
        $data->meta_title = $request->meta_title;
        $data->meta_description = $request->meta_description;
        $data->meta_keywords = $request->meta_keywords;
        $data->video_url = $request->video_url;
        $data->sort_order = Category::max('sort_order') + 1;

        if ($request->hasFile('image')) {
            $uploadedFile = $request->file('image');
            $randomName = mt_rand(10000000, 99999999).'.webp';
            $destinationPath = public_path('uploads/category/');

            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            Image::make($uploadedFile)
                ->resize(800, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })
                ->encode('webp', 50)
                ->save($destinationPath.$randomName);

            $data->image = '/uploads/category/'.$randomName;
        }

        if ($request->hasFile('meta_image')) {
            $destinationPath = public_path('uploads/category/');
            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            $randomName = mt_rand(10000000, 99999999).'.webp';
            Image::make($request->file('meta_image'))
                ->resize(1200, 630, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })
                ->encode('webp', 80)
                ->save($destinationPath.$randomName);
            $data->meta_image = '/uploads/category/'.$randomName;
        }

        if ($data->save()) {
            return response()->json([
                'message' => 'Category created successfully',
                'category' => $data,
            ], 200);
        }

        return response()->json([
            'message' => 'Error creating category',
        ], 500);
    }

    public function edit($id)
    {
        $where = [
            'id' => $id,
        ];
        $info = Category::where($where)->get()->first();

        return response()->json($info);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:categories,name,'.$request->codeid,
            'parent_id' => 'nullable|exists:categories,id',
        ], [
            'name.required' => 'Category name is required',
            'name.unique' => 'This category already exists',
            'parent_id.exists' => 'Parent category does not exist',
        ]);

        $data = Category::findOrFail($request->codeid);
        $data->name = $request->name;
        $data->description = $request->description;
        // Slug always follows the latest name.
        $data->slug = $this->uniqueSlug($request->name, Category::class, $data->id);
        $data->parent_id = $request->parent_id;
        $data->meta_title = $request->meta_title;
        $data->meta_description = $request->meta_description;
        $data->meta_keywords = $request->meta_keywords;
        $data->video_url = $request->video_url;

        if ($request->hasFile('image')) {
            $uploadedFile = $request->file('image');

            // Only delete old image if it's not the placeholder
            if ($data->image && $data->image !== 'placeholder.webp' && file_exists(public_path($data->image))) {
                @unlink(public_path($data->image));
            }

            $randomName = mt_rand(10000000, 99999999).'.webp';
            $destinationPath = public_path('uploads/category/');
            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            Image::make($uploadedFile)
                ->resize(800, null, fn ($c) => $c->aspectRatio())
                ->encode('webp', 50)
                ->save($destinationPath.$randomName)
                ->destroy();

            $data->image = '/uploads/category/'.$randomName;
        } elseif ($request->boolean('remove_image')) {
            // Only delete old image if it's not the placeholder
            if ($data->image && $data->image !== 'placeholder.webp' && file_exists(public_path($data->image))) {
                @unlink(public_path($data->image));
            }
            $data->image = null;
        }

        if ($request->hasFile('meta_image')) {
            if ($data->meta_image && file_exists(public_path($data->meta_image))) {
                @unlink(public_path($data->meta_image));
            }
            $randomName = mt_rand(10000000, 99999999).'.webp';
            $destinationPath = public_path('uploads/category/');
            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            Image::make($request->file('meta_image'))
                ->resize(1200, 630, fn ($c) => $c->aspectRatio())
                ->encode('webp', 80)
                ->save($destinationPath.$randomName)
                ->destroy();
            $data->meta_image = '/uploads/category/'.$randomName;
        } elseif ($request->boolean('remove_meta_image')) {
            if ($data->meta_image && file_exists(public_path($data->meta_image))) {
                @unlink(public_path($data->meta_image));
            }
            $data->meta_image = null;
        }

        if ($data->save()) {
            return response()->json([
                'message' => 'Category updated successfully',
            ], 200);
        }

        return response()->json([
            'message' => 'Error updating category',
        ], 500);
    }

    public function delete($id)
    {
        $data = Category::find($id);

        if (! $data) {
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        }

        $hasSubcategories = Category::where('parent_id', $id)->exists();
        if ($hasSubcategories) {
            return response()->json([
                'message' => 'This category has subcategories',
            ], 422);
        }

        if ($data->image && file_exists(public_path($data->image))) {
            @unlink(public_path($data->image));
        }

        if ($data->delete()) {
            return response()->json([
                'message' => 'Category deleted successfully',
            ], 200);
        }

        return response()->json([
            'message' => 'Error deleting category',
        ], 500);
    }

    public function toggleStatus(Request $request)
    {
        $category = Category::find($request->category_id);

        if (! $category) {
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        }

        $category->status = $request->status;

        if ($category->save()) {
            return response()->json([
                'message' => 'Status updated successfully',
            ], 200);
        }

        return response()->json([
            'message' => 'Error updating status',
        ], 500);
    }

    public function parentCategories()
    {
        $parentCategories = Category::where('status', 1)
            ->select('id', 'name')
            ->latest()
            ->get();

        return response()->json($parentCategories);
    }

    public function sortList()
    {
        $categories = Category::select(['id', 'name', 'image', 'parent_id', 'sort_order'])
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($category) {
                $category->image = $category->image ? url($category->image) : null;

                return $category;
            });

        return response()->json($categories);
    }

    public function sortUpdate(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
        ]);

        foreach ($request->ids as $index => $id) {
            Category::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['message' => 'Sort order updated successfully']);
    }
}

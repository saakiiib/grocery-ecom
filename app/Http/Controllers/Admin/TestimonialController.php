<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use Yajra\DataTables\Facades\DataTables;

class TestimonialController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $testimonials = Testimonial::select(['id', 'name', 'designation', 'image', 'review', 'sort_order', 'is_active'])->orderBy('sort_order');

            return DataTables::of($testimonials)
                ->addIndexColumn()
                ->addColumn('image', function ($row) {
                    $src = $row->image ? asset($row->image) : asset('placeholder.webp');

                    return '<img src="'.$src.'" class="img-thumbnail" style="width:50px;height:50px;object-fit:cover;border-radius:50%;">';
                })
                ->addColumn('review', function ($row) {
                    return strlen($row->review) > 60 ? substr($row->review, 0, 60).'...' : $row->review;
                })
                ->addColumn('status', function ($row) {
                    $checked = $row->is_active ? 'checked' : '';

                    return '
                        <div class="form-check form-switch" dir="ltr">
                            <input type="checkbox" class="form-check-input toggle-status"
                                id="status'.$row->id.'"
                                data-id="'.$row->id.'" '.$checked.'>
                            <label class="form-check-label" for="status'.$row->id.'"></label>
                        </div>';
                })
                ->addColumn('action', function ($row) {
                    return '
                        <div class="dropdown">
                            <button class="btn btn-soft-secondary btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ri-more-fill align-middle"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button class="dropdown-item edit-btn" data-id="'.$row->id.'" data-url="'.route('testimonial.edit', $row->id).'">
                                        <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit
                                    </button>
                                </li>
                                <li class="dropdown-divider"></li>
                                <li>
                                    <button class="dropdown-item deleteBtn" data-delete-url="'.route('testimonial.delete', $row->id).'" data-method="DELETE" data-table="#testimonialTable">
                                        <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> Delete
                                    </button>
                                </li>
                            </ul>
                        </div>';
                })
                ->rawColumns(['image', 'status', 'action'])
                ->make(true);
        }

        return view('admin.testimonials.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'review' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        Testimonial::create([
            'name' => $request->name,
            'designation' => $request->designation,
            'review' => $request->review,
            'image' => $this->uploadImage($request) ?? null,
            'sort_order' => Testimonial::max('sort_order') + 1,
            'is_active' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Testimonial added successfully.']);
    }

    public function edit($id)
    {
        $testimonial = Testimonial::findOrFail($id);

        return response()->json(['success' => true, 'data' => $testimonial]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'review' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $testimonial = Testimonial::findOrFail($request->id);

        $data = [
            'name' => $request->name,
            'designation' => $request->designation,
            'review' => $request->review,
        ];

        if ($request->hasFile('image')) {
            // Only delete old image if it's not the placeholder
            if ($testimonial->image && $testimonial->image !== 'placeholder.webp' && file_exists(public_path(ltrim($testimonial->image, '/')))) {
                @unlink(public_path(ltrim($testimonial->image, '/')));
            }
            $data['image'] = $this->uploadImage($request);
        } elseif ($request->boolean('remove_image')) {
            // Only delete old image if it's not the placeholder
            if ($testimonial->image && $testimonial->image !== 'placeholder.webp' && file_exists(public_path(ltrim($testimonial->image, '/')))) {
                @unlink(public_path(ltrim($testimonial->image, '/')));
            }
            $data['image'] = null;
        }

        $testimonial->update($data);

        return response()->json(['success' => true, 'message' => 'Testimonial updated successfully.']);
    }

    public function destroy($id)
    {
        $testimonial = Testimonial::findOrFail($id);
        if ($testimonial->image && file_exists(public_path(ltrim($testimonial->image, '/')))) {
            @unlink(public_path(ltrim($testimonial->image, '/')));
        }
        $testimonial->delete();

        return response()->json(['success' => true, 'message' => 'Testimonial deleted successfully.']);
    }

    public function toggleStatus(Request $request)
    {
        $testimonial = Testimonial::findOrFail($request->id);
        $testimonial->update(['is_active' => ! $testimonial->is_active]);

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    private function uploadImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $filename = mt_rand(10000000, 99999999).'.webp';
        $destPath = public_path('uploads/testimonials/');
        if (! file_exists($destPath)) {
            mkdir($destPath, 0755, true);
        }

        Image::make($request->file('image'))
            ->resize(300, 300, function ($c) {
                $c->aspectRatio();
                $c->upsize();
            })
            ->encode('webp', 80)
            ->save($destPath.$filename);

        return '/uploads/testimonials/'.$filename;
    }
}

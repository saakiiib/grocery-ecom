<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use Yajra\DataTables\Facades\DataTables;

class SliderController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $sliders = Slider::select(['id', 'title', 'subtitle', 'image', 'sort_order', 'is_active'])->orderBy('sort_order');

            return DataTables::of($sliders)
                ->addIndexColumn()
                ->addColumn('image', function ($row) {
                    $src = $row->image ? asset($row->image) : asset('placeholder.webp');

                    return '<img src="'.$src.'" class="img-thumbnail" style="width:100px;height:55px;object-fit:cover;">';
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
                                    <button class="dropdown-item edit-btn" data-id="'.$row->id.'" data-url="'.route('slider.edit', $row->id).'">
                                        <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit
                                    </button>
                                </li>
                                <li class="dropdown-divider"></li>
                                <li>
                                    <button class="dropdown-item deleteBtn" data-delete-url="'.route('slider.delete', $row->id).'" data-method="DELETE" data-table="#sliderTable">
                                        <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> Delete
                                    </button>
                                </li>
                            </ul>
                        </div>';
                })
                ->rawColumns(['image', 'status', 'action'])
                ->make(true);
        }

        return view('admin.sliders.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string',
            'btn_text' => 'nullable|string|max:255',
            'btn_url' => 'nullable|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:3072',
        ]);

        $imagePath = 'placeholder.webp';
        if ($request->hasFile('image')) {
            $filename = mt_rand(10000000, 99999999).'.webp';
            $destPath = public_path('uploads/sliders/');
            if (! file_exists($destPath)) {
                mkdir($destPath, 0755, true);
            }

            Image::make($request->file('image'))
                ->resize(1920, null, function ($c) {
                    $c->aspectRatio();
                    $c->upsize();
                })
                ->encode('webp', 80)
                ->save($destPath.$filename);

            $imagePath = '/uploads/sliders/'.$filename;
        }

        Slider::create([
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'btn_text' => $request->btn_text,
            'btn_url' => $request->btn_url,
            'image' => $imagePath,
            'sort_order' => Slider::max('sort_order') + 1,
            'is_active' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Slider created successfully.']);
    }

    public function edit($id)
    {
        $slider = Slider::findOrFail($id);

        return response()->json(['success' => true, 'data' => $slider]);
    }

    public function update(Request $request)
    {
        $slider = Slider::findOrFail($request->id);

        $request->validate([
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string',
            'btn_text' => 'nullable|string|max:255',
            'btn_url' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:3072',
        ]);

        if ($request->hasFile('image')) {
            // Only delete old image if it's not the placeholder
            if ($slider->image && $slider->image !== 'placeholder.webp' && file_exists(public_path($slider->image))) {
                @unlink(public_path($slider->image));
            }
            $filename = mt_rand(10000000, 99999999).'.webp';
            $destPath = public_path('uploads/sliders/');
            if (! file_exists($destPath)) {
                mkdir($destPath, 0755, true);
            }

            Image::make($request->file('image'))
                ->resize(1920, null, function ($c) {
                    $c->aspectRatio();
                    $c->upsize();
                })
                ->encode('webp', 80)
                ->save($destPath.$filename);

            $slider->image = '/uploads/sliders/'.$filename;
        } elseif ($request->boolean('remove_image')) {
            // Only delete old image if it's not the placeholder
            if ($slider->image && $slider->image !== 'placeholder.webp' && file_exists(public_path($slider->image))) {
                @unlink(public_path($slider->image));
            }
            $slider->image = null;
        }

        $slider->title = $request->title;
        $slider->subtitle = $request->subtitle;
        $slider->btn_text = $request->btn_text;
        $slider->btn_url = $request->btn_url;
        $slider->save();

        return response()->json(['success' => true, 'message' => 'Slider updated successfully.']);
    }

    public function destroy($id)
    {
        $slider = Slider::findOrFail($id);
        if ($slider->image && $slider->image !== 'placeholder.webp' && file_exists(public_path($slider->image))) {
            @unlink(public_path($slider->image));
        }
        $slider->delete();

        return response()->json(['success' => true, 'message' => 'Slider deleted successfully.']);
    }

    public function toggleStatus(Request $request)
    {
        $slider = Slider::findOrFail($request->id);
        $slider->update(['is_active' => ! $slider->is_active]);

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $reviews = ProductReview::with(['product:id,name', 'user:id,name'])
                ->select(['id', 'product_id', 'user_id', 'rating', 'title', 'body', 'status', 'created_at'])
                ->latest();

            return DataTables::of($reviews)
                ->addIndexColumn()
                ->addColumn('product', fn ($row) => $row->product?->name ?? '<span class="text-muted">-</span>')
                ->addColumn('user', fn ($row) => $row->user?->name ?? '<span class="text-muted">-</span>')
                ->addColumn('rating', function ($row) {
                    return str_repeat('★', $row->rating).str_repeat('☆', 5 - $row->rating);
                })
                ->addColumn('review', function ($row) {
                    $text = $row->title ? $row->title.' — '.$row->body : $row->body;

                    return strlen($text) > 80 ? substr($text, 0, 80).'...' : $text;
                })
                ->addColumn('status', function ($row) {
                    $checked = $row->status ? 'checked' : '';

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
                                    <button class="dropdown-item edit-btn" data-id="'.$row->id.'" data-url="'.route('reviews.edit', $row->id).'">
                                        <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit
                                    </button>
                                </li>
                                <li class="dropdown-divider"></li>
                                <li>
                                    <button class="dropdown-item deleteBtn"
                                        data-delete-url="'.route('reviews.delete', $row->id).'"
                                        data-method="DELETE"
                                        data-table="#reviewTable">
                                        <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> Delete
                                    </button>
                                </li>
                            </ul>
                        </div>';
                })
                ->rawColumns(['product', 'user', 'status', 'action'])
                ->make(true);
        }

        return view('admin.reviews.index');
    }

    public function toggleStatus(Request $request)
    {
        $review = ProductReview::findOrFail($request->id);
        $review->status = ! $review->status;
        $review->save();

        return response()->json([
            'success' => true,
            'message' => $review->status ? 'Review is now visible on the storefront.' : 'Review hidden from the storefront.',
        ]);
    }

    public function edit($id)
    {
        $review = ProductReview::with(['product:id,name', 'user:id,name'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $review->id,
                'product' => $review->product?->name ?? '-',
                'user' => $review->user?->name ?? '-',
                'rating' => $review->rating,
                'title' => $review->title,
                'body' => $review->body,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer|exists:product_reviews,id',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:120',
            'body' => 'required|string|max:2000',
        ]);

        $review = ProductReview::findOrFail($data['id']);
        $review->update([
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'body' => $data['body'],
        ]);

        return response()->json(['success' => true, 'message' => 'Review updated.']);
    }

    public function destroy($id)
    {
        ProductReview::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Review deleted.']);
    }
}

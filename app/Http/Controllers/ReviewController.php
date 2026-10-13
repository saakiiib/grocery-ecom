<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Create or update the shopper's own review (one per product).
     * Approved reviews show on the product page immediately.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:120',
            'body' => 'required|string|max:2000',
        ]);

        $product = Product::where('status', true)->findOrFail($data['product_id']);

        // New reviews wait for admin approval; edits keep their current status.
        $review = ProductReview::firstOrNew([
            'product_id' => $product->id, 'user_id' => auth()->id(),
        ]);
        $isNew = ! $review->exists;
        $review->fill([
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'body' => $data['body'],
        ]);
        if ($isNew) {
            $review->status = false;
        }
        $review->save();

        $summary = $this->summary($product->id);

        return response()->json([
            'ok' => true,
            'message' => $review->status ? 'Thanks — your review is live.' : 'Thanks — your review will appear once approved.',
            'review' => [
                'id' => $review->id,
                'rating' => $review->rating,
                'title' => $review->title,
                'body' => $review->body,
                'author' => auth()->user()->name,
                'date' => $review->created_at->format('j M Y'),
            ],
            'avg' => $summary['avg'],
            'count' => $summary['count'],
        ]);
    }

    /** Remove the shopper's own review. */
    public function destroy(int $id): JsonResponse
    {
        $review = ProductReview::where('user_id', auth()->id())->findOrFail($id);
        $productId = $review->product_id;
        $review->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Your review was removed.',
            ...$this->summary($productId),
        ]);
    }

    /**
     * @return array{avg: float|null, count: int}
     */
    private function summary(int $productId): array
    {
        $row = ProductReview::approved()
            ->where('product_id', $productId)
            ->selectRaw('COUNT(*) as count, AVG(rating) as avg')
            ->first();

        return [
            'avg' => $row && $row->count > 0 ? round((float) $row->avg, 1) : null,
            'count' => (int) ($row->count ?? 0),
        ];
    }
}

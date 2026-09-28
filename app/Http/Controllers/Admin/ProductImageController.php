<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;

class ProductImageController extends Controller
{
    public function list($productId)
    {
        return response()->json(
            ProductImage::where('product_id', $productId)->orderBy('sort_order')->get()
                ->map(fn ($i) => [...$i->toArray(), 'preview' => url($i->image)])
        );
    }

    public function store(Request $request, $productId)
    {
        $request->validate(['image' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096']);

        $path = public_path('uploads/products/gallery/');
        if (! file_exists($path)) {
            mkdir($path, 0755, true);
        }
        $name = mt_rand(10000000, 99999999).'.webp';
        Image::make($request->file('image'))->resize(1600, null, function ($c) {
            $c->aspectRatio();
            $c->upsize();
        })->encode('webp', 75)->save($path.$name);

        $img = ProductImage::create([
            'product_id' => $productId,
            'image' => '/uploads/products/gallery/'.$name,
            'caption' => $request->caption,
            'sort_order' => (int) (ProductImage::where('product_id', $productId)->max('sort_order') ?? 0) + 1,
        ]);

        return response()->json(['message' => 'Image added', 'data' => $img]);
    }

    public function update(Request $request, $id)
    {
        $img = ProductImage::findOrFail($id);
        $img->update($request->only(['caption']));

        return response()->json(['message' => 'Caption updated']);
    }

    public function destroy($id)
    {
        $img = ProductImage::findOrFail($id);
        if ($img->image && file_exists(public_path($img->image))) {
            @unlink(public_path($img->image));
        }
        $img->delete();

        return response()->json(['message' => 'Image deleted']);
    }
}

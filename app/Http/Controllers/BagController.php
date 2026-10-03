<?php

namespace App\Http\Controllers;

use App\Models\BogoOffer;
use App\Models\BundleOffer;
use App\Models\FlashSale;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BagController extends Controller
{
    public const SESSION_KEY = 'bag';

    /** Raw session bag: [variantId => qty]. */
    public static function bag(): array
    {
        $bag = session()->get(self::SESSION_KEY, []);

        return is_array($bag) ? $bag : [];
    }

    public static function count(): int
    {
        return array_sum(static::bag());
    }

    public static function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * Server-priced bag lines. Prices always come from the database —
     * the browser never decides what anything costs. Live BOGO offers
     * apply first (independent), then dynamic bundles regroup paid units,
     * so bag, checkout, and orders can never disagree.
     *
     * @return array{lines: array, count: int, subtotal: float, bogo_discount: float, bundle_discount: float}
     */
    public static function detailed(): array
    {
        $bag = static::bag();

        if ($bag === []) {
            return ['lines' => [], 'count' => 0, 'subtotal' => 0.0, 'bogo_discount' => 0.0, 'bundle_discount' => 0.0];
        }

        $variants = ProductVariant::with('product')
            ->whereIn('id', array_keys($bag))
            ->get()
            ->keyBy('id');

        $lines = [];
        $subtotal = 0.0;
        $bogoDiscount = 0.0;
        $bogos = BogoOffer::liveAll();
        $flash = FlashSale::liveMap();

        foreach ($bag as $variantId => $qty) {
            $variant = $variants->get($variantId);
            $product = $variant?->product;
            $available = $variant && $product
                && (bool) $product->status
                && (bool) $variant->status
                && (bool) $variant->in_stock;

            $price = $available ? (float) $variant->sellingPrice() : 0.0;
            // Scheduled flash beats the shelf price while live (never above it).
            if ($available && $variant) {
                $flashHit = FlashSale::priceFor($variant->id, $product->id, $flash);
                if ($flashHit && $flashHit['price'] < $price) {
                    $price = $flashHit['price'];
                }
            }
            $lineTotal = round($price * $qty, 2);

            // Independent BOGO: threshold met on this variant → free units, done.
            $promoLabel = null;
            $freeQty = 0;
            if ($available && $variant) {
                $bogo = BogoOffer::matchIn($bogos, $variant->id, $product->id);
                if ($bogo) {
                    $freeQty = $bogo->freeUnitsFor((int) $qty);
                    if ($freeQty > 0) {
                        $promoLabel = $bogo->label();
                        $saving = round($price * $freeQty, 2);
                        $lineTotal = round($lineTotal - $saving, 2);
                        $bogoDiscount = round($bogoDiscount + $saving, 2);
                    }
                }
            }
            $subtotal += $lineTotal;

            $lines[] = [
                'variant_id' => (int) $variantId,
                'qty' => (int) $qty,
                'available' => $available,
                'name' => $product?->name ?? 'Unavailable item',
                'slug' => $product?->slug,
                'pack' => $variant?->combinationLabel() ?? '',
                'sku' => $variant?->sku,
                'price' => $price,
                'line_total' => $lineTotal,
                'promo_label' => $promoLabel,
                'free_qty' => $freeQty,
                'image' => static::bagImage($variant?->image, 'uploads/products/variants/')
                    ?? static::bagImage($product?->hero_image, 'uploads/products/')
                    ?? asset('placeholder.webp'),
            ];
        }

        // Dynamic bundles regroup paid units cheapest-first (BOGO already applied).
        $bundleDiscount = BundleOffer::applyToLines($lines);
        $subtotal = round(array_sum(array_column($lines, 'line_total')), 2);

        return [
            'lines' => $lines,
            'count' => array_sum($bag),
            'subtotal' => round($subtotal, 2),
            'bogo_discount' => round($bogoDiscount, 2),
            'bundle_discount' => round($bundleDiscount, 2),
        ];
    }

    /**
     * Resolve a stored image to a URL. Handles absolute URLs (seeded),
     * paths that already carry their folder (admin uploads), and bare
     * filenames. Null when there is nothing to resolve.
     */
    private static function bagImage(?string $path, string $dir): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        if (str_starts_with($path, $dir)) {
            return asset($path);
        }

        return asset($dir.'/'.ltrim($path, '/'));
    }

    /**
     * Reconcile the session bag against the catalogue: drop unknown variants
     * and clamp quantities. Returns removed/skipped variant ids.
     *
     * @return array{removed: array, unavailable: array}
     */
    public static function reconcile(): array
    {
        $bag = static::bag();

        if ($bag === []) {
            return ['removed' => [], 'unavailable' => []];
        }

        $variants = ProductVariant::with('product')
            ->whereIn('id', array_keys($bag))
            ->get()
            ->keyBy('id');

        $removed = [];
        $unavailable = [];

        foreach ($bag as $variantId => $qty) {
            $variant = $variants->get($variantId);
            if (! $variant) {
                unset($bag[$variantId]);
                $removed[] = (int) $variantId;

                continue;
            }
            $bag[$variantId] = min(max((int) $qty, 1), 99);
            if (! $variant->product || ! $variant->product->status || ! $variant->status || ! $variant->in_stock) {
                $unavailable[] = (int) $variantId;
            }
        }

        session()->put(self::SESSION_KEY, $bag);

        return ['removed' => $removed, 'unavailable' => $unavailable];
    }

    public function add(Request $request): JsonResponse
    {
        $data = $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,id',
            'qty' => 'nullable|integer|min:1|max:99',
        ]);

        $variant = ProductVariant::with('product')->findOrFail($data['variant_id']);

        if (! $variant->product || ! $variant->product->status || ! $variant->status || ! $variant->in_stock) {
            return response()->json(['message' => 'Sorry, that item is out of stock.'], 422);
        }

        $bag = static::bag();
        $bag[$variant->id] = min(($bag[$variant->id] ?? 0) + ($data['qty'] ?? 1), 99);
        session()->put(self::SESSION_KEY, $bag);

        return response()->json(array_merge(['message' => 'Added to your bag.'], static::detailed()));
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,id',
            'qty' => 'required|integer|min:0|max:99',
        ]);

        $bag = static::bag();

        if (! array_key_exists($data['variant_id'], $bag)) {
            return response()->json(['message' => 'Item is not in your bag.'], 422);
        }

        if ($data['qty'] === 0) {
            unset($bag[$data['variant_id']]);
        } else {
            $bag[$data['variant_id']] = $data['qty'];
        }

        session()->put(self::SESSION_KEY, $bag);
        static::reconcile();

        return response()->json(static::detailed());
    }

    public function remove(Request $request): JsonResponse
    {
        $data = $request->validate(['variant_id' => 'required|integer|exists:product_variants,id']);

        $bag = static::bag();
        unset($bag[$data['variant_id']]);
        session()->put(self::SESSION_KEY, $bag);

        return response()->json(static::detailed());
    }

    public function show(): JsonResponse
    {
        static::reconcile();

        return response()->json(static::detailed());
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BagController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\FrontendController;
use App\Models\Allergen;
use App\Models\BogoOffer;
use App\Models\BundleOffer;
use App\Models\Category;
use App\Models\CompanyDetails;
use App\Models\DeliverySlot;
use App\Models\FlashSale;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Setting;
use App\Models\Slider;
use App\Models\Testimonial;
use App\Models\UserPoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** JSON twin of FrontendController — same queries, same rules, no views. */
class CatalogApiController extends FrontendController
{
    public function home(): JsonResponse
    {
        $products = Product::with(['category', 'images', 'variants.values.group'])
            ->withReviewSummary()->where('status', true)
            ->orderBy('sort_order')->orderByDesc('id')->get();
        $categories = Category::where('status', true)->orderBy('sort_order')->orderBy('id')->get();

        $cover = BundleOffer::coverMap();
        $flash = FlashSale::liveMap();
        $bogo = BogoOffer::liveAll();

        $featured = $products->where('is_featured', true)->values()->take(4)
            ->map(fn ($p) => $this->productCard($p, $cover, $flash, $bogo))->values();
        $offers = $products->filter(fn ($p) => $this->hasDeal($p, $flash))->take(4)->values()
            ->map(fn ($p) => $this->productCard($p, $cover, $flash, $bogo))->values();
        $cats = $categories->whereNull('parent_id')->take(10)->values()->map(function ($c) use ($products, $categories) {
            $ids = $this->categorySubtreeIds($categories, $c->id);

            return [
                'name' => $c->name,
                'slug' => $c->slug,
                'description' => $c->description,
                'image' => $c->image ? url($c->image) : asset('placeholder.webp'),
                'count' => $products->whereIn('category_id', $ids)->count(),
            ];
        })->values();

        return response()->json([
            'sliders' => Slider::where('is_active', true)->orderBy('sort_order')->orderBy('id')
                ->get(['badge', 'title', 'subtitle', 'btn_text', 'btn_url', 'btn_text2', 'btn_url2', 'image'])
                ->map(fn ($s) => [...$s->toArray(), 'image' => $s->image ? url($s->image) : url('placeholder.webp')])->values(),
            'categories' => $cats,
            'featured' => $featured,
            'offers' => $offers,
            'testimonials' => Testimonial::where('is_active', true)->orderBy('sort_order')->orderBy('id')
                ->get(['name', 'image', 'designation', 'review'])
                ->map(fn ($t) => [...$t->toArray(), 'image' => $t->image ? url($t->image) : null])->values(),
            'faqs' => $this->faqsJson(),
            'faq_categories' => $this->faqCatsJson(),
            'gallery' => $this->galleryJson(8),
            'company' => $this->company(),
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = Category::where('status', true)->orderBy('sort_order')->orderBy('id')->get();

        return response()->json(['categories' => $categories->map(fn ($c) => [
            'id' => $c->id,
            'parent_id' => $c->parent_id,
            'name' => $c->name,
            'slug' => $c->slug,
            'description' => $c->description,
            'image' => $c->image ? url($c->image) : asset('placeholder.webp'),
        ])->values()]);
    }

    /** Same filters/sort/paging as the web shop. */
    public function products(Request $request): JsonResponse
    {
        $categories = Category::where('status', true)->orderBy('sort_order')->orderBy('id')->get();
        $products = Product::with(['category', 'images', 'variants.values.group', 'allergens'])
            ->withReviewSummary()->where('status', true)
            ->orderBy('sort_order')->orderByDesc('id')->get();

        $category = $request->get('category');
        $onlyOffers = $category === 'offers' || $request->boolean('only_offers');
        $descendantIds = null;
        $activeSlug = null;
        if ($category && $category !== 'All' && $category !== 'offers') {
            $match = $categories->firstWhere('slug', $category)
                ?? $categories->first(fn ($c) => strcasecmp($c->name, $category) === 0);
            if ($match) {
                $activeSlug = $match->slug;
                $descendantIds = $this->categorySubtreeIds($categories, $match->id);
            }
        }

        $search = trim((string) $request->get('q', ''));
        $sort = $request->get('sort', 'featured');
        if (! in_array($sort, ['featured', 'price_asc', 'price_desc', 'offers', 'name'], true)) {
            $sort = 'featured';
        }
        $minPrice = $request->get('min_price');
        $minPrice = is_numeric($minPrice) && $minPrice >= 0 ? (float) $minPrice : null;
        $maxPrice = $request->get('max_price');
        $maxPrice = is_numeric($maxPrice) && $maxPrice >= 0 ? (float) $maxPrice : null;
        $rawPage = $request->get('page');
        $page = (is_scalar($rawPage) && ctype_digit((string) $rawPage) && (int) $rawPage >= 1) ? (int) $rawPage : 1;

        $diets = collect((array) $request->get('diet', []))
            ->map(fn ($d) => strtolower(trim((string) $d)))
            ->intersect(['vegetarian', 'vegan', 'halal', 'organic', 'gluten_free'])->values()->all();
        $freeFromInput = (array) $request->get('free_from', []);
        $freeFrom = $freeFromInput !== [] ? Allergen::whereIn('slug', $freeFromInput)->pluck('slug')->all() : [];

        $flash = FlashSale::liveMap();
        $cover = BundleOffer::coverMap();
        $bogo = BogoOffer::liveAll();

        $rows = $products
            ->when($descendantIds, fn ($c) => $c->filter(fn ($p) => in_array($p->category_id, $descendantIds, true))->values())
            ->when($onlyOffers, fn ($c) => $c->filter(fn ($p) => $this->hasDeal($p, $flash))->values())
            ->when($diets !== [], fn ($c) => $c->filter(fn ($p) => collect($diets)->every(fn ($d) => (bool) $p->{'is_'.$d}))->values())
            ->when($freeFrom !== [], fn ($c) => $c->filter(fn ($p) => $p->allergens->pluck('slug')->intersect($freeFrom)->isEmpty())->values())
            ->when($search !== '', function ($c) use ($search) {
                $term = mb_strtolower($search);

                return $c->filter(fn ($p) => str_contains(mb_strtolower($p->name.' '.($p->category?->name ?? '').' '.($p->defaultVariant()?->sku ?? '')), $term));
            })
            ->map(fn ($p) => ['product' => $p, 'floor' => $this->dealFloor($p, $flash)])
            ->filter(fn ($row) => $row['floor'] !== null)->values();

        $priceFloor = (int) floor($rows->min('floor') ?? 0);
        $priceCeil = (int) ceil($rows->max('floor') ?? 0);
        if ($minPrice !== null) {
            $minPrice = max($minPrice, $priceFloor);
        }
        if ($maxPrice !== null) {
            $maxPrice = min($maxPrice, $priceCeil);
        }
        if ($minPrice !== null || $maxPrice !== null) {
            $rows = $rows->filter(fn ($row) => ($minPrice === null || $row['floor'] >= $minPrice) && ($maxPrice === null || $row['floor'] <= $maxPrice))->values();
        }

        $saveOf = function ($p) use ($flash) {
            $d = $p->defaultVariant();
            if (! $d) {
                return 0;
            }
            $selling = $d->sellingPrice();
            $hit = FlashSale::priceFor($d->id, $p->id, $flash);
            if ($hit && $hit['price'] < $selling) {
                $selling = $hit['price'];
            }
            $mrp = (float) $d->mrp;

            return ($mrp && $selling < $mrp) ? (int) round((($mrp - $selling) / $mrp) * 100) : 0;
        };
        $sorted = match ($sort) {
            'price_asc' => $rows->sortBy(fn ($r) => $r['floor'] ?? PHP_FLOAT_MAX)->values(),
            'price_desc' => $rows->sortByDesc(fn ($r) => $r['floor'] ?? 0)->values(),
            'offers' => $rows->sortByDesc(fn ($r) => $saveOf($r['product']))->values(),
            'name' => $rows->sortBy(fn ($r) => mb_strtolower($r['product']->name))->values(),
            default => $rows->sortByDesc(fn ($r) => $r['product']->is_featured)->values(),
        };

        $perPage = 24;
        $total = $sorted->count();
        $items = $sorted->slice(($page - 1) * $perPage, $perPage)->values()
            ->map(fn ($r) => $this->productCard($r['product'], $cover, $flash, $bogo))->values();

        return response()->json([
            'products' => $items,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'has_more' => $total > $page * $perPage,
            'active_category' => $activeSlug,
            'only_offers' => $onlyOffers,
            'price_floor' => $priceFloor,
            'price_ceil' => $priceCeil,
            'allergens' => Allergen::orderBy('sort_order')->get(['slug', 'name']),
            'has_diet_flags' => $products->contains(fn ($p) => $p->is_vegetarian || $p->is_vegan || $p->is_halal || $p->is_organic || $p->is_gluten_free),
        ]);
    }

    public function product(string $slug): JsonResponse
    {
        $product = Product::with(['category', 'images', 'extraAttributes', 'variants.values.group', 'optionGroups.values', 'category.optionGroups.values', 'allergens'])
            ->where('slug', $slug)->where('status', true)->firstOrFail();

        $related = Product::with(['category', 'images', 'variants.values.group'])->withReviewSummary()
            ->where('status', true)->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->orderBy('sort_order')->take(4)->get();
        if ($related->count() < 4) {
            $exclude = $related->pluck('id')->push($product->id)->values();
            $related = $related->concat(
                Product::with(['category', 'images', 'variants.values.group'])->withReviewSummary()
                    ->where('status', true)->whereNotIn('id', $exclude)->inRandomOrder()->take(4 - $related->count())->get()
            )->values();
        }

        $maps = [BundleOffer::coverMap(), FlashSale::liveMap(), BogoOffer::liveAll()];
        $detail = $this->productDetail($product, ...$maps);
        $stats = ProductReview::approved()->where('product_id', $product->id)
            ->selectRaw('COUNT(*) as count, AVG(rating) as avg')->first();
        $detail['ratingAvg'] = $stats && $stats->count > 0 ? round((float) $stats->avg, 1) : null;
        $detail['ratingCount'] = (int) ($stats->count ?? 0);

        $reviews = ProductReview::approved()->with('user:id,name')->where('product_id', $product->id)
            ->latest()->take(20)->get()->map(fn ($r) => [
                'id' => $r->id,
                'rating' => $r->rating,
                'title' => $r->title,
                'body' => $r->body,
                'author' => $r->user?->name ?? 'Shopper',
                'date' => $r->created_at->format('j M Y'),
                'mine' => auth()->id() !== null && $r->user_id === auth()->id(),
            ])->values();

        return response()->json([
            'product' => $detail,
            'reviews' => $reviews,
            'my_review' => auth()->check()
                ? ProductReview::where('product_id', $product->id)->where('user_id', auth()->id())->first()
                : null,
            'related' => $related->map(fn ($p) => $this->productCard($p, ...$maps))->values(),
            'faqs' => $this->faqsJson(4),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $q = mb_strtolower(trim((string) $request->get('q', '')));
        $all = collect(static::searchCatalogData());
        if ($q !== '') {
            $all = $all->filter(fn ($row) => str_contains(mb_strtolower($row['tags']), $q))->values();
        }

        return response()->json(['results' => $all->take(30)->values()]);
    }

    public function bag(): JsonResponse
    {
        BagController::reconcile();

        return response()->json(BagController::detailed());
    }

    /** Everything the checkout screen needs (mirrors FrontendController::checkout). */
    public function checkoutInit(): JsonResponse
    {
        BagController::reconcile();
        $shopper = auth()->user();
        $addresses = collect();
        $defaultDelivery = null;
        $defaultBilling = null;
        if ($shopper) {
            $shopper->ensureAddressBook();
            $addresses = $shopper->addresses()->get();
            $defaultDelivery = $shopper->defaultDeliveryAddress();
            $defaultBilling = $shopper->defaultBillingAddress();
        }

        return response()->json([
            'bag' => BagController::detailed(),
            'slots' => DeliverySlot::ordered()->map(fn ($s) => [
                'id' => $s->id, 'name' => $s->name, 'label' => $s->label(), 'fee' => (float) $s->fee,
                'starts_at' => $s->starts_at, 'ends_at' => $s->ends_at,
            ])->values(),
            'dates' => DeliverySlot::bookableDates(),
            'min_order' => Setting::money('delivery_min_order', 15.00),
            'free_over' => Setting::money('delivery_free_over', 50.00),
            'stripe_on' => CheckoutController::stripeConfigured(),
            'paypal_on' => CheckoutController::paypalConfigured(),
            'stripe_publishable' => CheckoutController::stripePublishable(),
            'paypal_client_id' => CheckoutController::paypalClientId(),
            'paypal_mode' => CheckoutController::paypalMode(),
            'points_balance' => $shopper ? UserPoint::balance($shopper->id) : 0,
            'points_value' => UserPoint::value(),
            'points_min' => UserPoint::minRedeem(),
            'addresses' => $addresses,
            'default_delivery' => $defaultDelivery,
            'default_billing' => $defaultBilling,
            'shopper' => $shopper?->only(['name', 'email', 'phone', 'address', 'city', 'postcode']),
        ]);
    }

    /** Guest/shopper tracking: order number + phone used at checkout (+44 → 0). */
    public function trackOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'number' => 'required|string|max:30',
            'phone' => 'required|string|max:30',
        ]);

        $number = strtoupper(trim($data['number']));
        $normalize = fn ($p) => preg_replace('/^\+44/', '0', (string) preg_replace('/[\s\-()]/', '', $p));
        $phone = $normalize($data['phone']);

        $order = Order::with(['items', 'histories', 'status'])->where('number', $number)->get()
            ->first(fn ($o) => $normalize($o->phone) === $phone);

        if (! $order) {
            return response()->json([
                'message' => 'We could not find that order — check the number and the phone used at checkout.',
                'errors' => ['number' => ['We could not find that order — check the number and the phone used at checkout.']],
            ], 422);
        }

        return response()->json(['order' => OrderPayload::make($order)]);
    }

    public function faqs(): JsonResponse
    {
        return response()->json(['faqs' => $this->faqsJson(), 'categories' => $this->faqCatsJson()]);
    }

    public function gallery(): JsonResponse
    {
        return response()->json(['gallery' => $this->galleryJson(), 'categories' => $this->galleryCatsJson()]);
    }

    public function delivery(): JsonResponse
    {
        return response()->json([
            'min_order' => Setting::money('delivery_min_order', 15.00),
            'free_over' => Setting::money('delivery_free_over', 50.00),
            'slots' => DeliverySlot::ordered()->map(fn ($s) => ['id' => $s->id, 'label' => $s->label(), 'fee' => (float) $s->fee])->values(),
        ]);
    }

    public function loyalty(): JsonResponse
    {
        return response()->json([
            'per_pound' => UserPoint::perPound(),
            'value' => UserPoint::value(),
            'min_redeem' => UserPoint::minRedeem(),
            'balance' => auth()->check() ? UserPoint::balance(auth()->id()) : null,
        ]);
    }

    private function company(): array
    {
        $c = CompanyDetails::cached();

        return $c ? $c->only(['name', 'email', 'phone', 'address', 'vat_percent']) : [];
    }
}

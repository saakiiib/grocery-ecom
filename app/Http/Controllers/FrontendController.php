<?php

namespace App\Http\Controllers;

use App\Models\Allergen;
use App\Models\BogoOffer;
use App\Models\BundleOffer;
use App\Models\Category;
use App\Models\CompanyDetails;
use App\Models\Contact;
use App\Models\DeliverySlot;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\Favourite;
use App\Models\FlashSale;
use App\Models\Gallery;
use App\Models\GalleryCategory;
use App\Models\Order;
use App\Models\PageSeo;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Slider;
use App\Models\Testimonial;
use App\Models\UserPoint;
use App\Support\SitePromo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use OpenGraph;
use SEOMeta;
use Twitter;

class FrontendController extends Controller
{
    /** Absolute fallback image used when a product and its category have none. */
    public const DEFAULT_IMAGE = 'placeholder.webp';

    public function index()
    {
        $this->seo('home');

        $products = Product::with(['category', 'images', 'variants.values.group'])
            ->withReviewSummary()
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
        $featured = $products->where('is_featured', true)->values();
        if ($featured->isEmpty()) {
            $featured = $products->take(4)->values();
        }
        $categories = Category::where('status', true)->orderBy('sort_order')->orderBy('id')->get();

        $bundleCover = BundleOffer::coverMap();
        $flashMap = FlashSale::liveMap();
        $bogoLive = BogoOffer::liveAll();
        $productsJson = collect();
        $featuredJson = collect();
        $featuredCards = $products->where('is_featured', true)->values()->map(fn ($p) => $this->productCard($p, $bundleCover, $flashMap, $bogoLive))->values();
        // Homepage "Shop by category" shows top 10 parents, in parent-scoped sort_order.
        $categoriesJson = $categories->whereNull('parent_id')->take(10)->values()->map(function ($c) use ($products, $categories) {
            $ids = $this->categorySubtreeIds($categories, $c->id);

            return [
                'name' => $c->name,
                'slug' => $c->slug,
                'description' => $c->description,
                'image' => $c->image ? url($c->image) : asset('placeholder.webp'),
                'count' => $products->whereIn('category_id', $ids)->count(),
            ];
        })->values();
        $offerCards = $products->filter(fn ($p) => $this->hasDeal($p, $flashMap))
            ->take(4)->values()->map(fn ($p) => $this->productCard($p, $bundleCover, $flashMap, $bogoLive))->values();
        $faqsJson = $this->faqsJson();
        $faqCatsJson = $this->faqCatsJson();
        $galleryJson = $this->galleryJson();
        $galleryCatsJson = $this->galleryCatsJson();
        $testimonialsJson = Testimonial::where('is_active', true)->orderBy('sort_order')->orderBy('id')
            ->get(['name', 'image', 'designation', 'review'])
            ->map(fn ($t) => [...$t->toArray(), 'image' => $t->image ? url($t->image) : null])
            ->values();
        $filesJson = collect();
        $zonesJson = collect();
        $slidersJson = Slider::where('is_active', true)->orderBy('sort_order')->orderBy('id')
            ->get(['badge', 'title', 'subtitle', 'btn_text', 'btn_url', 'btn_text2', 'btn_url2', 'image'])
            ->map(fn ($s) => [...$s->toArray(), 'image' => $s->image ? url($s->image) : url('placeholder.webp')])
            ->values();

        $promo = SitePromo::promo();

        return spa('frontend.index', compact('productsJson', 'featuredJson', 'featuredCards', 'categoriesJson', 'offerCards', 'faqsJson', 'faqCatsJson', 'galleryJson', 'galleryCatsJson', 'testimonialsJson', 'filesJson', 'zonesJson', 'slidersJson', 'promo'));
    }

    public function shop(Request $request, ?string $category = null)
    {
        $this->seo('shop');

        $categories = Category::where('status', true)->orderBy('sort_order')->orderBy('id')->get();
        $products = Product::with(['category', 'images', 'variants.values.group', 'allergens'])
            ->withReviewSummary()
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        // Parent/child tree for the shop pills. Parents render in parent-scoped
        // sort_order, children as chips in their own per-parent sort_order.
        $parents = $categories->whereNull('parent_id')->values();

        // Pretty paths: /shop/{category-slug} filters, /shop/offers shows offers.
        // Plain query params (?category=, ?only_offers=1) still work but 301 to
        // the pretty URL so only one canonical address exists. A parent selection
        // includes every descendant category's products.
        if ($category !== null && $category !== 'offers' && ! $categories->firstWhere('slug', $category)) {
            abort(404);
        }
        $onlyOffers = $category === 'offers' || $request->boolean('only_offers');

        $activeCategory = $category ?? $request->get('category', 'All');
        $activeCategorySlug = null;
        $activeParent = null;
        $descendantIds = null;
        if ($activeCategory !== 'All' && $activeCategory !== 'offers') {
            $match = $categories->firstWhere('slug', $activeCategory)
                ?? $categories->first(fn ($c) => strcasecmp($c->name, $activeCategory) === 0);
            if ($match) {
                $activeCategory = $match->name;
                $activeCategorySlug = $match->slug;
                $descendantIds = $this->categorySubtreeIds($categories, $match->id);
                $activeParent = $match->parent_id
                    ? $categories->firstWhere('id', $match->parent_id)
                    : $match;
            } else {
                $activeCategory = 'All';
            }
        } elseif ($activeCategory === 'offers') {
            $activeCategory = 'All';
        }

        $search = trim((string) $request->get('q', ''));
        $sort = $request->get('sort', 'featured');
        if (! in_array($sort, ['featured', 'price_asc', 'price_desc', 'offers', 'name'], true)) {
            $sort = 'featured';
        }

        // Price range filters against the cheapest variant — clamped to the shop bounds.
        // (Parsed here so the canonical redirects below can carry every filter.)
        $minPrice = $request->get('min_price');
        $minPrice = is_numeric($minPrice) && $minPrice >= 0 ? (float) $minPrice : null;
        $maxPrice = $request->get('max_price');
        $maxPrice = is_numeric($maxPrice) && $maxPrice >= 0 ? (float) $maxPrice : null;

        $rawPage = $request->get('page');
        $page = (is_scalar($rawPage) && ctype_digit((string) $rawPage) && (int) $rawPage >= 1) ? (int) $rawPage : 1;

        // Diet + allergen filters (sanitized to known flags/slugs; arrays in the query string).
        $diets = collect((array) $request->get('diet', []))
            ->map(fn ($d) => strtolower(trim((string) $d)))
            ->intersect(['vegetarian', 'vegan', 'halal', 'organic', 'gluten_free'])->values()->all();
        $freeFromInput = (array) $request->get('free_from', []);
        $freeFrom = $freeFromInput !== [] ? Allergen::whereIn('slug', $freeFromInput)->pluck('slug')->all() : [];
        $allergens = Allergen::orderBy('sort_order')->get(['slug', 'name']);

        if ($category === null) {
            $rest = array_filter([
                'q' => $search ?: null,
                'sort' => $sort !== 'featured' ? $sort : null,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'only_offers' => $onlyOffers ? 1 : null,
                'page' => $page > 1 ? $page : null,
                'diet' => $diets !== [] ? $diets : null,
                'free_from' => $freeFrom !== [] ? $freeFrom : null,
            ]);
            if ($activeCategorySlug) {
                return redirect()->route('shop.category', array_merge(['category' => $activeCategorySlug], $rest), 301);
            }
            if ($onlyOffers && $search === '' && $sort === 'featured' && $minPrice === null && $maxPrice === null && $page === 1) {
                return redirect()->route('shop.offers', [], 301);
            }
        }

        // Cheapest-variant price per product — drives the slider bounds and the filter.
        $flashMap = FlashSale::liveMap();
        $bundleCover = BundleOffer::coverMap();
        $bogoLive = BogoOffer::liveAll();
        $withFloors = $products
            ->when($descendantIds, fn ($c) => $c->filter(fn ($p) => in_array($p->category_id, $descendantIds, true))->values())
            ->when($onlyOffers, fn ($c) => $c->filter(fn ($p) => $this->hasDeal($p, $flashMap))->values())
            ->when($diets !== [], fn ($c) => $c->filter(fn ($p) => collect($diets)->every(fn ($d) => (bool) $p->{'is_'.$d}))->values())
            ->when($freeFrom !== [], fn ($c) => $c->filter(fn ($p) => $p->allergens->pluck('slug')->intersect($freeFrom)->isEmpty())->values())
            ->when($search !== '', function ($c) use ($search) {
                $term = mb_strtolower($search);

                return $c->filter(fn ($p) => str_contains(mb_strtolower($p->name.' '.($p->category?->name ?? '').' '.($p->defaultVariant()?->sku ?? '')), $term));
            })
            ->map(fn ($p) => [
                'product' => $p,
                'floor' => $this->dealFloor($p, $flashMap),
            ])
            ->filter(fn ($row) => $row['floor'] !== null)
            ->values();

        $priceFloor = (int) floor($withFloors->min('floor') ?? 0);
        $priceCeil = (int) ceil($withFloors->max('floor') ?? 0);

        // Price range filters against the cheapest variant — clamped to the shop bounds.
        if ($minPrice !== null) {
            $minPrice = max($minPrice, $priceFloor);
        }
        if ($maxPrice !== null) {
            $maxPrice = min($maxPrice, $priceCeil);
        }

        // Sort the light rows first, then build full cards only for the
        // shown slice — previously every product paid for a full card build.
        if ($minPrice !== null || $maxPrice !== null) {
            $withFloors = $withFloors->filter(function ($row) use ($minPrice, $maxPrice) {
                if ($minPrice !== null && $row['floor'] < $minPrice) {
                    return false;
                }
                if ($maxPrice !== null && $row['floor'] > $maxPrice) {
                    return false;
                }

                return true;
            })->values();
        }
        $saveOf = function ($p) use ($flashMap) {
            $d = $p->defaultVariant();
            if (! $d) {
                return 0;
            }
            $selling = $d->sellingPrice();
            $hit = FlashSale::priceFor($d->id, $p->id, $flashMap);
            if ($hit && $hit['price'] < $selling) {
                $selling = $hit['price'];
            }
            $mrp = (float) $d->mrp;

            return ($mrp && $selling < $mrp) ? (int) round((($mrp - $selling) / $mrp) * 100) : 0;
        };
        $sorted = match ($sort) {
            'price_asc' => $withFloors->sortBy(fn ($row) => $row['floor'] ?? PHP_FLOAT_MAX)->values(),
            'price_desc' => $withFloors->sortByDesc(fn ($row) => $row['floor'] ?? 0)->values(),
            'offers' => $withFloors->sortByDesc(fn ($row) => $saveOf($row['product']))->values(),
            'name' => $withFloors->sortBy(fn ($row) => mb_strtolower($row['product']->name))->values(),
            default => $withFloors->sortByDesc(fn ($row) => $row['product']->is_featured)->values(),
        };

        $productsJson = collect();
        $categoriesJson = $categories->map(fn ($c) => $c->name)->values();

        // Paged grid with a Load more button — each page keeps everything above
        // it (?page=2 shows items 1–48), filters reset to page 1, and the
        // button carries every active filter forward via ?page=.
        $perPage = 24;
        $total = $sorted->count();
        $productsJson = $sorted->slice(0, $page * $perPage)->values()
            ->map(fn ($row) => $this->productCard($row['product'], $bundleCover, $flashMap, $bogoLive))->values();
        $shown = $productsJson->count();
        $hasMore = $total > $page * $perPage;

        $categoryPath = ($category !== null && $category !== 'offers') ? $category : null;
        $offersPath = $category === 'offers';
        // Diet chips only render when at least one product actually carries a flag.
        $hasDietFlags = $products->contains(fn ($p) => $p->is_vegetarian || $p->is_vegan || $p->is_halal || $p->is_organic || $p->is_gluten_free);

        return spa('frontend.shop', compact('categories', 'parents', 'activeParent', 'productsJson', 'activeCategory', 'activeCategorySlug', 'categoriesJson', 'search', 'sort', 'onlyOffers', 'minPrice', 'maxPrice', 'priceFloor', 'priceCeil', 'page', 'perPage', 'total', 'shown', 'hasMore', 'categoryPath', 'offersPath', 'diets', 'freeFrom', 'allergens', 'hasDietFlags'));
    }

    /** Offers landing page — the shop with the offers filter pre-selected. */
    public function shopOffers(Request $request)
    {
        return $this->shop($request, 'offers');
    }

    /** Lightweight search index, fetched on demand when the search overlay opens. */
    public function searchCatalog()
    {
        return response()->json(self::searchCatalogData());
    }

    public static function searchCatalogData()
    {
        return Cache::remember('egf_catalog', 3600, function () {
            return Product::with(['category:id,name', 'variants.values.group', 'variants' => fn ($q) => $q->where('status', true)->orderBy('sort_order')])
                ->where('status', true)
                ->orderBy('sort_order')->orderByDesc('id')
                ->get()
                ->map(function ($p) {
                    $v = $p->variants->firstWhere('is_default', true) ?? $p->variants->first();
                    if (! $v) {
                        return null;
                    }

                    $img = $v->image ? url($v->image) : ($p->hero_image ? url($p->hero_image) : url('placeholder.webp'));

                    return [
                        'slug' => $p->slug,
                        'name' => $p->name,
                        'cat' => $p->category?->name ?? '',
                        'tags' => trim($p->name.' '.($p->category?->name ?? '').' '.($v->sku ?? '')),
                        'price' => $v->sellingPrice(),
                        'pack' => $v->combinationLabel() ?? '',
                        'img' => Product::thumb($img, 300),
                        'full' => $img,
                        'variant_id' => $v->id,
                    ];
                })->filter()->values();
        });
    }

    /** Category id plus every descendant id (parents include children's products). */
    protected function categorySubtreeIds($categories, int $rootId): array
    {
        $ids = [$rootId];
        foreach ($categories->where('parent_id', $rootId) as $child) {
            $ids = array_merge($ids, $this->categorySubtreeIds($categories, $child->id));
        }

        return $ids;
    }

    public function productShow($slug)
    {
        $product = Product::with(['category', 'images', 'extraAttributes', 'variants.values.group', 'optionGroups.values', 'category.optionGroups.values', 'allergens'])
            ->where('slug', $slug)
            ->where('status', true)
            ->firstOrFail();

        $seo = $product->seoArray();
        $this->seo(null, $seo['title'], $seo['description'], $seo['keywords'], $this->imgUrl($seo['image']));

        $related = Product::with(['category', 'images', 'variants.values.group'])
            ->withReviewSummary()
            ->where('status', true)
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->orderBy('sort_order')
            ->take(4)
            ->get();
        if ($related->count() < 4) {
            $excludeIds = $related->pluck('id')->push($product->id)->values();
            $filler = Product::with(['category', 'images', 'variants.values.group'])
                ->withReviewSummary()
                ->where('status', true)
                ->whereNotIn('id', $excludeIds)
                ->inRandomOrder()
                ->take(4 - $related->count())
                ->get();
            $related = $related->concat($filler)->values();
        }

        $relMaps = [BundleOffer::coverMap(), FlashSale::liveMap(), BogoOffer::liveAll()];
        $productJson = $this->productDetail($product, ...$relMaps);
        $reviewStats = ProductReview::approved()->where('product_id', $product->id)
            ->selectRaw('COUNT(*) as count, AVG(rating) as avg')
            ->first();
        $productJson['ratingAvg'] = $reviewStats && $reviewStats->count > 0 ? round((float) $reviewStats->avg, 1) : null;
        $productJson['ratingCount'] = (int) ($reviewStats->count ?? 0);
        $reviewsJson = ProductReview::approved()->with('user:id,name')
            ->where('product_id', $product->id)
            ->latest()
            ->take(20)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'rating' => $r->rating,
                'title' => $r->title,
                'body' => $r->body,
                'author' => $r->user?->name ?? 'Shopper',
                'date' => $r->created_at->format('j M Y'),
                'mine' => auth()->id() !== null && $r->user_id === auth()->id(),
            ])->values();
        $myReview = auth()->check()
            ? ProductReview::where('product_id', $product->id)->where('user_id', auth()->id())->first()
            : null;
        $optionsJson = [
            'config' => [],
            'finish' => [],
            'glazing' => [],
            'upgrade' => [],
        ];
        $zonesJson = collect();
        $relatedJson = $related->map(fn ($p) => $this->productCard($p, ...$relMaps))->values();
        $faqsJson = $this->faqsJson(4);
        $docsJson = collect();
        $videoUrl = null;
        $videoEmbed = null;

        return spa('frontend.details', compact('product', 'productJson', 'optionsJson', 'zonesJson', 'relatedJson', 'faqsJson', 'docsJson', 'videoUrl', 'videoEmbed', 'reviewsJson', 'myReview'));
    }

    public function about()
    {
        $this->seo('about');

        return spa('frontend.about');
    }

    public function contact()
    {
        $this->seo('contact');

        return spa('frontend.contact');
    }

    public function contactStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'topic' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:50',
            'message' => 'required|string|max:5000',
        ]);

        Contact::create([
            ...$data,
            'subject' => $data['topic'] ?? 'Website Enquiry',
        ]);

        return response()->json(['success' => true, 'message' => 'Message received. The shop will reply within one working day.']);
    }

    public function refund()
    {
        $this->seo('refund');

        return spa('frontend.refund');
    }

    /** Public loyalty explainer — live rates from Shop Settings, balance when signed in. */
    public function loyalty()
    {
        $this->seo('loyalty');

        $rates = [
            'perPound' => UserPoint::perPound(),
            'value' => UserPoint::value(),
            'minRedeem' => UserPoint::minRedeem(),
        ];
        $balance = auth()->check() ? UserPoint::balance(auth()->id()) : null;

        return spa('frontend.loyalty', compact('rates', 'balance'));
    }

    /** Delivery info — live fees, minimums and bookable windows, no checkout required. */
    public function delivery()
    {
        $this->seo('delivery');

        $minOrder = Setting::money('delivery_min_order', 15.00);
        $freeOver = Setting::money('delivery_free_over', 50.00);
        $slots = DeliverySlot::ordered();

        return spa('frontend.delivery', compact('minOrder', 'freeOver', 'slots'));
    }

    public function bag()
    {
        $this->seo('cart');

        $bag = BagController::detailed();

        return spa('frontend.bag', compact('bag'));
    }

    public function checkout()
    {
        $this->seo('checkout');

        BagController::reconcile();
        $bag = BagController::detailed();
        $slots = DeliverySlot::ordered();
        $dates = DeliverySlot::bookableDates();
        $minOrder = Setting::money('delivery_min_order', 15.00);
        $freeOver = Setting::money('delivery_free_over', 50.00);
        $stripeOn = CheckoutController::stripeConfigured();
        $paypalOn = CheckoutController::paypalConfigured();
        $paypalClient = CheckoutController::paypalClientId();
        $shopper = auth()->user();
        $pointsBalance = $shopper ? UserPoint::balance($shopper->id) : 0;
        $pointsValue = UserPoint::value();
        $pointsMin = UserPoint::minRedeem();
        $addresses = collect();
        $defaultDelivery = null;
        $defaultBilling = null;
        if ($shopper) {
            $shopper->ensureAddressBook();
            $addresses = $shopper->addresses()->get();
            $defaultDelivery = $shopper->defaultDeliveryAddress();
            $defaultBilling = $shopper->defaultBillingAddress();
        }
        $coAddressBook = $addresses->mapWithKeys(fn ($a) => [
            $a->id => $a->only(['name', 'phone', 'address', 'city', 'postcode']),
        ])->all();

        return spa('frontend.checkout', compact('bag', 'slots', 'dates', 'minOrder', 'freeOver', 'stripeOn', 'paypalOn', 'paypalClient', 'shopper', 'pointsBalance', 'pointsValue', 'pointsMin', 'addresses', 'defaultDelivery', 'defaultBilling', 'coAddressBook'));
    }

    /** Guest order tracking: order number + the phone given at checkout. */
    public function track()
    {
        $this->seo('track');

        return spa('frontend.track', ['order' => null]);
    }

    public function trackLookup(Request $request)
    {
        $this->seo('track');

        $data = $request->validate([
            'number' => 'required|string|max:30',
            'phone' => 'required|string|max:30',
        ]);

        $number = strtoupper(trim($data['number']));
        // Normalize UK mobiles: strip separators, treat +44 as a leading 0.
        $normalizePhone = fn ($p) => preg_replace('/^\+44/', '0', (string) preg_replace('/[\s\-()]/', '', $p));
        $phone = $normalizePhone($data['phone']);

        $order = Order::with(['items', 'histories', 'status'])
            ->where('number', $number)
            ->get()
            ->first(fn ($o) => $normalizePhone($o->phone) === $phone);

        if (! $order) {
            return spa('frontend.track', ['order' => null])->withErrors([
                'number' => 'We could not find that order — check the number and the phone used at checkout.',
            ])->withInput($request->only('number'));
        }

        return spa('frontend.track', compact('order'));
    }

    public function faq()
    {
        $this->seo('faq');
        $faqsJson = $this->faqsJson();
        $faqCatsJson = $this->faqCatsJson();

        return spa('frontend.faq', compact('faqsJson', 'faqCatsJson'));
    }

    public function gallery()
    {
        $this->seo('gallery');

        $galleryJson = $this->galleryJson();
        $galleryCatsJson = $this->galleryCatsJson();

        return spa('frontend.gallery', compact('galleryJson', 'galleryCatsJson'));
    }

    public function privacy()
    {
        $this->seo('privacy');

        return spa('frontend.privacy');
    }

    public function terms()
    {
        $this->seo('terms');

        return spa('frontend.terms');
    }

    protected function imgUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return url($path);
    }

    protected function heroFor(Product $p): string
    {
        return $this->imgUrl($p->hero_image)
            ?? $this->imgUrl($p->category?->image)
            ?? url(self::DEFAULT_IMAGE);
    }

    private function priceFor(Product $p): string
    {
        return $p->priceRange() ?? 'On request';
    }

    /** A product counts as an offer when any variant has an offer price or a live flash below mrp. */
    protected function hasDeal(Product $p, array $flashMap): bool
    {
        foreach ($p->variants as $v) {
            if (! $v->status) {
                continue;
            }
            if ($v->offer_price !== null && (float) $v->offer_price < (float) $v->mrp) {
                return true;
            }
            $hit = FlashSale::priceFor($v->id, $p->id, $flashMap);
            if ($hit && $hit['price'] < (float) $v->mrp) {
                return true;
            }
        }

        return false;
    }

    /** Cheapest variant price with live flash applied. */
    protected function dealFloor(Product $p, array $flashMap): ?float
    {
        $prices = $p->variants->where('status', true)->map(function ($v) use ($p, $flashMap) {
            $price = $v->sellingPrice();
            $hit = FlashSale::priceFor($v->id, $p->id, $flashMap);
            if ($hit && $hit['price'] < $price) {
                $price = $hit['price'];
            }

            return $price;
        })->filter(fn ($s) => $s !== null);

        return $prices->isNotEmpty() ? $prices->min() : null;
    }

    /** Card shape used by shop grid, featured rail, offers rail, related. */
    public function productCard(Product $p, ?array $bundleCover = null, ?array $flashMap = null, $bogoLive = null): array
    {
        $gallery = $p->relationLoaded('images')
            ? $p->images->map(fn ($i) => $this->imgUrl($i->image))->values()->all()
            : [];

        $default = $p->defaultVariant();
        $selling = $default?->sellingPrice();
        $flashMap = $flashMap ?? FlashSale::liveMap();
        $flash = $default ? FlashSale::priceFor($default->id, $p->id, $flashMap) : null;
        if ($flash && $flash['price'] < $selling) {
            $selling = $flash['price'];
        } else {
            $flash = null;
        }
        $mrp = $default !== null ? (float) $default->mrp : null;
        $savePct = ($default && $selling !== null && $mrp && $selling < $mrp)
            ? (int) round((($mrp - $selling) / $mrp) * 100)
            : null;
        $bogo = $default ? BogoOffer::matchIn($bogoLive ?? BogoOffer::liveAll(), $default->id, $p->id) : null;
        $cover = $bundleCover ?? BundleOffer::coverMap();
        $bundle = $default ? ($cover[$default->id] ?? null) : null;

        return [
            'id' => $p->id,
            'slug' => $p->slug,
            'modelCode' => $default?->sku,
            'name' => $p->name,
            'category' => $p->category?->name ?? 'Collection',
            'categorySlug' => $p->category?->slug,
            'discipline' => $p->category?->name ?? 'Collection',
            'tagline' => $p->tagline,
            'subtitle' => $p->tagline,
            'price' => $this->priceFor($p),
            'leadTime' => null,
            'heroImage' => $this->heroFor($p),
            'images' => $gallery,
            'dimensions' => null,
            'warranty' => null,
            // Storefront card data (additive; existing keys untouched).
            'variantId' => $default?->id,
            'packLabel' => $default?->combinationLabel() ?? '',
            'priceNum' => $selling,
            'oldNum' => ($savePct ? $mrp : null),
            'savePct' => $savePct,
            'inStock' => $default ? (bool) $default->in_stock : false,
            'isFeatured' => (bool) $p->is_featured,
            'ratingAvg' => $p->reviews_avg_rating !== null ? round((float) $p->reviews_avg_rating, 1) : null,
            'ratingCount' => (int) ($p->reviews_count ?? 0),
            'bogo' => $bogo ? [
                'buy' => $bogo->buy_qty,
                'free' => $bogo->free_qty,
                'label' => $bogo->label(),
            ] : null,
            'bundle' => $bundle ? [
                'label' => $bundle->label(),
                'name' => $bundle->name,
            ] : null,
            'diets' => $p->dietBadges(),
            'flashEnds' => $flash ? $flash['ends']->format('D j M, H:i') : null,
            'favourited' => Favourite::isFavourited(auth()->id(), $p->id),
            'url' => route('product.show', $p->slug),
            'imgAbs' => $this->imgUrl($this->heroFor($p)),
            'imgThumb' => Product::thumb($this->imgUrl($this->heroFor($p)), 300),
            'cardVariants' => $p->relationLoaded('variants')
                ? $p->variants->where('status', true)->sortBy('sort_order')->values()->map(function ($v) use ($p, $flashMap) {
                    $price = $v->sellingPrice();
                    $hit = FlashSale::priceFor($v->id, $p->id, $flashMap);
                    if ($hit && $hit['price'] < $price) {
                        $price = $hit['price'];
                    }

                    return [
                        'id' => $v->id,
                        'label' => $v->combinationLabel() ?: ($v->sku ?? 'Standard'),
                        'selling' => $price,
                        'mrp' => (float) $v->mrp,
                        'in_stock' => (bool) $v->in_stock,
                        'image' => $this->imgUrl($v->image),
                    ];
                })->all()
                : [],
        ];
    }

    /** Full shape for the details page JS. */
    protected function productDetail(Product $p, ?array $bundleCover = null, ?array $flashMap = null, $bogoLive = null): array
    {
        $flashMap = $flashMap ?? FlashSale::liveMap();

        return [
            ...$this->productCard($p, $bundleCover, $flashMap, $bogoLive),
            'description' => $p->description,
            'highlights' => $p->highlightList(),
            'extraAttributes' => $p->extraAttributes->map(fn ($a) => [
                'label' => $a->label, 'value' => $a->value,
            ])->values()->all(),
            'video' => null,
            'videoEmbed' => null,
            'model3d' => null,
            'show3d' => false,
            'gallery' => $p->images->map(fn ($i) => [
                'src' => Product::thumb($this->imgUrl($i->image), 600), 'full' => $this->imgUrl($i->image), 'caption' => $i->caption,
            ])->values()->all(),
            'optionGroups' => $p->effectiveOptionGroups()->map(fn ($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'slug' => $g->slug,
                'type' => $g->type,
                'values' => $g->values->where('status', true)->values()->map(fn ($v) => [
                    'id' => $v->id, 'label' => $v->label, 'slug' => $v->slug,
                ])->all(),
            ])->values()->all(),
            'variants' => $p->activeVariants->map(function ($v) use ($p, $flashMap) {
                $price = $v->sellingPrice();
                $hit = FlashSale::priceFor($v->id, $p->id, $flashMap);
                $flashEnds = null;
                if ($hit && $hit['price'] < $price) {
                    $price = $hit['price'];
                    $flashEnds = $hit['ends']->format('D j M, H:i');
                }

                return [
                    'id' => $v->id,
                    'sku' => $v->sku,
                    'mrp' => (float) $v->mrp,
                    'offer_price' => $v->offer_price === null ? null : (float) $v->offer_price,
                    'selling' => $price,
                    'flash_ends' => $flashEnds,
                    'in_stock' => (bool) $v->in_stock,
                    'is_default' => (bool) $v->is_default,
                    'image' => $this->imgUrl($v->image),
                    'values' => $v->values->map(fn ($val) => [
                        'group' => $val->group->slug, 'value' => $val->slug, 'label' => $val->label,
                    ])->values()->all(),
                ];
            })->values()->all(),
            'bogoOffers' => collect($bogoLive ?? BogoOffer::liveAll())->where('product_id', $p->id)->map(fn ($o) => [
                'buy' => $o->buy_qty,
                'free' => $o->free_qty,
                'label' => $o->label(),
                'variant' => $o->product_variant_id
                    ? ($p->variants->firstWhere('id', $o->product_variant_id)?->combinationLabel() ?? '')
                    : 'All packs',
            ])->values()->all(),
            'flashOffers' => collect(FlashSale::liveAll())->where('product_id', $p->id)->map(fn ($s) => [
                'price' => (float) $s->promo_price,
                'ends' => $s->ends_at->format('D j M, H:i'),
                'variant' => $s->product_variant_id
                    ? ($p->variants->firstWhere('id', $s->product_variant_id)?->combinationLabel() ?? '')
                    : 'All packs',
            ])->values()->all(),
            'bundleOffers' => $this->bundleOffersFor($p, $bundleCover),
            'diets' => $p->dietBadges(),
            'origin' => $p->origin_country,
            'allergens' => $p->allergens->pluck('name')->all(),
            'nutritionPer' => $p->nutrition_per,
            'nutrition' => [
                'Energy (kcal)' => $p->energy_kcal !== null ? (float) $p->energy_kcal : null,
                'Fat' => $p->fat_g !== null ? (float) $p->fat_g : null,
                'Saturates' => $p->saturates_g !== null ? (float) $p->saturates_g : null,
                'Carbs' => $p->carbs_g !== null ? (float) $p->carbs_g : null,
                'Sugars' => $p->sugars_g !== null ? (float) $p->sugars_g : null,
                'Fibre' => $p->fibre_g !== null ? (float) $p->fibre_g : null,
                'Protein' => $p->protein_g !== null ? (float) $p->protein_g : null,
                'Salt' => $p->salt_g !== null ? (float) $p->salt_g : null,
            ],
        ];
    }

    /** Bundle pools covering this product, with a few mix-and-match partners. */
    private function bundleOffersFor(Product $p, ?array $bundleCover = null): array
    {
        $cover = $bundleCover ?? BundleOffer::coverMap();
        $found = [];
        foreach ($p->variants as $v) {
            $b = $cover[$v->id] ?? null;
            if ($b && ! isset($found[$b->id])) {
                $found[$b->id] = $b;
            }
        }
        $out = [];
        foreach ($found as $bundle) {
            $poolProductIds = ProductVariant::whereIn('id', $bundle->poolVariantIds())
                ->where('product_id', '!=', $p->id)
                ->distinct()->pluck('product_id')->take(6)->all();
            $others = Product::with('variants')
                ->whereIn('id', $poolProductIds)->where('status', true)
                ->get()->map(fn ($op) => [
                    'name' => $op->name,
                    'url' => route('product.show', $op->slug),
                    'image' => $this->imgUrl($this->heroFor($op)),
                    'price' => $op->priceRange() ?? '',
                ])->all();
            $out[] = [
                'label' => $bundle->label(),
                'name' => $bundle->name,
                'others' => $others,
            ];
        }

        return $out;
    }

    /** Convert a YouTube/Vimeo page URL into its player embed URL; null for direct files. */
    private function videoEmbedUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        if (preg_match('~(?:youtube\.com/(?:watch\?[^#]*v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,})~', $url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1];
        }
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return null;
    }

    protected function faqsJson(?int $limit = null)
    {
        $q = Faq::with('category')->where('status', true)->orderBy('sort_order');
        if ($limit) {
            $q->limit($limit);
        }

        return $q->get()->map(fn ($f) => [
            'id' => 'faq-'.$f->id,
            'cat' => $f->category?->slug ?? 'general',
            'badge' => $f->badge ?? $f->category?->name ?? 'FAQ',
            'q' => $f->question,
            'a' => $f->answer,
        ])->values();
    }

    protected function faqCatsJson()
    {
        return FaqCategory::where('status', true)->orderBy('sort_order')
            ->pluck('name', 'slug')->all();
    }

    protected function galleryJson(?int $limit = null)
    {
        $q = Gallery::with('category')->where('status', true)->orderBy('sort_order');
        if ($limit) {
            $q->limit($limit);
        }

        return $q->get()->map(function ($g) {
            $unsplashId = null;
            if (preg_match('#images\.unsplash\.com/(photo-[A-Za-z0-9-]+)#', $g->image ?? '', $m)) {
                $unsplashId = $m[1];
            }

            return [
                'id' => $unsplashId ?? ('db-'.$g->id),
                'src' => $this->imgUrl($g->image),
                'cat' => $g->category?->slug ?? 'general',
                'catLabel' => $g->category?->name ?? 'Gallery',
                'caption' => $g->caption ?? '',
            ];
        })->values();
    }

    protected function galleryCatsJson()
    {
        return GalleryCategory::where('status', true)->orderBy('sort_order')
            ->pluck('name', 'slug')->all();
    }

    private function seo($pageKey = null, $title = null, $description = null, $keywords = null, $image = null)
    {
        $company = CompanyDetails::cached();
        $pageSeo = $pageKey ? PageSeo::where('page_key', $pageKey)->first() : null;

        $title = $title ?: ($pageSeo?->meta_title ?: $company?->meta_title);
        $description = $description ?: ($pageSeo?->meta_description ?: $company?->meta_description);
        $keywords = $keywords ?: ($pageSeo?->meta_keywords ?: $company?->meta_keywords);
        $image = $image ?: ($pageSeo?->meta_image
            ? $this->imgUrl($pageSeo->meta_image)
            : ($company?->meta_image ? asset('uploads/company/'.$company->meta_image) : null));

        if ($title) {
            SEOMeta::setTitle($title);
            OpenGraph::setTitle($title);
            Twitter::setTitle($title);
        }
        if ($description) {
            SEOMeta::setDescription($description);
            OpenGraph::setDescription($description);
            Twitter::setDescription($description);
        }
        if ($keywords) {
            SEOMeta::setKeywords($keywords);
        }
        if ($image) {
            OpenGraph::addImage($image);
            Twitter::setImage($image);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CompanyDetails;
use App\Models\Contact;
use App\Models\DeliverySlot;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\Favourite;
use App\Models\Gallery;
use App\Models\GalleryCategory;
use App\Models\Order;
use App\Models\PageSeo;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Setting;
use App\Models\Slider;
use App\Models\UserPoint;
use Illuminate\Http\Request;
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

        $productsJson = $products->map(fn ($p) => $this->productCard($p))->values();
        $featuredJson = $featured->map(fn ($p) => $this->productCard($p))->values();
        $featuredCards = $products->where('is_featured', true)->values()->map(fn ($p) => $this->productCard($p))->values();
        // Homepage "Shop by category" shows parents only, in parent-scoped sort_order.
        $categoriesJson = $categories->whereNull('parent_id')->values()->map(function ($c) use ($products, $categories) {
            $ids = $this->categorySubtreeIds($categories, $c->id);

            return [
                'name' => $c->name,
                'slug' => $c->slug,
                'image' => $c->image ? url($c->image) : asset('placeholder.webp'),
                'count' => $products->whereIn('category_id', $ids)->count(),
            ];
        })->values();
        $offerCards = $products->filter(fn ($p) => collect($p->variants)->contains(fn ($v) => $v->status && $v->offer_price !== null && (float) $v->offer_price < (float) $v->mrp))
            ->take(4)->values()->map(fn ($p) => $this->productCard($p))->values();
        $faqsJson = $this->faqsJson();
        $faqCatsJson = $this->faqCatsJson();
        $galleryJson = $this->galleryJson(8);
        $galleryCatsJson = $this->galleryCatsJson();
        $filesJson = collect();
        $zonesJson = collect();
        $slidersJson = Slider::where('is_active', true)->orderBy('sort_order')->orderBy('id')
            ->get(['badge', 'title', 'subtitle', 'btn_text', 'btn_url', 'btn_text2', 'btn_url2', 'image'])
            ->map(fn ($s) => [...$s->toArray(), 'image' => $s->image ? url($s->image) : url('placeholder.webp')])
            ->values();

        return spa('frontend.index', compact('productsJson', 'featuredJson', 'featuredCards', 'categoriesJson', 'offerCards', 'faqsJson', 'faqCatsJson', 'galleryJson', 'galleryCatsJson', 'filesJson', 'zonesJson', 'slidersJson'));
    }

    public function shop(Request $request, ?string $category = null)
    {
        $this->seo('shop');

        $categories = Category::where('status', true)->orderBy('sort_order')->orderBy('id')->get();
        $products = Product::with(['category', 'images', 'variants.values.group'])
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

        if ($category === null) {
            $rest = array_filter([
                'q' => $search ?: null,
                'sort' => $sort !== 'featured' ? $sort : null,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'only_offers' => $onlyOffers ? 1 : null,
                'page' => $page > 1 ? $page : null,
            ]);
            if ($activeCategorySlug) {
                return redirect()->route('shop.category', array_merge(['category' => $activeCategorySlug], $rest), 301);
            }
            if ($onlyOffers && $search === '' && $sort === 'featured' && $minPrice === null && $maxPrice === null && $page === 1) {
                return redirect()->route('shop.offers', [], 301);
            }
        }

        // Cheapest-variant price per product — drives the slider bounds and the filter.
        $withFloors = $products
            ->when($descendantIds, fn ($c) => $c->filter(fn ($p) => in_array($p->category_id, $descendantIds, true))->values())
            ->when($onlyOffers, fn ($c) => $c->filter(fn ($p) => $p->variants->contains(fn ($v) => $v->status && $v->offer_price !== null && (float) $v->offer_price < (float) $v->mrp))->values())
            ->when($search !== '', function ($c) use ($search) {
                $term = mb_strtolower($search);

                return $c->filter(fn ($p) => str_contains(mb_strtolower($p->name.' '.($p->category?->name ?? '').' '.($p->defaultVariant()?->sku ?? '')), $term));
            })
            ->map(fn ($p) => [
                'product' => $p,
                'floor' => $p->variants->where('status', true)
                    ->map(fn ($v) => $v->sellingPrice())
                    ->filter(fn ($s) => $s !== null)
                    ->min(),
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

        $cards = $withFloors
            ->when($minPrice !== null || $maxPrice !== null, function ($c) use ($minPrice, $maxPrice) {
                return $c->filter(function ($row) use ($minPrice, $maxPrice) {
                    if ($minPrice !== null && $row['floor'] < $minPrice) {
                        return false;
                    }
                    if ($maxPrice !== null && $row['floor'] > $maxPrice) {
                        return false;
                    }

                    return true;
                })->values();
            })
            ->map(fn ($row) => $this->productCard($row['product']))
            ->values();

        $cards = match ($sort) {
            'price_asc' => $cards->sortBy(fn ($p) => $p['priceNum'] ?? PHP_FLOAT_MAX)->values(),
            'price_desc' => $cards->sortByDesc(fn ($p) => $p['priceNum'] ?? 0)->values(),
            'offers' => $cards->sortByDesc(fn ($p) => $p['savePct'] ?? 0)->values(),
            'name' => $cards->sortBy(fn ($p) => mb_strtolower($p['name']))->values(),
            default => $cards->sortByDesc(fn ($p) => $p['isFeatured'])->values(),
        };

        $productsJson = $cards;
        $categoriesJson = $categories->map(fn ($c) => $c->name)->values();

        // Paged grid with a Load more button — each page keeps everything above
        // it (?page=2 shows items 1–48), filters reset to page 1, and the
        // button carries every active filter forward via ?page=.
        $perPage = 24;
        $total = $cards->count();
        $productsJson = $cards->slice(0, $page * $perPage)->values();
        $shown = $productsJson->count();
        $hasMore = $total > $page * $perPage;

        $categoryPath = ($category !== null && $category !== 'offers') ? $category : null;
        $offersPath = $category === 'offers';

        return spa('frontend.shop', compact('categories', 'parents', 'activeParent', 'productsJson', 'activeCategory', 'activeCategorySlug', 'categoriesJson', 'search', 'sort', 'onlyOffers', 'minPrice', 'maxPrice', 'priceFloor', 'priceCeil', 'page', 'perPage', 'total', 'shown', 'hasMore', 'categoryPath', 'offersPath'));
    }

    /** Offers landing page — the shop with the offers filter pre-selected. */
    public function shopOffers(Request $request)
    {
        return $this->shop($request, 'offers');
    }

    /** Category id plus every descendant id (parents include children's products). */
    private function categorySubtreeIds($categories, int $rootId): array
    {
        $ids = [$rootId];
        foreach ($categories->where('parent_id', $rootId) as $child) {
            $ids = array_merge($ids, $this->categorySubtreeIds($categories, $child->id));
        }

        return $ids;
    }

    public function productShow($slug)
    {
        $product = Product::with(['category', 'images', 'extraAttributes', 'variants.values.group', 'optionGroups.values', 'category.optionGroups.values'])
            ->where('slug', $slug)
            ->where('status', true)
            ->firstOrFail();

        $seo = $product->seoArray();
        $this->seo(null, $seo['title'], $seo['description'], $seo['keywords'], $this->imgUrl($seo['image']));

        $related = Product::with('category')
            ->withReviewSummary()
            ->where('status', true)
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->orderBy('sort_order')
            ->take(4)
            ->get();
        if ($related->count() < 4) {
            $excludeIds = $related->pluck('id')->push($product->id)->values();
            $filler = Product::with('category')
                ->withReviewSummary()
                ->where('status', true)
                ->whereNotIn('id', $excludeIds)
                ->inRandomOrder()
                ->take(4 - $related->count())
                ->get();
            $related = $related->concat($filler)->values();
        }

        $productJson = $this->productDetail($product);
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
        $relatedJson = $related->map(fn ($p) => $this->productCard($p))->values();
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
            'message' => 'required|string',
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
        $phone = preg_replace('/[\s\-()]/', '', $data['phone']);

        $order = Order::with(['items', 'histories', 'status'])
            ->where('number', $number)
            ->get()
            ->first(fn ($o) => preg_replace('/[\s\-()]/', '', (string) $o->phone) === $phone);

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

    private function imgUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return url($path);
    }

    private function heroFor(Product $p): string
    {
        return $this->imgUrl($p->hero_image)
            ?? $this->imgUrl($p->category?->image)
            ?? url(self::DEFAULT_IMAGE);
    }

    private function priceFor(Product $p): string
    {
        return $p->priceRange() ?? 'On request';
    }

    /** Card shape used by shop grid, featured rail, offers rail, related. */
    public function productCard(Product $p): array
    {
        $gallery = $p->relationLoaded('images')
            ? $p->images->map(fn ($i) => $this->imgUrl($i->image))->values()->all()
            : [];

        $default = $p->defaultVariant();
        $selling = $default?->sellingPrice();
        $mrp = $default !== null ? (float) $default->mrp : null;
        $savePct = ($default && $selling !== null && $mrp && $selling < $mrp)
            ? (int) round((($mrp - $selling) / $mrp) * 100)
            : null;

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
            'favourited' => Favourite::isFavourited(auth()->id(), $p->id),
            'url' => route('product.show', $p->slug),
            'imgAbs' => $this->imgUrl($this->heroFor($p)),
            'cardVariants' => $p->relationLoaded('variants')
                ? $p->variants->where('status', true)->sortBy('sort_order')->values()->map(fn ($v) => [
                    'id' => $v->id,
                    'label' => $v->combinationLabel() ?: ($v->sku ?? 'Standard'),
                    'selling' => $v->sellingPrice(),
                    'mrp' => (float) $v->mrp,
                    'in_stock' => (bool) $v->in_stock,
                    'image' => $this->imgUrl($v->image),
                ])->all()
                : [],
        ];
    }

    /** Full shape for the details page JS. */
    private function productDetail(Product $p): array
    {
        return [
            ...$this->productCard($p),
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
                'src' => $this->imgUrl($i->image), 'caption' => $i->caption,
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
            'variants' => $p->activeVariants->map(fn ($v) => [
                'id' => $v->id,
                'sku' => $v->sku,
                'mrp' => (float) $v->mrp,
                'offer_price' => $v->offer_price === null ? null : (float) $v->offer_price,
                'selling' => $v->sellingPrice(),
                'in_stock' => (bool) $v->in_stock,
                'is_default' => (bool) $v->is_default,
                'image' => $this->imgUrl($v->image),
                'values' => $v->values->map(fn ($val) => [
                    'group' => $val->group->slug, 'value' => $val->slug, 'label' => $val->label,
                ])->values()->all(),
            ])->values()->all(),
        ];
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

    private function faqsJson(?int $limit = null)
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

    private function faqCatsJson()
    {
        return FaqCategory::where('status', true)->orderBy('sort_order')
            ->pluck('name', 'slug')->all();
    }

    private function galleryJson(?int $limit = null)
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

    private function galleryCatsJson()
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

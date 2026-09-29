<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CompanyDetails;
use App\Models\Contact;
use App\Models\DeliverySlot;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\Gallery;
use App\Models\GalleryCategory;
use App\Models\PageSeo;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Slider;
use Illuminate\Http\Request;
use OpenGraph;
use SEOMeta;
use Twitter;

class FrontendController extends Controller
{
    /** Static Unsplash fallbacks keyed by category slug (raw design imagery). */
    public const FALLBACK_IMAGES = [
        'kitchen' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=85',
        'bath-wellness' => 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=1200&q=85',
        'sculptural-lighting' => 'https://images.unsplash.com/photo-1513506003901-1e6a229e2d15?auto=format&fit=crop&w=1200&q=85',
        'architectural-joinery' => 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=1200&q=85',
        'hardware-surfaces' => 'https://images.unsplash.com/photo-1558211553-d9326f10c561?auto=format&fit=crop&w=1200&q=85',
        'expandable-homes' => 'https://images.unsplash.com/photo-1518780664697-55e3ad937233?auto=format&fit=crop&w=1200&q=85',
    ];

    public const DEFAULT_IMAGE = 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=85';

    public function index()
    {
        $this->seo('home');

        $products = Product::with(['category', 'images', 'variants.values.group'])
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
        $featured = $products->where('is_featured', true)->values();
        if ($featured->isEmpty()) {
            $featured = $products->take(4)->values();
        }
        $categories = Category::where('status', true)->orderBy('sort_order')->get();

        $productsJson = $products->map(fn ($p) => $this->productCard($p))->values();
        $featuredJson = $featured->map(fn ($p) => $this->productCard($p))->values();
        $featuredCards = $products->where('is_featured', true)->values()->map(fn ($p) => $this->productCard($p))->values();
        $tileFallbacks = ['cat-veg', 'cat-fruit', 'cat-bakery', 'cat-dairy', 'cat-seafood', 'cat-pantry'];
        $categoriesJson = $categories->values()->map(fn ($c, $i) => [
            'name' => $c->name,
            'slug' => $c->slug,
            'image' => $c->image ? url($c->image) : asset('frontend-raw/assets/images/'.$tileFallbacks[$i % count($tileFallbacks)].'.jpg'),
            'count' => $products->where('category_id', $c->id)->count(),
        ])->values();
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

    public function collections(Request $request)
    {
        $this->seo('collections');

        $categories = Category::where('status', true)->orderBy('sort_order')->get();
        $products = Product::with(['category', 'images', 'variants.values.group'])
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        // Header/footer link with ?category=slug — resolved to the category name.
        $activeCategory = $request->get('category', 'All');
        $activeCategorySlug = null;
        if ($activeCategory !== 'All') {
            $match = $categories->firstWhere('slug', $activeCategory)
                ?? $categories->first(fn ($c) => strcasecmp($c->name, $activeCategory) === 0);
            $activeCategory = $match?->name ?? 'All';
            $activeCategorySlug = $match?->slug;
        }

        $search = trim((string) $request->get('q', ''));
        $sort = $request->get('sort', 'featured');
        if (! in_array($sort, ['featured', 'price_asc', 'price_desc', 'offers', 'name'], true)) {
            $sort = 'featured';
        }

        $cards = $products
            ->when($activeCategorySlug, fn ($c) => $c->filter(fn ($p) => $p->category?->slug === $activeCategorySlug)->values())
            ->when($search !== '', function ($c) use ($search) {
                $term = mb_strtolower($search);

                return $c->filter(fn ($p) => str_contains(mb_strtolower($p->name.' '.($p->category?->name ?? '').' '.($p->defaultVariant()?->sku ?? '')), $term));
            })
            ->map(fn ($p) => $this->productCard($p))
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

        return spa('frontend.collections', compact('categories', 'productsJson', 'activeCategory', 'activeCategorySlug', 'categoriesJson', 'search', 'sort'));
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
            ->where('status', true)
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->orderBy('sort_order')
            ->take(4)
            ->get();
        if ($related->count() < 4) {
            $excludeIds = $related->pluck('id')->push($product->id)->values();
            $filler = Product::with('category')
                ->where('status', true)
                ->whereNotIn('id', $excludeIds)
                ->inRandomOrder()
                ->take(4 - $related->count())
                ->get();
            $related = $related->concat($filler)->values();
        }

        $productJson = $this->productDetail($product);
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

        return spa('frontend.details', compact('product', 'productJson', 'optionsJson', 'zonesJson', 'relatedJson', 'faqsJson', 'docsJson', 'videoUrl', 'videoEmbed'));
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

        return response()->json(['success' => true, 'message' => 'Enquiry received. The studio will reply within one working day.']);
    }

    public function offers()
    {
        $this->seo('offers');

        $products = Product::with(['category', 'images', 'variants.values.group'])
            ->where('status', true)
            ->whereHas('variants', fn ($q) => $q->where('status', true)
                ->whereNotNull('offer_price')
                ->whereColumn('offer_price', '<', 'mrp'))
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        $offerCards = $products->map(fn ($p) => $this->productCard($p))
            ->sortByDesc(fn ($p) => $p['savePct'] ?? 0)->values();

        return spa('frontend.offers', compact('offerCards'));
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

        return spa('frontend.checkout', compact('bag', 'slots', 'dates', 'minOrder', 'freeOver', 'stripeOn', 'paypalOn', 'paypalClient', 'shopper'));
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
            ?? self::FALLBACK_IMAGES[$p->category?->slug ?? ''] ?? self::DEFAULT_IMAGE;
    }

    private function priceFor(Product $p): string
    {
        return $p->priceRange() ?? 'On request';
    }

    /** Card shape used by collections grid, featured rail, configurator, related. */
    private function productCard(Product $p): array
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

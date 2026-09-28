<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CompanyDetails;
use App\Models\Contact;
use App\Models\Download;
use App\Models\Enquiry;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\FloorZone;
use App\Models\Gallery;
use App\Models\GalleryCategory;
use App\Models\PageSeo;
use App\Models\Product;
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

        $products = Product::with(['category', 'options'])
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
        $categoriesJson = $categories->map(fn ($c) => ['name' => $c->name, 'slug' => $c->slug])->values();
        $faqsJson = $this->faqsJson();
        $faqCatsJson = $this->faqCatsJson();
        $galleryJson = $this->galleryJson(8);
        $galleryCatsJson = $this->galleryCatsJson();
        $filesJson = $this->filesJson(6);
        $zonesJson = $this->zonesJson();

        return spa('frontend.index', compact('productsJson', 'featuredJson', 'featuredCards', 'categoriesJson', 'faqsJson', 'faqCatsJson', 'galleryJson', 'galleryCatsJson', 'filesJson', 'zonesJson'));
    }

    public function collections(Request $request)
    {
        $this->seo('collections');

        $categories = Category::where('status', true)->orderBy('sort_order')->get();
        $products = Product::with(['category', 'options'])
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        // Header/footer link with ?category=slug — JS filters by category name.
        $activeCategory = $request->get('category', 'All');
        if ($activeCategory !== 'All') {
            $match = $categories->firstWhere('slug', $activeCategory)
                ?? $categories->first(fn ($c) => strcasecmp($c->name, $activeCategory) === 0);
            $activeCategory = $match?->name ?? 'All';
        }

        $productsJson = $products->map(fn ($p) => $this->productCard($p))->values();
        $categoriesJson = $categories->map(fn ($c) => $c->name)->values();

        return spa('frontend.collections', compact('categories', 'productsJson', 'activeCategory', 'categoriesJson'));
    }

    public function productShow($slug)
    {
        $product = Product::with(['category', 'images', 'materials', 'specs', 'options', 'techSpecs', 'documents'])
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

        $options = $product->options->where('status', true)->groupBy('group');

        $productJson = $this->productDetail($product);
        $optionsJson = [
            'config' => $this->optsJson($options->get('config', collect())),
            'finish' => $this->optsJson($options->get('finish', collect())),
            'glazing' => $this->optsJson($options->get('glazing', collect())),
            'upgrade' => $this->optsJson($options->get('upgrade', collect())),
        ];
        $zonesJson = $this->zonesJson($product->id);
        $relatedJson = $related->map(fn ($p) => $this->productCard($p))->values();
        $faqsJson = $this->faqsJson(4);
        $docsJson = $product->documents->map(fn ($d) => ['title' => $d->title, 'url' => url($d->file)])->values();
        $videoUrl = $product->effectiveVideoUrl();
        $videoEmbed = $this->videoEmbedUrl($product->effectiveVideoUrl());

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

    public function customBuild()
    {
        $this->seo('custom-build');

        return spa('frontend.custom-build');
    }

    public function enquiriesStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'postcode' => 'nullable|string|max:50',
            'topic' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'product_id' => 'nullable|exists:products,id',
            'config_summary' => 'nullable|string',
            'guide_price' => 'nullable|numeric|min:0',
            'source_page' => 'nullable|string|max:50',
        ]);

        Enquiry::create($data);

        return response()->json(['success' => true, 'message' => 'Enquiry received. The studio will reply within one working day.']);
    }

    public function gallery()
    {
        $this->seo('gallery');

        $galleryJson = $this->galleryJson();
        $galleryCatsJson = $this->galleryCatsJson();

        return spa('frontend.gallery', compact('galleryJson', 'galleryCatsJson'));
    }

    public function downloads()
    {
        $this->seo('downloads');

        $filesJson = $this->filesJson();

        return spa('frontend.downloads', compact('filesJson'));
    }

    public function downloadFile($id)
    {
        $dl = Download::where('id', $id)->where('status', true)->firstOrFail();
        if (! $dl->file || ! file_exists(public_path($dl->file))) {
            abort(404);
        }
        $dl->increment('downloads_count');

        return response()->download(public_path($dl->file));
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
        return $p->base_price ? 'From £'.number_format((float) $p->base_price) : 'On request';
    }

    /** Card shape used by collections grid, featured rail, configurator, related. */
    private function productCard(Product $p): array
    {
        return [
            'id' => $p->id,
            'slug' => $p->slug,
            'modelCode' => $p->model_code,
            'name' => $p->name,
            'category' => $p->category?->name ?? 'Collection',
            'categorySlug' => $p->category?->slug,
            'discipline' => $p->category?->name ?? 'Collection',
            'tagline' => $p->tagline,
            'price' => $this->priceFor($p),
            'leadTime' => $p->lead_time,
            'heroImage' => $this->heroFor($p),
            'materials' => $p->relationLoaded('materials') ? $p->materials->pluck('name')->all() : [],
            'dimensions' => $p->dimensions,
            'warranty' => $p->warranty,
            'specs' => $p->relationLoaded('specs') ? $p->specs->pluck('point')->all() : [],
        ];
    }

    /** Full shape for the details page JS. */
    private function productDetail(Product $p): array
    {
        return [
            ...$this->productCard($p),
            'description' => $p->description,
            'video' => $p->effectiveVideoUrl(),
            'videoEmbed' => $this->videoEmbedUrl($p->effectiveVideoUrl()),
            'model3d' => $p->model_3d ? url($p->model_3d) : null,
            'show3d' => (bool) $p->show_3d,
            'gallery' => $p->images->map(fn ($i) => [
                'src' => $this->imgUrl($i->image), 'caption' => $i->caption,
            ])->values()->all(),
            'tech' => $p->techSpecs->map(fn ($t) => [
                'label' => $t->label, 'value' => $t->value, 'highlight' => (bool) $t->highlight,
            ])->values()->all(),
            'docs' => $p->documents->map(fn ($d) => ['title' => $d->title, 'url' => url($d->file)])->values()->all(),
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

    private function optsJson($opts): array
    {
        return collect($opts)->map(fn ($o) => [
            'id' => 'opt-'.$o->id,
            'name' => $o->name,
            'sub' => $o->subtitle,
            'price' => $o->price_delta ? (float) $o->price_delta : 0,
            'swatch' => $o->swatch_color,
            'default' => (bool) $o->is_default,
        ])->values()->all();
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

    private function filesJson(?int $limit = null)
    {
        $q = Download::with('product:id,name,model_code')->where('status', true)->orderBy('sort_order');
        if ($limit) {
            $q->limit($limit);
        }

        return $q->get()->map(fn ($f) => [
            'id' => $f->id,
            'ref' => $f->ref,
            'suite' => $f->product?->name ? strtoupper($f->product->name) : 'ORIGINSPACES STUDIO',
            'title' => $f->title,
            'format' => $f->format,
            'size' => $f->size ?? '—',
            'rev' => $f->rev ?? '—',
            'url' => $f->file ? route('downloads.file', $f->id) : null,
        ])->values();
    }

    private function zonesJson(?int $productId = null)
    {
        // Product-specific zones replace the global set when they exist;
        // products without their own zones keep showing the global set.
        $specific = FloorZone::where('status', true)->where('product_id', $productId)
            ->orderBy('sort_order')->get();
        $zones = $specific->isNotEmpty() ? $specific : FloorZone::where('status', true)->whereNull('product_id')
            ->orderBy('sort_order')->get();

        return $zones->map(fn ($z, $i) => [
            'id' => 'zone-'.$z->id,
            'name' => $z->name,
            'desc' => $z->desc,
            'dims' => $z->dims,
            'sort' => $i,
        ])->values();
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Product extends Model
{
    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('egf_catalog'));
        static::deleted(fn () => Cache::forget('egf_catalog'));
    }

    protected $fillable = [
        'category_id', 'name', 'slug', 'tagline', 'highlights', 'description',
        'hero_image', 'is_featured', 'status', 'sort_order',
        'meta_title', 'meta_description', 'meta_keywords', 'meta_image',
        'origin_country', 'is_vegetarian', 'is_vegan', 'is_halal', 'is_organic', 'is_gluten_free',
        'nutrition_per', 'energy_kcal', 'fat_g', 'saturates_g', 'carbs_g',
        'sugars_g', 'fibre_g', 'protein_g', 'salt_g',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'status' => 'boolean',
            'sort_order' => 'integer',
            'is_vegetarian' => 'boolean',
            'is_vegan' => 'boolean',
            'is_halal' => 'boolean',
            'is_organic' => 'boolean',
            'is_gluten_free' => 'boolean',
        ];
    }

    /**
     * Sized variant of a remote WooCommerce upload (e.g. -300x300), which is
     * typically 10x lighter than the original. Local images pass through
     * untouched. Every <img> using this MUST keep the original as an
     * onerror fallback in case WP never generated that size.
     */
    public static function thumb(?string $url, int $size = 300): ?string
    {
        if (! $url) {
            return null;
        }
        if (str_contains($url, '/wp-content/uploads/') && preg_match('/\.(webp|jpe?g|png)$/i', $url)) {
            return (string) preg_replace('/\.(webp|jpe?g|png)$/i', "-{$size}x{$size}.$1", $url);
        }

        return $url;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('status', true);
    }

    /** Free-form per-product details (cooking suggestion, allergy advice, storage…). */
    public function extraAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class)->orderBy('sort_order');
    }

    /** Structured allergen flags (the 14 UK regulated allergens). */
    public function allergens(): BelongsToMany
    {
        return $this->belongsToMany(Allergen::class)->orderByPivot('id');
    }

    /** Diet badges with at least one flag set. */
    public function dietBadges(): array
    {
        $badges = [];
        foreach (['is_vegetarian' => 'Vegetarian', 'is_vegan' => 'Vegan', 'is_halal' => 'Halal', 'is_organic' => 'Organic', 'is_gluten_free' => 'Gluten-free'] as $flag => $label) {
            if ($this->$flag) {
                $badges[] = $label;
            }
        }

        return $badges;
    }

    /** True when any nutrition row is filled in. */
    public function hasNutrition(): bool
    {
        foreach (['energy_kcal', 'fat_g', 'saturates_g', 'carbs_g', 'sugars_g', 'fibre_g', 'protein_g', 'salt_g'] as $col) {
            if ($this->$col !== null) {
                return true;
            }
        }

        return false;
    }

    /** Shopper reviews for this product (newest first). */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->latest();
    }

    /**
     * Approved-review aggregates for cards: reviews_avg_rating + reviews_count.
     */
    public function scopeWithReviewSummary(Builder $query): Builder
    {
        return $query
            ->withAvg(['reviews' => fn ($q) => $q->approved()], 'rating')
            ->withCount(['reviews' => fn ($q) => $q->approved()]);
    }

    /** Key points as a trimmed non-empty list (one per line in admin). */
    public function highlightList(): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $this->highlights ?? ''))
            ->map(fn ($l) => trim($l))
            ->filter()
            ->values()
            ->all();
    }

    /** Per-product option-group overrides; when present they replace the category template. */
    public function optionGroups(): BelongsToMany
    {
        return $this->belongsToMany(OptionGroup::class, 'product_option_group')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    /**
     * Effective option groups: product overrides win, else the category template.
     *
     * @return Collection<int, OptionGroup>
     */
    public function effectiveOptionGroups()
    {
        if (! $this->relationLoaded('optionGroups')) {
            $this->load('optionGroups.values');
        }
        if ($this->optionGroups->isNotEmpty()) {
            return $this->optionGroups;
        }
        if (! $this->relationLoaded('category')) {
            $this->load('category.optionGroups.values');
        }

        return $this->category?->optionGroups ?? collect();
    }

    public function defaultVariant(): ?ProductVariant
    {
        if (! $this->relationLoaded('variants')) {
            $this->load('variants');
        }

        return $this->variants->firstWhere('is_default', true)
            ?? $this->variants->firstWhere('status', true)
            ?? $this->variants->first();
    }

    /** Single price (default variant) or min–max range across active in-stock variants. */
    public function priceRange(): ?string
    {
        if (! $this->relationLoaded('variants')) {
            $this->load('variants');
        }
        $prices = $this->variants
            ->where('status', true)
            ->map(fn ($v) => $v->sellingPrice())
            ->values();
        if ($prices->isEmpty()) {
            return null;
        }
        $min = $prices->min();
        $max = $prices->max();
        if ($min === $max) {
            return '£'.number_format($min, 2);
        }

        return '£'.number_format($min, 2).' – £'.number_format($max, 2);
    }

    /** Frontend SEO array shape for SEOMeta/OpenGraph. */
    public function seoArray(): array
    {
        return [
            'title' => $this->meta_title ?: $this->name,
            'description' => $this->meta_description ?: $this->tagline,
            'keywords' => $this->meta_keywords,
            'image' => $this->meta_image ?: $this->hero_image,
        ];
    }
}

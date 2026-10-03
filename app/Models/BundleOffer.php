<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BundleOffer extends Model
{
    protected $fillable = [
        'offer_id', 'name', 'required_qty', 'bundle_price',
        'starts_at', 'ends_at', 'status', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'required_qty' => 'integer',
            'bundle_price' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'bundle_offer_categories');
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'bundle_offer_variants', 'bundle_offer_id', 'product_variant_id');
    }

    /** Parent campaign — its window and switch govern attached rows. */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    /** Live right now (status + optional window + parent campaign when attached). */
    public function isLive(?Carbon $at = null): bool
    {
        if (! $this->status) {
            return false;
        }
        $at = $at ?? now();
        if ($this->starts_at && $this->starts_at->gt($at)) {
            return false;
        }
        if ($this->ends_at && $this->ends_at->lt($at)) {
            return false;
        }
        $parent = $this->offer;
        if ($parent && ! $parent->isLive($at)) {
            return false;
        }

        return true;
    }

    public function label(): string
    {
        return 'Any '.$this->required_qty.' for £'.number_format($this->bundle_price, 2);
    }

    /** All live bundles in admin order. */
    public static function liveAll(): Collection
    {
        return static::with('offer')->where('status', true)->orderBy('sort_order')->orderBy('id')->get()->filter(fn ($b) => $b->isLive())->values();
    }

    /**
     * Pool variant ids: every variant in the chosen categories (dynamic —
     * future variants join automatically) plus explicitly picked packs.
     *
     * @return int[]
     */
    public function poolVariantIds(): array
    {
        $fromCategories = [];
        $categoryIds = $this->categories()->pluck('categories.id')->all();
        if ($categoryIds !== []) {
            $fromCategories = ProductVariant::whereHas('product', fn ($q) => $q->whereIn('category_id', $categoryIds))->pluck('id')->all();
        }
        $explicit = $this->variants()->pluck('product_variants.id')->all();

        return array_values(array_unique([...$fromCategories, ...$explicit]));
    }

    /**
     * Group paid units into bundles, cheapest first. Mutates line totals and
     * promo labels; a unit joins at most one bundle. Returns total saving.
     *
     * @param  array<int, array{variant_id: int, price: float, qty: int, free_qty: int, available: bool, line_total: float, promo_label: ?string}>  $lines
     */
    public static function applyToLines(array &$lines, ?Collection $live = null): float
    {
        $saving = 0.0;
        $remaining = [];
        foreach ($lines as $i => $line) {
            $remaining[$i] = ($line['available'] ?? false) ? max(0, (int) $line['qty'] - (int) ($line['free_qty'] ?? 0)) : 0;
        }

        foreach ($live ?? static::liveAll() as $bundle) {
            if ($bundle->required_qty < 2) {
                continue;
            }
            $pool = array_flip($bundle->poolVariantIds());

            // Cheapest paid units first.
            $units = [];
            foreach ($lines as $i => $line) {
                if (! isset($pool[$line['variant_id']])) {
                    continue;
                }
                for ($u = 0; $u < ($remaining[$i] ?? 0); $u++) {
                    $units[] = ['line' => $i, 'price' => (float) $line['price']];
                }
            }
            usort($units, fn ($a, $b) => $a['price'] <=> $b['price']);

            $groups = (int) floor(count($units) / $bundle->required_qty);
            if ($groups < 1) {
                continue;
            }

            // Proportional split of the bundle price: every grouped unit stays
            // at or below shelf, shares sum to exactly P, last unit absorbs dust.
            $bundledShelf = [];
            $bundledCharged = [];
            for ($g = 0; $g < $groups; $g++) {
                $slice = array_slice($units, $g * $bundle->required_qty, $bundle->required_qty);
                $shelfSum = round(array_sum(array_column($slice, 'price')), 2);
                if ($shelfSum <= (float) $bundle->bundle_price) {
                    continue;
                }
                $ratio = (float) $bundle->bundle_price / $shelfSum;
                $assigned = 0.0;
                $last = count($slice) - 1;
                foreach ($slice as $j => $unit) {
                    $charged = $j === $last
                        ? round((float) $bundle->bundle_price - $assigned, 2)
                        : round($unit['price'] * $ratio, 2);
                    $assigned = round($assigned + $charged, 2);
                    $i = $unit['line'];
                    $bundledShelf[$i] = ($bundledShelf[$i] ?? 0) + $unit['price'];
                    $bundledCharged[$i] = ($bundledCharged[$i] ?? 0) + $charged;
                    $remaining[$i]--;
                }
            }

            foreach ($bundledShelf as $i => $shelf) {
                $charged = round($bundledCharged[$i], 2);
                if ($charged >= $shelf) {
                    continue;
                }
                $lines[$i]['line_total'] = round($lines[$i]['line_total'] - $shelf + $charged, 2);
                $saving = round($saving + $shelf - $charged, 2);
                $label = $bundle->label();
                $lines[$i]['promo_label'] = $lines[$i]['promo_label']
                    ? $lines[$i]['promo_label'].' · '.$label
                    : $label;
            }
        }

        return $saving;
    }

    /** First live bundle covering a variant (for badges), or null. */
    public static function forVariant(?int $variantId, ?int $productId, ?array $poolCache = null): ?self
    {
        foreach (static::liveAll() as $bundle) {
            $pool = $poolCache[$bundle->id] ?? $bundle->poolVariantIds();
            if (in_array($variantId, $pool, true)) {
                return $bundle;
            }
        }

        return null;
    }

    /**
     * Cover map for a cycle: [variantId => BundleOffer] first-match.
     * Prime once per listing cycle and pass down; keeps grids to a few queries.
     *
     * @return array<int, self>
     */
    public static function coverMap(?Collection $live = null): array
    {
        $map = [];
        foreach ($live ?? static::liveAll() as $bundle) {
            foreach ($bundle->poolVariantIds() as $vid) {
                $map[$vid] ??= $bundle;
            }
        }

        return $map;
    }
}

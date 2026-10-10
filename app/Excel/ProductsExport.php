<?php

namespace App\Excel;

use App\Models\Allergen;
use App\Models\Category;
use App\Models\OptionGroup;
use App\Models\Product;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Full-catalog export: one row per variant.
 *
 * Fixed columns + one column per option group in the library (header = group
 * name, cell = value label, blank = group not used by that variant). Reimport
 * matches rows by SKU (variant) and Product ID / Product Name (product).
 */
class ProductsExport
{
    /** Columns before the dynamic option-group columns. */
    public const LEAD_COLUMNS = [
        'Product ID',
        'SKU',
        'Product Name',
        'Category',
        'Card Subtitle',
        'Key Points',
        'Description',
        'Extra Details',
        'Hero Image',
        'Featured',
        'Product Status',
        'Sort Order',
        'Origin Country',
        'Vegetarian',
        'Vegan',
        'Halal',
        'Organic',
        'Gluten-Free',
        'Allergens',
        'Nutrition Per',
        'Energy (kcal)',
        'Fat (g)',
        'Saturates (g)',
        'Carbs (g)',
        'Sugars (g)',
        'Fibre (g)',
        'Protein (g)',
        'Salt (g)',
        'Meta Title',
        'Meta Keywords',
        'Meta Description',
    ];

    /** Columns after the dynamic option-group columns. */
    public const TAIL_COLUMNS = [
        'MRP',
        'Offer Price',
        'In Stock',
        'Default',
        'Variant Image',
        'Variant Status',
    ];

    public static function yesNo(bool $v): string
    {
        return $v ? 'yes' : 'no';
    }

    public static function build(): Spreadsheet
    {
        $groups = OptionGroup::with('values')->orderBy('sort_order')->get();
        $products = Product::with([
            'category', 'extraAttributes', 'variants.values.group', 'allergens',
        ])->orderBy('sort_order')->orderByDesc('id')->get();

        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        self::productsSheet($spreadsheet, $products, $groups);
        self::referenceSheet($spreadsheet, $groups);
        self::imageSlotsSheet($spreadsheet, $products);
        self::guideSheet($spreadsheet, $groups);
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private static function headers($groups): array
    {
        return array_merge(
            self::LEAD_COLUMNS,
            $groups->pluck('name')->all(),
            self::TAIL_COLUMNS
        );
    }

    private static function productsSheet(Spreadsheet $book, $products, $groups): void
    {
        $sheet = new Worksheet($book, 'Products');
        $book->addSheet($sheet);

        $headers = self::headers($groups);
        $sheet->fromArray([$headers], null, 'A1');
        self::styleHeader($sheet, count($headers));

        $row = 2;
        foreach ($products as $p) {
            $variants = $p->variants->sortBy('sort_order')->values();
            if ($variants->isEmpty()) {
                $sheet->fromArray([self::row($p, null, $groups)], null, "A{$row}");
                $row++;

                continue;
            }
            foreach ($variants as $v) {
                $sheet->fromArray([self::row($p, $v, $groups)], null, "A{$row}");
                $row++;
            }
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }
    }

    private static function row(Product $p, $v, $groups): array
    {
        $lead = [
            $p->id,
            $v?->sku,
            $p->name,
            $p->category?->name,
            $p->tagline,
            implode("\n", $p->highlightList()),
            strip_tags($p->description ?? ''),
            $p->extraAttributes->map(fn ($a) => $a->label.' | '.$a->value)->join("\n"),
            $p->hero_image,
            self::yesNo((bool) $p->is_featured),
            $p->status ? 'active' : 'disabled',
            $p->sort_order,
            $p->origin_country,
            self::yesNo((bool) $p->is_vegetarian),
            self::yesNo((bool) $p->is_vegan),
            self::yesNo((bool) $p->is_halal),
            self::yesNo((bool) $p->is_organic),
            self::yesNo((bool) $p->is_gluten_free),
            $p->allergens->pluck('name')->join(', '),
            $p->nutrition_per,
            $p->energy_kcal !== null ? (float) $p->energy_kcal : null,
            $p->fat_g !== null ? (float) $p->fat_g : null,
            $p->saturates_g !== null ? (float) $p->saturates_g : null,
            $p->carbs_g !== null ? (float) $p->carbs_g : null,
            $p->sugars_g !== null ? (float) $p->sugars_g : null,
            $p->fibre_g !== null ? (float) $p->fibre_g : null,
            $p->protein_g !== null ? (float) $p->protein_g : null,
            $p->salt_g !== null ? (float) $p->salt_g : null,
            $p->meta_title,
            $p->meta_keywords,
            $p->meta_description,
        ];

        $byGroup = [];
        if ($v && $v->relationLoaded('values')) {
            foreach ($v->values as $val) {
                $byGroup[$val->group->name] = $val->label;
            }
        }
        $opts = $groups->map(fn ($g) => $byGroup[$g->name] ?? null)->all();

        $tail = $v ? [
            $v->mrp !== null ? (float) $v->mrp : null,
            $v->offer_price !== null ? (float) $v->offer_price : null,
            self::yesNo((bool) $v->in_stock),
            self::yesNo((bool) $v->is_default),
            $v->image,
            $v->status ? 'active' : 'disabled',
        ] : [null, null, null, null, null, null];

        return array_merge($lead, $opts, $tail);
    }

    /**
     * Every image slot the import expects: hero = hero/{slug}.jpg,
     * variant = variants/{SKU}.jpg (any of jpg/png/webp accepted on upload).
     */
    private static function imageSlotsSheet(Spreadsheet $book, $products): void
    {
        $sheet = new Worksheet($book, 'Image Slots');
        $book->addSheet($sheet);

        $sheet->fromArray([['Type', 'Product', 'Key', 'Expected Filename', 'Has Image']], null, 'A1');
        self::styleHeader($sheet, 5);

        $row = 2;
        foreach ($products as $p) {
            $sheet->fromArray([[
                'Hero', $p->name, $p->slug,
                ProductsImport::expectedHeroName($p->slug),
                $p->hero_image ? 'yes' : 'no',
            ]], null, "A{$row}");
            $row++;
            foreach ($p->variants->sortBy('sort_order')->values() as $v) {
                if (! $v->sku) {
                    continue;
                }
                $sheet->fromArray([[
                    'Variant', $p->name, $v->sku,
                    ProductsImport::expectedVariantName($v->sku),
                    $v->image ? 'yes' : 'no',
                ]], null, "A{$row}");
                $row++;
            }
        }

        $sheet->freezePane('A2');
        foreach (range(1, 5) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }
    }

    private static function referenceSheet(Spreadsheet $book, $groups): void
    {
        $sheet = new Worksheet($book, 'Reference');
        $book->addSheet($sheet);

        $sheet->fromArray([['Use these exact names in the Products sheet. New categories and new option values are created on import; option groups are NOT — create the group first under Master Setup → Option Groups.']], null, 'A1');
        $sheet->getStyle('A1')->getFont()->setBold(true);

        $sheet->fromArray([['Categories']], null, 'A3');
        $sheet->getStyle('A3')->getFont()->setBold(true);
        $cats = Category::orderBy('sort_order')->pluck('name')->all();
        $r = 4;
        foreach ($cats as $c) {
            $sheet->setCellValue("A{$r}", $c);
            $r++;
        }

        $sheet->fromArray([['Allergens (fixed list)']], null, 'B3');
        $sheet->getStyle('B3')->getFont()->setBold(true);
        $r = 4;
        foreach (Allergen::orderBy('sort_order')->pluck('name')->all() as $a) {
            $sheet->setCellValue("B{$r}", $a);
            $r++;
        }

        $col = 3;
        foreach ($groups as $g) {
            $cell = fn ($r) => Coordinate::stringFromColumnIndex($col).$r;
            $sheet->setCellValue($cell(3), $g->name.' ('.$g->type.')');
            $sheet->getStyle($cell(3))->getFont()->setBold(true);
            $r = 4;
            foreach ($g->values as $v) {
                $sheet->setCellValue($cell($r), $v->label);
                $r++;
            }
            $col++;
        }
        foreach (range(1, $col) as $c) {
            $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
        }
    }

    private static function guideSheet(Spreadsheet $book, $groups): void
    {
        $sheet = new Worksheet($book, 'Guide');
        $book->addSheet($sheet);

        $lines = [
            ['PRODUCT EXCEL — HOW TO USE'],
            [''],
            ['1. EXPORT sends you this file with every product and variant as rows.'],
            ['2. EDIT it: change prices, stock, names. Add rows for new variants or products.'],
            ['3. UPLOAD it back: Admin → Products → Import → preview → Confirm.'],
            [''],
            ['RULES'],
            ['- One row = one variant. Product columns repeat on each row of the same product.'],
            ['- SKU identifies the variant. Keep it stable; blank SKU only works when the product has exactly one variant.'],
            ['- Product ID is read-only and wins over Product Name when matching. Do not edit it.'],
            ['- Category: exact name match (case-insensitive). A new name CREATES the category and is reused for all rows using it.'],
            ['- Option columns ('.($groups->pluck('name')->join(', ') ?: 'none yet').'): exact value label, blank = not used. A new label CREATES the value in that group.'],
            ['- New option GROUP columns are rejected — ask admin to create the group first.'],
            ['- yes/no fields accept yes, no, 1, 0, true, false. Status fields accept active/disabled too.'],
            ['- Key Points: one per line inside the cell (Alt+Enter). Extra Details: one per line as "Label | value".'],
            ['- MRP is required and must be ≥ 0. Offer Price must be empty or ≤ MRP.'],
            ['- Default: mark the card-price row. If several are marked, the first wins; if none, the first row wins.'],
            ['- Hero Image / Variant Image cells are for reference only — this import never changes photos. Use Admin → Products → Bulk Photos to change photos.'],
            ['- Allergens: comma-separated exact names from the Reference sheet (Milk, Nuts…). Unknown names are rejected — allergens are a fixed list.'],
            ['- Nutrition columns are optional numbers; blank means "not stated". Origin Country is free text.'],
            ['- "Disable missing variants" (checkbox at import): variants in the shop but absent from the sheet are set to disabled. Off by default.'],
            [''],
            ['IMAGES'],
            ['- Photos are managed on the Bulk Photos page (Admin → Products → Bulk Photos): drop photos, they match by SKU in the filename, review, confirm.'],
            ['- The "Image Slots" sheet lists every product slug and variant SKU for naming your photo files.'],
        ];
        $sheet->fromArray($lines, null, 'A1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getColumnDimension('A')->setWidth(130);
    }

    private static function styleHeader(Worksheet $sheet, int $cols): void
    {
        $range = 'A1:'.Coordinate::stringFromColumnIndex($cols).'1';
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F6F43']],
            'alignment' => ['wrapText' => true, 'vertical' => 'center'],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getStyle($sheet->calculateWorksheetDimension())->getAlignment()->setWrapText(true);
    }
}

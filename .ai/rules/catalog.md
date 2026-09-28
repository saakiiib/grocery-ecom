---
paths:
  - 'app/Models/Product*.php'
  - 'app/Models/Option*.php'
  - 'app/Models/Category.php'
  - 'app/Http/Controllers/Admin/Product*.php'
  - 'database/migrations/*product*.php'
  - 'database/migrations/*option*.php'
  - 'database/seeders/OriginSpacesDemoSeeder.php'
  - 'resources/views/admin/products/**'
---

# Grocery Catalog (products + variants)

Copied from an OriginSpaces showcase app and converted to grocery. Product price NEVER lives on `products` — only on variant rows.

## Domain model (normalized, no JSON blobs)

- `products`: identity only (name, slug, category, tagline = card subtitle, highlights one-per-line, description, hero_image, featured/status/sort, SEO). No price, no SKU, no dimensions/warranty/video/3D columns (removed).
- `option_groups` (name, slug, type buttons|dropdown) + `option_values` (unique per group): shop-wide reusable vocabulary, defined once.
- `category_option_group`: template inherited by products in that category.
- `product_option_group`: per-product override; when rows exist they REPLACE the category template (`Product::effectiveOptionGroups()`).
- `product_variants` (sku unique nullable, mrp, offer_price nullable ≤ mrp, image nullable, in_stock bool, is_default, status): the purchasable rows. Simple item = 1 row with no value links.
- `product_variant_values` (composite PK, restrict on delete): combination = rows, never JSON.
- `product_attributes` (label, value, sort_order): free-form per-product details (cooking, allergens, storage). Relation is `extraAttributes()` — never name it `attributes()`.

## Invariants (enforce, don't break)

- Every product has ≥ 1 variant, exactly 1 default. Auto-created (mrp 0) on store; destroy promotes the next row when the default is deleted.
- Max one value per option group per variant (`assertOneValuePerGroup` + unique pivot).
- `sellingPrice()` = offer when set and < mrp, else mrp. `priceRange()` = single price or min–max. Deleting an option value in use is blocked by FK (restrict).
- Meta/OG images stay jpeg/png (never webp); main images webp via Intervention 2.7. Image removal uses dedicated DELETE routes + buttons, never checkboxes or `Current:` path text.

## Workflow conventions for this project

- Pre-launch dev: edit the ORIGINAL create-table migration and `migrate:fresh --seed` (no alter-migrations for products/variants).
- Seeder `OriginSpacesDemoSeeder::seedGroceryCatalog()` is the acceptance dataset: lamb matrix (8 rows), rice packs (3), salt simple (1). FAQ/gallery seed data is still old showcase content — leave it.
- `tests/Feature/AdminCatalogTest.php` + `FrontendTest.php` target the dead showcase schema (31 failing); rewrite for grocery, don't patch. Never delete test files without approval.
- Frontend theme is the owner's area: `FrontendController` exposes `subtitle`, `highlights[]`, `extraAttributes[]`, `optionGroups[]`, `variants[]` matrix — keep those keys stable.

## Known gaps (as of 2026-09-28 audit)

- No admin CRUD for option groups/values (seed-only; admin can't add e.g. "750g").
- Shopper variant picker (dropdowns + live price) not built.
- Product Excel lives in `app/Excel/` on raw PhpSpreadsheet (no maatwebsite): `ProductsExport` (Products/Reference/Image Slots/Guide sheets, one row per variant, dynamic group columns), `ProductsImport::parse($path, $imageDir)` (no writes) + `::commit($rows, $disableMissing, $imageDir)` (transaction). Keys: SKU → variant, Product ID then Name → product, category/value names auto-create (case-insensitive), unknown group headers abort, global row-0 conflicts block confirm. Uploads stage in `storage/app/imports/{token}/` (excel + extracted images/), re-parsed on confirm, dir deleted after.
- Bulk images: slot filenames are `hero/{product-slug}.jpg` and `variants/{SKU}.jpg` (jpg/png/webp, case-insensitive stem match). `products.imageTemplate` downloads hero/+variants/ folders + README + manifest.csv. Hero/Variant Image cells override: bare filename must be in the ZIP (else row error), URL or /path stored as-is, blank uses the slot file. Converted to webp on commit (hero 1600px → `public/uploads/products/`, variants 800px → `public/uploads/products/variants/`), old files deleted.
- `contacts.product_id` unused in admin. Old theme blades still reference null compat keys (`leadTime`, `dimensions`, `warranty`, `model3d`) — safe, remove during theme rebuild.

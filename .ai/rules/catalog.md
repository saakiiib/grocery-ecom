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
- `option_groups` (name, slug, type buttons|dropdown) + `option_values` (unique per group): shop-wide reusable vocabulary, defined once. Full admin CRUD at Master Setup → Option Groups (`OptionGroupController`: index/sort/manage-values, status toggles, guarded deletes). Deleting a group/value in use by a template, override or variant is blocked with 422.
- `category_option_group`: template inherited by products in that category. Assigned in admin via checkboxes on the category form (`CategoryController::groupSyncPayload`); empty = no template.
- `product_option_group`: per-product override; when rows exist they REPLACE the category template (`Product::effectiveOptionGroups()`). Managed per product via `ProductVariantController::syncGroups`.
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
- Never depend on `public/frontend-raw/` (owner deletes it): icons live at `public/resources/frontend/js/icons.js` (parsed by `x-icon`), every image fallback is `public/placeholder.webp` (header/footer logos render text-only when no company logo), gallery seeds use hosted food photos. Seeder is `GroceryDemoSeeder` (grocery FAQs/gallery/sliders/statuses/slots/settings — no OriginSpaces content anywhere).
- Commerce engine (2026-09-29, `tests/Feature/ShopFlowTest.php` 9 tests): session bag ONLY (`BagController`, key `bag`, ids+qty in, server prices out — never trust browser prices; availability = product.status && variant.status && variant.in_stock). Frontend says **bag** everywhere (route `bag`, `/bag`, `data-bag-*`, `EGF.addToBag/loadBag`); CSS hooks `cart-item/cart-layout` kept (raw theme classes). Checkout `POST /checkout/place` validates bag/min-order/slot/date, prices via `CheckoutController::priceBag`, snapshots `order_items`, writes history, clears bag. COD confirms instantly; Stripe (PaymentIntent + `payment-confirm` verify) and PayPal (Orders create+capture) via raw HTTP — no composer packages — credentials resolve `.env` (`STRIPE_PUBLISHABLE/SECRET`, `PAYPAL_CLIENT_ID/SECRET/MODE` in `config/services.php`) first, Shop Settings as fallback; `credentialSource()` powers the admin status badges; empty keys hide the method. `POST /checkout/cancel` removes abandoned unpaid `new` orders (PayPal onCancel calls it; paid orders refused). `order_statuses` table is the dynamic lifecycle (`Order::changeStatus` writes `order_status_histories` with changer+note; admin can rename/recolour/toggle but never delete). `delivery_slots` (fee + cutoff) + `bookableDates()` drive checkout; `delivery_min_order/delivery_free_over` in settings. One `users` table: `user_type` 1 admin / 0 shopper, same `/login`, `/register` creates shoppers and honours `?redirect=` (bag survives in session). Account: order list/detail/reorder (`buy again` skips unavailable with a note)/profile; unpaid online orders show pay-now resume (`account.pay` → fresh credentials → same `payment-confirm`); shoppers cancel own new/confirmed unpaid-or-COD orders, paid ones are directed to contact (refund). Admin: Orders DataTable + detail + status form + timeline; dashboard shows today orders/revenue, open orders, shoppers, latest 5; Delivery Slots CRUD; Order Statuses edit; Shop Settings page. Success page gated by owner-or-`last_order_id`-session. Full suite 33 passed.

## Known gaps (as of 2026-09-28 audit)

- Shopper variant picker (dropdowns + live price) not built.
- `tests/Feature/AdminCatalogTest.php` (9 tests: invariants, guards, template/override, Excel roundtrip, sliders, page auth) + `FrontendTest.php` (5 tests) rewritten for grocery 2026-09-29 — full suite 16 passed. Never delete test files without approval.
- Admin menu is flat: Dashboard, Products, Category, Option Groups, Contacts as single items; Content group (Sliders visible, Testimonials hidden); Settings group. No Master Setup / Leads groups.
- Product Excel lives in `app/Excel/` on raw PhpSpreadsheet (no maatwebsite): `ProductsExport` (Products/Reference/Image Slots/Guide sheets, one row per variant, dynamic group columns), `ProductsImport::parse($path, $imageDir)` (no writes) + `::commit($rows, $disableMissing, $imageDir)` (transaction). Keys: SKU → variant, Product ID then Name → product, category/value names auto-create (case-insensitive), unknown group headers abort, global row-0 conflicts block confirm. Uploads stage in `storage/app/imports/{token}/` (excel + extracted images/), re-parsed on confirm, dir deleted after.
- Bulk images: slot filenames are `hero/{product-slug}.jpg` and `variants/{SKU}.jpg` (jpg/png/webp, case-insensitive stem match). `products.imageTemplate` downloads hero/+variants/ folders + README + manifest.csv. Hero/Variant Image cells override: bare filename must be in the ZIP (else row error), URL or /path stored as-is, blank uses the slot file. Converted to webp on commit (hero 1600px → `public/uploads/products/`, variants 800px → `public/uploads/products/variants/`), old files deleted.
- `contacts.product_id` unused in admin. Old theme blades still reference null compat keys (`leadTime`, `dimensions`, `warranty`, `model3d`) — safe, remove during theme rebuild.

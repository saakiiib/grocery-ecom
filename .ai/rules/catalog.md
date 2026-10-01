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
- Seeder `GroceryDemoSeeder::seedGroceryCatalog()` is the acceptance dataset: 10 parents / 20 children; 8 hand-crafted products (lamb leg + chops, chicken, beef mince, basmati, everyday rice, sea salt, pepper) plus `seedBulkScale()` (94 generated products, 102 total, DEMO- SKUs, hosted hero images); all option groups render as buttons; grocery FAQs/gallery/sliders/statuses/slots/settings — no OriginSpaces content anywhere. Seeded admin login is `admin@gmail.com` / `123456` (`UserSeeder`); shoppers self-register at `/register` (`user_type` 0).
- Never depend on `public/frontend-raw/` (owner deletes it): icons live at `public/resources/frontend/js/icons.js` (parsed by `x-icon`), every image fallback is `public/placeholder.webp` (header/footer logos render text-only when no company logo), gallery seeds use hosted food photos. Seeder is `GroceryDemoSeeder` (grocery FAQs/gallery/sliders/statuses/slots/settings — no OriginSpaces content anywhere).
- Commerce engine (2026-09-29, `tests/Feature/ShopFlowTest.php` 9 tests): session bag ONLY (`BagController`, key `bag`, ids+qty in, server prices out — never trust browser prices; availability = product.status && variant.status && variant.in_stock). Frontend says **bag** everywhere (route `bag`, `/bag`, `data-bag-*`, `EGF.addToBag/loadBag`); CSS hooks `cart-item/cart-layout` kept (raw theme classes). Checkout `POST /checkout/place` validates bag/min-order/slot/date, prices via `CheckoutController::priceBag`, snapshots `order_items`, writes history, clears bag. COD confirms instantly; Stripe (PaymentIntent + `payment-confirm` verify) and PayPal (Orders create+capture) via raw HTTP — no composer packages — credentials resolve `.env` (`STRIPE_PUBLISHABLE/SECRET`, `PAYPAL_CLIENT_ID/SECRET/MODE` in `config/services.php`) first, Shop Settings as fallback; `credentialSource()` powers the admin status badges; empty keys hide the method. `POST /checkout/cancel` removes abandoned unpaid `new` orders (PayPal onCancel calls it; paid orders refused). `order_statuses` table is the dynamic lifecycle (`Order::changeStatus` writes `order_status_histories` with changer+note; admin can rename/recolour/toggle but never delete). `delivery_slots` (fee + cutoff) + `bookableDates()` drive checkout; `delivery_min_order/delivery_free_over` in settings. One `users` table: `user_type` 1 admin / 0 shopper, same `/login`, `/register` creates shoppers and honours `?redirect=` (bag survives in session). Account: order list/detail/reorder (`buy again` skips unavailable with a note)/profile/change-password; loyalty panel (balance + last 10 ledger rows); unpaid online orders show pay-now resume (`account.pay` → fresh credentials → same `payment-confirm`); shoppers cancel own new/confirmed unpaid-or-COD orders, paid ones are directed to contact (refund). Guest tracking `/track` (order number + checkout phone, throttled). Loyalty: `user_points` ledger, earn `points_per_pound`/£1 of goods on Delivered (registered only, idempotent), redeem at `points_value` each (min `points_min_redeem`, capped at subtotal, guests refused), cancelled orders auto-refund via `reversal` row — all inside `Order::settlePoints()`; rates in Shop Settings; admin order page shows earned/redeemed. Admin: Orders DataTable + detail + status form + timeline; dashboard shows today orders/revenue, open orders, shoppers, latest 5; Delivery Slots CRUD; Order Statuses edit; Shop Settings page. Success page gated by owner-or-`last_order_id`-session. Full suite 38 passed.

## Live recheck 2026-09-29 (fresh seed → HTTP end-to-end, all green)

- Guest: home/shop/details/bag/checkout/track + about/gallery/contact/privacy/terms/refund/faq all 200; `/offers` 301s to `/shop/offers`; `/collections` is gone (404s); shop URLs are pretty (`/shop/lamb`, `/shop/offers`, query `?category=`/`?only_offers=1` 301 to them, unknown slugs 404); shop filters are category tree (parent includes descendants) + `only_offers` toggle + search + capped price slider + sort, 24/page cumulative with Load more, all SPA-navigated without reload; seeded slugs are `lamb-leg-bone-in`, `basmati-rice-extra-long`, `sea-salt-flakes`; SKUs look like `EGF89913`; order numbers sequence `EGF-10001…`. COD place → bag cleared, subtotal+fee+total snapshotted, status Confirmed/unpaid; success page owner-only (strangers 404); tracking works with lowercase number + spaced phone.
- Shopper: register keeps the session bag → order as user → account shows order + loyalty panel → reorder refills bag → cancel own confirmed-COD works; profile rename + password change verified by re-login with the new password.
- Loyalty live: registered order delivered → `floor(goods subtotal)` points earned (35 pts on £35.98, worth £0.35 at 0.01/pt), account panel shows balance + value; guest orders earn 0; cancelled orders auto-refund redeemed points.
- Admin: full lifecycle `confirmed → packed → out_for_delivery → delivered` with per-step notes recorded in `order_status_histories`; dashboard shows today/open/latest; slots/statuses/settings/products/category/option-groups pages all 200. With empty gateway keys checkout offers COD only + min-order note.
- Ops notes: `SESSION_DRIVER=database`, `CACHE_STORE=database`. Serve each project on a UNIQUE port — stale `originspaces` dev servers were squatting on 8123 and served the wrong app (load-balanced by the OS); the grocery recheck ran on 8137. Zero `frontend-raw` references in served HTML.

## Known gaps (as of 2026-09-28 audit, fallback + /clear resolved 2026-10-01)

- Route fallback now `abort(404)` (routes/web.php), so typo'd paths render `errors/404.blade.php` instead of redirecting to `/`.
- `GET /clear` dev route removed (2026-10-01) — it logged out, flushed the session (nuked bags) and cleared caches with no auth guard.
- Stripe/PayPal verified with faked HTTP in tests only — never completed live (no keys in `.env`); with empty keys only COD shows at checkout, which is the intended behaviour.
- `tests/Feature/AdminCatalogTest.php` (9 tests: invariants, guards, template/override, Excel roundtrip, sliders, page auth) + `FrontendTest.php` (public pages, search/filter/sort, offers filter, details picker, SPA-safety) + `ShopFlowTest.php` (18 tests: full commerce incl. password change, tracking, points earn/redeem/reversal, lifecycle) — full suite 38 passed. Never delete test files without approval.
- Admin menu is flat: Dashboard, Products, Category, Option Groups, Contacts as single items; Content group (Sliders visible, Testimonials hidden); Settings group. No Master Setup / Leads groups.
- Product Excel lives in `app/Excel/` on raw PhpSpreadsheet (no maatwebsite): `ProductsExport` (Products/Reference/Image Slots/Guide sheets, one row per variant, dynamic group columns), `ProductsImport::parse($path, $imageDir)` (no writes) + `::commit($rows, $disableMissing, $imageDir)` (transaction). Keys: SKU → variant, Product ID then Name → product, category/value names auto-create (case-insensitive), unknown group headers abort, global row-0 conflicts block confirm. Uploads stage in `storage/app/imports/{token}/` (excel + extracted images/), re-parsed on confirm, dir deleted after.
- Bulk images: slot filenames are `hero/{product-slug}.jpg` and `variants/{SKU}.jpg` (jpg/png/webp, case-insensitive stem match). `products.imageTemplate` downloads hero/+variants/ folders + README + manifest.csv. Hero/Variant Image cells override: bare filename must be in the ZIP (else row error), URL or /path stored as-is, blank uses the slot file. Converted to webp on commit (hero 1600px → `public/uploads/products/`, variants 800px → `public/uploads/products/variants/`), old files deleted.
- `contacts.product_id` unused in admin. Contact form posts to `contact.store` and lands in admin Contacts inbox (verified live).

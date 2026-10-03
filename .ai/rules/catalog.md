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

- Pre-launch dev: ONE migration per table — edit the ORIGINAL create-table migration and `migrate:fresh --seed` (no `add_*`/`alter` migrations; FK dependents must sort after their parents by filename timestamp, e.g. `coupons` before `orders`, `offers` before BOGO/flash/bundles).
- Seeder `GroceryDemoSeeder::seedGroceryCatalog()` is the acceptance dataset: 10 parents / 20 children; 8 hand-crafted products (lamb leg + chops, chicken, beef mince, basmati, everyday rice, sea salt, pepper) plus `seedBulkScale()` (94 generated products, 102 total, DEMO- SKUs, hosted hero images); all option groups render as buttons; grocery FAQs/gallery/sliders/statuses/slots/settings — no OriginSpaces content anywhere. Seeded admin login is `admin@gmail.com` / `123456` (`UserSeeder`); shoppers self-register at `/register` (`user_type` 0).
- Never depend on `public/frontend-raw/` (owner deletes it): icons live at `public/resources/frontend/js/icons.js` (parsed by `x-icon`), every image fallback is `public/placeholder.webp` (header/footer logos render text-only when no company logo), gallery seeds use hosted food photos. Seeder is `GroceryDemoSeeder` (grocery FAQs/gallery/sliders/statuses/slots/settings — no OriginSpaces content anywhere).
- Commerce engine (2026-09-29, `tests/Feature/ShopFlowTest.php` 9 tests): session bag ONLY (`BagController`, key `bag`, ids+qty in, server prices out — never trust browser prices; availability = product.status && variant.status && variant.in_stock). Frontend says **bag** everywhere (route `bag`, `/bag`, `data-bag-*`, `EGF.addToBag/loadBag`); CSS hooks `cart-item/cart-layout` kept (raw theme classes). Checkout `POST /checkout/place` validates bag/min-order/slot/date, prices via `CheckoutController::priceBag`, snapshots `order_items`, writes history, clears bag. COD confirms instantly; Stripe (PaymentIntent + `payment-confirm` verify) and PayPal (Orders create+capture) via raw HTTP — no composer packages — credentials resolve `.env` (`STRIPE_PUBLISHABLE/SECRET`, `PAYPAL_CLIENT_ID/SECRET/MODE` in `config/services.php`) first, Shop Settings as fallback; `credentialSource()` powers the admin status badges; both methods always render at checkout, disabled with "Switching on soon" until keys exist (server still refuses unconfigured methods). Bag images resolve via `BagController::bagImage()` (absolute URLs pass through, stored paths already carry their folder — never double-prefixed). `POST /checkout/cancel` removes abandoned unpaid `new` orders (PayPal onCancel calls it; paid orders refused). `order_statuses` table is the dynamic lifecycle (`Order::changeStatus` writes `order_status_histories` with changer+note; admin can rename/recolour/toggle but never delete). `delivery_slots` (fee + cutoff) + `bookableDates()` drive checkout; `delivery_min_order/delivery_free_over` in settings. Coupons: `coupons` table (code/type/value/min/expiry/max_uses/max_per_user, shoppers only), validated live at `POST /checkout/coupon` and rechecked inside `place()` (`CouponRejected` → 422); orders store `coupon_id/code/discount`; admin CRUD + per-coupon usage (who × which order). One `users` table: `user_type` 1 admin / 0 shopper, same `/login`, `/register` creates shoppers and honours `?redirect=` (bag survives in session). Account: order list/detail/reorder (`buy again` skips unavailable with a note)/profile/change-password; loyalty panel (balance + last 10 ledger rows); unpaid online orders show pay-now resume (`account.pay` → fresh credentials → same `payment-confirm`); shoppers cancel own new/confirmed unpaid-or-COD orders, paid ones are directed to contact (refund). Guest tracking `/track` (order number + checkout phone, throttled). Loyalty: `user_points` ledger, earn `points_per_pound`/£1 of goods on Delivered (registered only, idempotent), redeem at `points_value` each (min `points_min_redeem`, capped at subtotal, guests refused), cancelled orders auto-refund via `reversal` row — all inside `Order::settlePoints()`; rates in Shop Settings; admin order page shows earned/redeemed. Admin: Orders DataTable + detail + status form + timeline; dashboard shows today orders/revenue, open orders, shoppers, latest 5; Delivery Slots CRUD; Order Statuses edit; Shop Settings page. Success page gated by owner-or-`last_order_id`-session. Full suite 38 passed.

## Category sorting (scoped, 2026-10-03)

- `categories.sort_order` is scoped by level, not global: parents (`parent_id IS NULL`) share one `0..n` sequence; each parent's children share their own `0..n` sequence.
- Homepage "Shop by category" (`FrontendController@index` → `$categoriesJson`), shop pills (`shop()` → `$parents`), footer `take(5)` all show parents only in parent-scoped order. Shop child chips show one parent's children in that parent's order.
- Admin `CategoryController::store()` appends at end of its sibling scope (`max+1` within same `parent_id`); `sortList(?scope=parent|children&parent_id=)` + `sortUpdate(ids[])` reorder one scope at a time; Sort tab has separate Parent list + per-parent Child list. Never mix parents and children in one sortable list.

## Addresses, VAT, invoice, status mails (2026-10-03)

- `addresses` table = shopper book (`label/name/phone/address/city/postcode`, `is_default_delivery`, `is_default_billing`); first entry becomes both defaults; `User::ensureAddressBook()` seeds from legacy profile columns once. Checkout snapshots BOTH delivery + billing onto `orders` (`billing_*` columns, `Order::billTo()` falls back to delivery for legacy rows) — never trust address ids from the browser, only posted fields.
- VAT is inclusive: rate from `CompanyDetails::cached()->vat_percent`; `orders.vat_percent/vat_amount` snapshotted at `place()` (`vat = total × rate/(100+rate)`).
- Status mails: `OrderPlaced` on confirm, `OrderStatusUpdated` on packed/out_for_delivery/cancelled (always, incl. self-cancel), `OrderDelivered` on delivered — never double-send; all via fire-and-forget `Order::sendMail`. Receipt emails share `emails/orders/partials/*`; portal/track/success share `frontend/partials/order-journey.blade.php` (stepper + history).
- Invoice: `admin/orders/invoice.blade.php` (tables + inline styles only — dompdf-safe, base64 logo) for browser print, `orders.invoicePdf` download via `barryvdh/laravel-dompdf`, same PDF attached to `OrderPlaced`. Avoid complex expressions inside Blade directives (`@json(fn…)` breaks the compiler) — build data in controllers, use `{!! json_encode() !!}` in views.

## Delivery zones (2026-10-03)

- `delivery_zones` (name/is_active/sort) + `delivery_zone_postcodes` (zone FK, normalized uppercase prefix, unique per zone). Empty table = deliver everywhere; any rows = enforce.
- `DeliveryZone::matching($postcode)` (case/space-insensitive, longest prefix wins, active only — iterate rows, never `pluck` keyed by zone id or multi-prefix zones collapse) and `serves()` gate. `place()` rejects outside zones with 422; `POST checkout/postcode` gives the live check (informational only). Admin CRUD + toggle under Delivery Zones; checkout JS debounces postcode input and blocks submit on known-bad.

## Refunds (2026-10-03)

- `orders.refunded_amount` cumulative; `payment_status` gains `partially_refunded`/`refunded` (`isPaid()` covers paid + partial; `paymentStatusLabel()` for display — never `ucfirst(payment_status)`).
- Gateway first, ledger second: `CheckoutController::stripeRefund` (`POST /v1/refunds` with `payment_intent`) and `paypalRefund` (`POST /v2/payments/captures/{id}/refund`) throw on refusal (incl. unconfigured gateway); `Admin/OrderController@refund` validates `amount ≤ refundableAmount()`, writes history with gateway ref. COD/unpaid/full orders refuse via `refundableAmount() === 0`. Refund rows on admin show, invoice, mail `_receipt`.

## Substitutions (2026-10-03)

- `orders.substitution_preference` (`substitute|refund|call`, required at checkout, shown to shopper + admin packing view); `order_items.status` (`ok|unavailable`, default ok).
- Packing: `Admin/OrderController@markUnavailable` (blocked on delivered/cancelled + repeat lines) refunds `min(line_total, refundableAmount())` via gateway when money moved, else just marks; writes history; item badges on admin show, portal detail, success, invoice, mail `_receipt`.
- Blade rule: never put two `@if` directives on one line (compiler leaves the second raw) — split lines; mail partials must be render-tested (`OrderStatusUpdated::render()`), `Mail::fake` never compiles views.

## BOGO (2026-10-03, independent promo)

- `bogo_offers` (product FK, nullable variant FK = all packs, buy_qty/free_qty, starts/ends, status/sort); `order_items.promo_label/free_qty` snapshot at `place()`.
- Engine lives in `BagController::detailed()` (single `liveAll()` per cycle, `matchIn()` per line; variant-specific beats product-wide; free units = floor(qty/(buy+free))×free) — never in the browser; subtotal already reduced so `priceBag`/coupons/points/VAT all see promo prices. `productCard` adds `bogo{buy,free,label}`, details adds `bogoOffers[]`.
- Admin BOGO page (product select2 → variant dropdown via `product-variants.list`, buy/free, optional window) + sidebar item. Storefront: card `badge-bogo`, details `bogo-panel`, bag/checkout `promo-tag` + savings rows (server blade + `egf.js renderBagPage/updateBagSummary`).
- Blade: `!empty()` inside directives miscompiles — use plain truthiness (`@if ($x)`); one directive per line.

## Flash sales (2026-10-03, scheduled layer over offer_price)

- `flash_sales` (product FK, nullable variant FK = all packs, promo_price, required starts/ends, status/sort). Everyday `offer_price` untouched; flash wins only inside its window, then vanishes — "make permanent" copies it into `offer_price`.
- Resolution: `FlashSale::liveMap()` (variant + product entries, lowest wins) + `priceFor($vid,$pid,$map)` (specific beats product-wide); applied as `min(shelf, flash)` in `BagController::detailed` (preloaded once), `productCard`/`cardVariants`/`productDetail` (per-card map, same cost class as `favourited`), `hasDeal()` for offers rail/filter, `dealFloor()` for the price slider.
- Admin Flash Sales page (variant picker, promo must sit below shelf price, live badge, make-permanent, toggle) + sidebar item. Storefront: `badge-flash` + `flashEnds` countdown text on cards, red flash panel on details, picker `old/save` derived from flash-adjusted selling.
- Never forget model imports when referencing new models from controllers (missing `use` = 500 on every listing).

## Bundles (2026-10-03, dynamic mix-and-match)

- `bundle_offers` (name/required_qty/bundle_price/optional window/status/sort) + `bundle_offer_categories` (whole ranges join, future packs included) + `bundle_offer_variants` (explicit SKUs); pool = union.
- Engine `BundleOffer::applyToLines(&$lines)` runs after BOGO on paid units only (`qty − free_qty`): cheapest-first grouping, worthless groups skipped (`shelf ≤ P`), proportional split of P (every unit ≤ shelf, shares sum exactly, last absorbs dust); one unit joins one bundle; labels combine (`BOGO · Bundle`). Returns saving; `detailed()` gains `bundle_discount`.
- Cards carry `bundle{label,name}` via precomputed `coverMap()` threaded through `productCard($p, $cover)` (never per-card pool queries); details adds `bundleOffers[]` with up to 6 partner products (`bundleOffersFor`). Bag/checkout show Bundle savings rows (blade + `egf.js updateBagSummary`); promo labels flow to order items, mails, invoice, admin show unchanged.

## Offer campaigns (2026-10-03, parent over BOGO/flash/bundle)

- `offers` (name, required starts/ends, status/sort); `bogo_offers/flash_sales/bundle_offers.offer_id` nullable FK `nullOnDelete` — attached rows follow the parent, blank means standalone (nothing breaks).
- Effective live = own status + own window + parent live (`isLive()` in each child; `liveAll()` eager-loads `offer`). Child dates optional when attached; parent switch pauses everything at once; deleting a parent orphans children to standalone.
- Admin Promotions group: Offers (create once → +BOGO/+Flash/+Bundle buttons with `?offer_id` preset, item lists with edit links) + the three type pages (each form has a parent-offer select, index badges show the parent).

## Diet, allergens, nutrition (2026-10-03)

- `products`: `origin_country`, flags `is_vegetarian/is_vegan/is_halal/is_organic/is_gluten_free`, `nutrition_per` + 8 nullable numerics (`energy_kcal…salt_g`). `allergens` fixed UK-14 table (`Allergen::seedDefaults()`, called from `GroceryDemoSeeder::run`) + `allergen_product` pivot; never auto-create allergens on import.
- Admin manage Basic tab: origin, diet checkboxes, allergen checkboxes, nutrition grid (posts with `basicForm` FormData; unchecked = false via `$request->boolean`, allergens `sync(input ?? [])`).
- Excel: 16 diet columns in LEAD (after Sort Order); export writes flags/allergen names/numerics; import parses flags via `bool()`, allergens matched case-insensitively (unknown = row error), nutrition optional ≥ 0; conflicts cover origin/diets/nutrition/allergens; commit writes product fields + `allergens()->sync` on first row; Reference sheet lists allergens; Guide documents all.
- Shop: `?diet[]=vegan…` (sanitized to 5 flags) + `?free_from[]=nuts…` (sanitized to known slugs), collection filters, pretty-URL `redirect` carries them, pills toggle + multi-select + active chips. Details: diet badges, origin line, allergy box, nutrition table. Tests assert absence via product URLs (`/product/slug`) — names leak through `EGF_CATALOG` JSON.

## Live recheck 2026-09-29 (fresh seed → HTTP end-to-end, all green)

- Guest: home/shop/details/bag/checkout/track + about/gallery/contact/privacy/terms/refund/faq all 200; `/offers` 301s to `/shop/offers`; `/collections` is gone (404s); shop URLs are pretty (`/shop/lamb`, `/shop/offers`, query `?category=`/`?only_offers=1` 301 to them, unknown slugs 404); shop filters are category tree (parent includes descendants) + `only_offers` toggle + search + capped price slider + sort, 24/page cumulative with Load more, all SPA-navigated without reload; seeded slugs are `lamb-leg-bone-in`, `basmati-rice-extra-long`, `sea-salt-flakes`; SKUs look like `EGF89913`; order numbers sequence `EGF-10001…`. COD place → bag cleared, subtotal+fee+total snapshotted, status Confirmed/unpaid; success page owner-only (strangers 404); tracking works with lowercase number + spaced phone. Full-journey test (`ShopFlowTest`: register → shop → bag → COD + email → success → account → track → reorder → cancel) must keep passing. Favourites: `favourites` table (user+product unique), heart toggle (JSON, guests → login), `/favourites` page + move-all, hearts on cards + details via `productCard()['favourited']` (request-memoized). Emails: `OrderPlaced` on COD place + online confirm, `OrderDelivered` on delivered move (`Order::sendMail`, fire-and-forget, skipped without address); checkout collects optional `orders.email`, fallback is the shopper's account email. Cookie banner in layout (localStorage `egf-consent`). Footer socials render from company fields (brand icons added to `icons.js`); footer tagline is `footer_content`.
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

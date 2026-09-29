<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\DeliverySlot;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\Gallery;
use App\Models\GalleryCategory;
use App\Models\OptionGroup;
use App\Models\OptionValue;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Slider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GroceryDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedGroceryCatalog();
        $this->seedGrocerySliders();
        $this->seedOrderStatuses();
        $this->seedDeliverySlots();
        $this->seedShopSettings();

        $data = [
            'faqs' => [
                0 => [
                    'id' => 'faq-minimum',
                    'cat' => 'ordering',
                    'badge' => 'Ordering',
                    'q' => 'Is there a minimum order?',
                    'a' => 'Yes — the minimum order for delivery is £15. Delivery is £2.99–£3.99 depending on your time window, and free on orders over £50.',
                ],
                1 => [
                    'id' => 'faq-slots',
                    'cat' => 'delivery',
                    'badge' => 'Delivery',
                    'q' => 'When will my groceries arrive?',
                    'a' => 'Pick a day (up to 5 days ahead) and a time window — Morning (8–12), Afternoon (12–5) or Evening (5–9) — at checkout. Order before 8pm for next-day slots. Everything travels cold-packed.',
                ],
                2 => [
                    'id' => 'faq-freshness',
                    'cat' => 'freshness',
                    'badge' => 'Freshness',
                    'q' => 'What if something arrives past its best?',
                    'a' => 'Tell us within 24 hours with a photo and we will refund or replace the item — no need to send anything back. Fresh meat and produce are picked the same day they travel.',
                ],
                3 => [
                    'id' => 'faq-substitutions',
                    'cat' => 'ordering',
                    'badge' => 'Ordering',
                    'q' => 'What happens if an item is out of stock after I order?',
                    'a' => 'We never substitute silently. If a line cannot be fulfilled we remove it before packing, refund that line in full, and note it on your order so you can see exactly what changed.',
                ],
                4 => [
                    'id' => 'faq-payment',
                    'cat' => 'payments',
                    'badge' => 'Payments',
                    'q' => 'How can I pay?',
                    'a' => 'Cash on delivery, card online via Stripe, or PayPal. Online payments are verified with the gateway before we confirm your order — the prices are always re-checked from our shelves, never from your screen.',
                ],
                5 => [
                    'id' => 'faq-account',
                    'cat' => 'ordering',
                    'badge' => 'Ordering',
                    'q' => 'Do I need an account to order?',
                    'a' => 'No — guest checkout works fully. An account simply remembers your details, shows your order history with live status, and lets you buy everything again in one tap.',
                ],
                6 => [
                    'id' => 'faq-cancel',
                    'cat' => 'ordering',
                    'badge' => 'Ordering',
                    'q' => 'Can I cancel or change my order?',
                    'a' => 'Yes, while it is still New or Confirmed and unpaid (or cash on delivery) — cancel it yourself from your account. Paid online orders are refunded if they have not been packed; just contact us.',
                ],
                7 => [
                    'id' => 'faq-offers',
                    'cat' => 'freshness',
                    'badge' => 'Freshness',
                    'q' => 'Where do the offer prices come from?',
                    'a' => 'Every product carries its full price and, where reduced, a genuine lower offer price per pack. The Offers page lists only real reductions, biggest saving first.',
                ],
            ],
            'faqCats' => [
                'ordering' => 'Ordering',
                'delivery' => 'Delivery',
                'freshness' => 'Freshness & Quality',
                'payments' => 'Payments',
            ],
            'gallery' => [
                0 => ['src' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1600&q=85', 'cat' => 'produce', 'caption' => 'Fresh produce, picked daily'],
                1 => ['src' => 'https://images.unsplash.com/photo-1488459716781-31db52582fe9?auto=format&fit=crop&w=1600&q=85', 'cat' => 'produce', 'caption' => 'Morning veg box'],
                2 => ['src' => 'https://images.unsplash.com/photo-1490474418585-ba9bad8fd0ea?auto=format&fit=crop&w=1600&q=85', 'cat' => 'produce', 'caption' => 'Fresh fruit, every day'],
                3 => ['src' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=1600&q=85', 'cat' => 'bakery', 'caption' => 'Baked every morning'],
                4 => ['src' => 'https://images.unsplash.com/photo-1486297678162-eb2a19b0a32d?auto=format&fit=crop&w=1600&q=85', 'cat' => 'dairy', 'caption' => 'Farmhouse cheese counter'],
                5 => ['src' => 'https://images.unsplash.com/photo-1550583724-b2692b85b150?auto=format&fit=crop&w=1600&q=85', 'cat' => 'dairy', 'caption' => 'Fresh milk, daily'],
                6 => ['src' => 'https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?auto=format&fit=crop&w=1600&q=85', 'cat' => 'pantry', 'caption' => 'Free-range eggs'],
                7 => ['src' => 'https://images.unsplash.com/photo-1547514701-42782101795e?auto=format&fit=crop&w=1600&q=85', 'cat' => 'produce', 'caption' => 'Juicing oranges'],
                8 => ['src' => 'https://images.unsplash.com/photo-1518843875459-f738682238a6?auto=format&fit=crop&w=1600&q=85', 'cat' => 'produce', 'caption' => 'Leafy greens'],
            ],
            'galleryCats' => [
                'produce' => 'Fresh Produce',
                'bakery' => 'Bakery',
                'dairy' => 'Dairy',
                'pantry' => 'Pantry',
            ],
        ];

        // FAQ categories + FAQs
        $faqCatIds = [];
        $i = 0;
        foreach ($data['faqCats'] as $slug => $label) {
            $c = FaqCategory::firstOrCreate(
                ['slug' => $slug],
                ['name' => $label, 'status' => true, 'sort_order' => $i++]
            );
            $faqCatIds[$slug] = $c->id;
        }
        $j = 0;
        foreach ($data['faqs'] as $f) {
            Faq::firstOrCreate(
                ['question' => $f['q']],
                [
                    'faq_category_id' => $faqCatIds[$f['cat']] ?? reset($faqCatIds),
                    'answer' => $f['a'],
                    'badge' => $f['badge'] ?? null,
                    'status' => true,
                    'sort_order' => $j++,
                ]
            );
        }

        // Gallery categories + items
        $galCatIds = [];
        $i = 0;
        foreach ($data['galleryCats'] as $slug => $label) {
            $c = GalleryCategory::firstOrCreate(
                ['slug' => $slug],
                ['name' => $label, 'status' => true, 'sort_order' => $i++]
            );
            $galCatIds[$slug] = $c->id;
        }
        $j = 0;
        foreach ($data['gallery'] as $g) {
            Gallery::firstOrCreate(
                ['caption' => $g['caption']],
                [
                    'gallery_category_id' => $galCatIds[$g['cat']] ?? reset($galCatIds),
                    'image' => $g['src'],
                    'status' => true,
                    'sort_order' => $j++,
                ]
            );
        }
    }

    /** Demo hero slides matching the raw theme (badge, title, subtitle, two buttons). Images can be uploaded later in admin. */
    private function seedGrocerySliders(): void
    {
        $sort = 0;
        foreach ([
            [
                'badge' => 'Fresh picks · Everyday goodness',
                'title' => 'Good food. Better days.',
                'subtitle' => 'From just-picked produce to pantry favourites, bring home the good stuff without leaving home.',
                'btn_text' => 'Shop groceries', 'btn_url' => '/collections',
                'btn_text2' => 'Explore offers', 'btn_url2' => '/collections',
            ],
            [
                'badge' => 'Market garden · Picked today',
                'title' => 'Produce with real character.',
                'subtitle' => 'Vegetables and leaves chosen by hand each morning, from growers we actually know.',
                'btn_text' => 'Shop produce', 'btn_url' => '/collections',
                'btn_text2' => 'About us', 'btn_url2' => '/about',
            ],
            [
                'badge' => 'Counter service · Premium quality',
                'title' => 'The good counter cuts.',
                'subtitle' => 'Meat and deli favourites, prepared to order and packed cold for the journey home.',
                'btn_text' => 'Shop fresh meat', 'btn_url' => '/collections',
                'btn_text2' => null, 'btn_url2' => null,
            ],
        ] as $s) {
            Slider::firstOrCreate(
                ['title' => $s['title']],
                [...$s, 'image' => null, 'sort_order' => $sort++, 'is_active' => true]
            );
        }
    }

    /** Order lifecycle: every status change writes a row in order_status_histories. */
    private function seedOrderStatuses(): void
    {
        $sort = 0;
        foreach ([
            ['slug' => 'new', 'name' => 'New', 'color' => '#B45309', 'is_final' => false],
            ['slug' => 'confirmed', 'name' => 'Confirmed', 'color' => '#1D4ED8', 'is_final' => false],
            ['slug' => 'packed', 'name' => 'Packed', 'color' => '#6D28D9', 'is_final' => false],
            ['slug' => 'out_for_delivery', 'name' => 'Out for Delivery', 'color' => '#0E7490', 'is_final' => false],
            ['slug' => 'delivered', 'name' => 'Delivered', 'color' => '#1A2E22', 'is_final' => true],
            ['slug' => 'cancelled', 'name' => 'Cancelled', 'color' => '#B91C1C', 'is_final' => true],
        ] as $s) {
            OrderStatus::firstOrCreate(
                ['slug' => $s['slug']],
                [...$s, 'sort_order' => $sort++, 'is_active' => true]
            );
        }
    }

    /** Delivery windows shoppers pick at checkout, each with its own fee. */
    private function seedDeliverySlots(): void
    {
        $sort = 0;
        foreach ([
            ['name' => 'Morning', 'starts_at' => '08:00', 'ends_at' => '12:00', 'fee' => 2.99, 'cutoff_hour' => 20],
            ['name' => 'Afternoon', 'starts_at' => '12:00', 'ends_at' => '17:00', 'fee' => 2.99, 'cutoff_hour' => 20],
            ['name' => 'Evening', 'starts_at' => '17:00', 'ends_at' => '21:00', 'fee' => 3.99, 'cutoff_hour' => 20],
        ] as $s) {
            DeliverySlot::firstOrCreate(
                ['name' => $s['name']],
                [...$s, 'sort_order' => $sort++, 'is_active' => true]
            );
        }
    }

    /** Shop money rules + payment credentials (keys stay empty until the owner adds them). */
    private function seedShopSettings(): void
    {
        foreach ([
            'delivery_min_order' => '15.00',
            'delivery_free_over' => '50.00',
            'points_per_pound' => '1',
            'points_value' => '0.01',
            'points_min_redeem' => '100',
            'stripe_publishable' => '',
            'stripe_secret' => '',
            'paypal_client_id' => '',
            'paypal_secret' => '',
            'paypal_mode' => 'sandbox',
        ] as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    /** Grocery catalog: categories, reusable option library, templates, products + variants. */
    private function seedGroceryCatalog(): void
    {        // Option groups (the reusable shop-wide vocabulary)
        $groups = [];
        $sort = 0;
        foreach ([
            ['name' => 'Pack Size', 'type' => 'buttons'],
            ['name' => 'Cut Option', 'type' => 'dropdown'],
            ['name' => 'Fat Option', 'type' => 'dropdown'],
        ] as $g) {
            $groups[$g['name']] = OptionGroup::firstOrCreate(
                ['slug' => Str::slug($g['name'])],
                ['name' => $g['name'], 'type' => $g['type'], 'status' => true, 'sort_order' => $sort++]
            );
        }

        // Option values (defined once, referenced by every variant)
        $values = [];
        $defs = [
            'Pack Size' => ['500g', '1kg', '2kg', '5kg'],
            'Cut Option' => ['Large pcs', 'Medium-Cube', 'Thick'],
            'Fat Option' => ['Fat On', 'Fat Off'],
        ];
        foreach ($defs as $groupName => $labels) {
            $sort = 0;
            foreach ($labels as $label) {
                $values[$label] = OptionValue::firstOrCreate(
                    ['option_group_id' => $groups[$groupName]->id, 'slug' => Str::slug($label)],
                    ['label' => $label, 'status' => true, 'sort_order' => $sort++]
                );
            }
        }

        // Categories + option-group templates
        $catIds = [];
        $sort = 0;
        $cats = [
            'Fresh Meat' => ['Pack Size', 'Cut Option', 'Fat Option'],
            'Rice & Grains' => ['Pack Size'],
            'Pantry Essentials' => [],
        ];
        foreach ($cats as $name => $template) {
            $cat = Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'meta_title' => $name.' | Grocery', 'status' => true, 'sort_order' => $sort++]
            );
            $catIds[$name] = $cat->id;
            $sync = [];
            foreach (array_values($template) as $i => $groupName) {
                $sync[$groups[$groupName]->id] = ['sort_order' => $i];
            }
            $cat->optionGroups()->sync($sync);
        }

        // Products with their variant rows. Each variant: [sku, mrp, offer, in_stock, default, value labels]
        $products = [
            [
                'slug' => 'lamb-leg-bone-in',
                'name' => 'Lamb Leg Bone-In',
                'category' => 'Fresh Meat',
                'tagline' => 'Halal British lamb leg, butchered to your cut and fat preference.',
                'highlights' => "100% British halal lamb\nMatured 7 days for flavour\nFreezer-friendly",
                'extra' => [
                    ['Cooking suggestion', 'Roast at 180°C for 25 mins per 500g plus 25 mins rest. Serve pink in the middle.'],
                    ['Allergy advice', 'Prepared in an environment that handles no major allergens. Gluten-free.'],
                    ['Storage', 'Keep refrigerated below 4°C. Use within 2 days or freeze on day of delivery.'],
                ],
                'featured' => true,
                'variants' => [
                    ['EGF89913', 19.99, 16.99, true, true, ['1kg', 'Thick', 'Fat On']],
                    ['EGF89914', 19.49, 16.49, true, false, ['1kg', 'Thick', 'Fat Off']],
                    ['EGF89915', 18.99, null, true, false, ['1kg', 'Medium-Cube', 'Fat On']],
                    ['EGF89916', 18.49, 15.99, false, false, ['1kg', 'Medium-Cube', 'Fat Off']],
                    ['EGF89917', 37.99, 32.99, true, false, ['2kg', 'Thick', 'Fat On']],
                    ['EGF89918', 36.99, null, true, false, ['2kg', 'Thick', 'Fat Off']],
                    ['EGF89919', 35.99, 30.99, true, false, ['2kg', 'Medium-Cube', 'Fat On']],
                    ['EGF89920', 34.99, 29.99, false, false, ['2kg', 'Medium-Cube', 'Fat Off']],
                ],
            ],
            [
                'slug' => 'basmati-rice-extra-long',
                'name' => 'Basmati Rice Extra Long',
                'category' => 'Rice & Grains',
                'tagline' => 'Aged extra-long grain basmati, perfect for biryani.',
                'highlights' => "Aged 2 years for aroma\nExtra-long grain, non-sticky\n",
                'extra' => [
                    ['Cooking suggestion', 'Rinse, soak 30 mins, then boil 1 part rice to 1.5 parts water for 12 mins. Rest 5 mins.'],
                    ['Storage', 'Store in a cool dry place in an airtight container.'],
                ],
                'featured' => true,
                'variants' => [
                    ['RICE-500', 4.99, 3.99, true, false, ['500g']],
                    ['RICE-1K', 8.99, 7.49, true, true, ['1kg']],
                    ['RICE-5K', 34.99, 29.99, true, false, ['5kg']],
                ],
            ],
            [
                'slug' => 'sea-salt-flakes',
                'name' => 'Sea Salt Flakes',
                'category' => 'Pantry Essentials',
                'tagline' => 'Hand-harvested flaky sea salt for finishing.',
                'highlights' => "Hand-harvested flakes\nNo anti-caking agents",
                'extra' => [
                    ['Storage', 'Keep dry. Clumping is natural — break up with a spoon.'],
                ],
                'featured' => false,
                'variants' => [
                    ['SALT-01', 2.49, null, true, true, []],
                ],
            ],
        ];

        $order = 0;
        foreach ($products as $p) {
            $product = Product::updateOrCreate(
                ['slug' => $p['slug']],
                [
                    'category_id' => $catIds[$p['category']] ?? null,
                    'name' => $p['name'],
                    'tagline' => $p['tagline'],
                    'highlights' => $p['highlights'] ?? null,
                    'meta_title' => $p['name'].' | Grocery',
                    'is_featured' => $p['featured'],
                    'status' => true,
                    'sort_order' => $order++,
                ]
            );
            $sort = 0;
            foreach ($p['variants'] as [$sku, $mrp, $offer, $inStock, $isDefault, $labels]) {
                $variant = ProductVariant::updateOrCreate(
                    ['sku' => $sku],
                    [
                        'product_id' => $product->id,
                        'mrp' => $mrp,
                        'offer_price' => $offer,
                        'in_stock' => $inStock,
                        'is_default' => $isDefault,
                        'status' => true,
                        'sort_order' => $sort++,
                    ]
                );
                $variant->values()->sync(
                    collect($labels)->map(fn ($l) => $values[$l]->id)->all()
                );
            }
            foreach ($p['extra'] ?? [] as $i => [$label, $value]) {
                $product->extraAttributes()->updateOrCreate(
                    ['label' => $label],
                    ['value' => $value, 'sort_order' => $i]
                );
            }
        }
    }
}

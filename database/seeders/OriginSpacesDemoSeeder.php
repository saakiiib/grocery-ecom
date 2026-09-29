<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\Gallery;
use App\Models\GalleryCategory;
use App\Models\OptionGroup;
use App\Models\OptionValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Slider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OriginSpacesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedGroceryCatalog();
        $this->seedGrocerySliders();

        $data = [
            'faqs' => [
                0 => [
                    'id' => 'faq-leadtime',
                    'cat' => 'times',
                    'badge' => 'Lead Times',
                    'q' => 'What is the full timeline from factory order in China to delivery on my UK site?',
                    'a' => 'The complete door-to-door cycle typically takes 8 to 11 weeks: (1) Custom CAD architectural approval & factory manufacturing: 25 to 35 days; (2) Pre-delivery factory inspection (PDI) with live video walkthrough: 3 to 5 days; (3) Ocean shipping to UK: 28 to 35 days; (4) UK central warehouse intake, quality check & final haulage to your site: 24 to 48 hours. Once on site, unfolding and leveling takes under 1 hour.',
                ],
                1 => [
                    'id' => 'faq-warehouse',
                    'cat' => 'shipping',
                    'badge' => 'Warehouse Logistics',
                    'q' => 'How are expandable houses delivered to my UK site or property?',
                    'a' => 'Our expandable houses are engineered to fold into an ultra-compact ISO-standard road freight container profile (approx. 2.25m width). Built-to-order units are imported directly to our central UK distribution warehouse, where they receive a comprehensive 50-point Pre-Delivery Inspection (PDI). From our UK warehouse, our specialized rigid flatbed truck equipped with an on-board hydraulic Hiab crane transports the unit directly to your plot or garden, placing it safely onto your prepared foundation pads or ground screws. You can also arrange haulier collection directly from our depot.',
                ],
                2 => [
                    'id' => 'faq-planning',
                    'cat' => 'planning',
                    'badge' => 'UK Planning & Laws',
                    'q' => 'Do I need UK planning permission for an expandable house, annex, or garden office?',
                    'a' => 'In many residential cases in the UK, an expandable pod qualifies under Permitted Development or the Caravan Sites Act 1968 (Section 13) as a non-permanent, moveable structure. This means garden annexes for family members, garden offices, or temporary leisure pods often do not require full planning permission. For commercial uses (such as roadside coffee shops or holiday glamping sites), planning permission or a Certificate of Lawful Development is usually advised. We provide full architectural elevation drawings and specification sheets to assist your local council submission.',
                ],
                3 => [
                    'id' => 'faq-insulation',
                    'cat' => 'specs',
                    'badge' => 'UK Climate Rating',
                    'q' => 'Are these houses warm enough for a cold UK winter? What is the insulation grade?',
                    'a' => 'Our UK-specification models are purpose-built for the British climate. We use 100mm high-density PIR or non-combustible Rockwool core sandwich panels, achieving a thermal U-value ≤ 0.18 W/m²K, which complies with UK Building Regulations Part L. Paired with argon-gas filled double-glazed thermal break windows and insulated composite flooring, the buildings retain heat efficiently with minimal heating required.',
                ],
                4 => [
                    'id' => 'faq-foundations',
                    'cat' => 'specs',
                    'badge' => 'Site Ground Prep',
                    'q' => 'What kind of base or ground foundation is required on my UK site?',
                    'a' => 'You do not need an expensive poured concrete foundation. Most UK installations use galvanized helical ground screws or 6 to 8 concrete leveling pad piers set at key outriggers load points. The house features built-in heavy-duty hydraulic outriggers that allow precision leveling up to ±2mm even on gentle slopes.',
                ],
                5 => [
                    'id' => 'faq-electric',
                    'cat' => 'planning',
                    'badge' => 'British Standards',
                    'q' => 'Are the electrical and plumbing systems compliant with British Standards?',
                    'a' => 'Yes. All units destined for the UK are pre-wired in the factory to BS 7671 (18th Edition IET Wiring Regulations), fitted with a certified UK consumer unit, double-pole RCBO breakers, and standard UK 3-pin sockets. Plumbing pipes use standard UK 15mm/22mm compression fittings and 110mm push-fit waste connections compatible with UK municipal mains or septic tanks.',
                ],
                6 => [
                    'id' => 'faq-custom',
                    'cat' => 'specs',
                    'badge' => 'Custom Factory Specs',
                    'q' => 'Can I order custom layouts, extra windows, or a commercial cafe serving hatch?',
                    'a' => 'Yes — every unit is built to order. Choose your 20/30/40ft chassis, partition layouts, extra glazing and commercial options such as the gas-strut serving hatch, 3-phase prep and hygiene wall panels. Custom CAD drawings are issued for your sign-off before manufacturing begins.',
                ],
                7 => [
                    'id' => 'faq-price',
                    'cat' => 'times',
                    'badge' => 'Lead Times & Pricing',
                    'q' => 'How is pricing structured, and when do I pay?',
                    'a' => 'Factory base prices run from £24,800 to £45,900 ex-works, plus UK haulage and VAT. There is no deposit for CAD drawings; production follows a staged schedule — deposit, production milestone, PDI release and final balance on delivery.',
                ],
                8 => [
                    'id' => 'faq-customs',
                    'cat' => 'shipping',
                    'badge' => 'Shipping & Logistics',
                    'q' => 'Who handles customs, VAT and port clearance?',
                    'a' => 'We do. Units ship from Shanghai/Ningbo to Felixstowe or Southampton, clear customs under our management, then transfer to our UK Central Distribution Warehouse for the 50-point PDI before Hiab delivery to your site.',
                ],
            ],
            'faqCats' => [
                'times' => 'Lead Times',
                'specs' => 'Custom Factory Specs',
                'shipping' => 'Shipping & Logistics',
                'planning' => 'UK Planning & Standards',
            ],
            'gallery' => [
                0 => [
                    'id' => 'photo-1512917774080-9991f1c4c750',
                    'cat' => 'exterior',
                    'caption' => 'Expanded Villa at Dusk',
                ],
                1 => [
                    'id' => 'photo-1600596542815-ffad4c1539a9',
                    'cat' => 'exterior',
                    'caption' => 'Estate Exterior by Day',
                ],
                2 => [
                    'id' => 'photo-1518780664697-55e3ad937233',
                    'cat' => 'exterior',
                    'caption' => 'Lakeside Cabin Pod',
                ],
                3 => [
                    'id' => 'photo-1513694203232-719a280e022f',
                    'cat' => 'interior',
                    'caption' => 'Open-Plan Living Space',
                ],
                4 => [
                    'id' => 'photo-1616486338812-3dadae4b4ace',
                    'cat' => 'interior',
                    'caption' => 'Dressing & Joinery Suite',
                ],
                5 => [
                    'id' => 'photo-1542314831-068cd1dbfeeb',
                    'cat' => 'interior',
                    'caption' => 'Hotel-Grade Guest Suite',
                ],
                6 => [
                    'id' => 'photo-1556911220-e15b29be8c8f',
                    'cat' => 'kitchenbath',
                    'caption' => 'Scullery in Nero Marble',
                ],
                7 => [
                    'id' => 'photo-1600585154340-be6161a56a0c',
                    'cat' => 'kitchenbath',
                    'caption' => 'Open Kitchen & Living',
                ],
                8 => [
                    'id' => 'photo-1584622650111-993a426fbf0a',
                    'cat' => 'kitchenbath',
                    'caption' => 'Stone Bath Sanctuary',
                ],
            ],
            'galleryCats' => [
                'exterior' => 'Exterior',
                'interior' => 'Interior',
                'kitchenbath' => 'Kitchen & Bath',
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
                    'image' => 'https://images.unsplash.com/'.$g['id'].'?auto=format&fit=crop&w=1600&q=85',
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

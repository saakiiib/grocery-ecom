<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Download;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\FloorZone;
use App\Models\Gallery;
use App\Models\GalleryCategory;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OriginSpacesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'products' => [
                0 => [
                    'id' => 'hs-ktc-01',
                    'modelCode' => 'HS-KTC-01/MON',
                    'name' => 'The Monolith Calacatta Viola Island Suite',
                    'category' => 'Kitchen',
                    'discipline' => 'Kitchen',
                    'tagline' => 'Precision-mitered Italian marble island with integrated induction, bronze fluted reveals, and concealed prep cabinetry.',
                    'price' => 'From £28,500',
                    'leadTime' => '8-10 Weeks',
                    'heroImage' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=85',
                    'materials' => [
                        0 => 'Honed Calacatta Viola Marble',
                        1 => 'Solid Smoked European Oak',
                        2 => 'Brushed Architectural Bronze',
                    ],
                    'dimensions' => '3,800mm (L) × 1,150mm (W) × 920mm (H)',
                    'warranty' => '15-Year Structural Stone & Joinery Guarantee',
                    'specs' => [
                        0 => 'CNC-mitered 45-degree waterfall edge profiles with continuous bookmatched stone veining',
                        1 => 'Integrated flush-mounted invisible 4-zone induction cooking surfaces directly beneath stone',
                        2 => 'Soft-closing solid oak drawer boxes with dovetailed joints and magnetic utensil inserts',
                        3 => 'Concealed 240V architectural power pop-ups with brushed bronze flush trim plates',
                    ],
                ],
                1 => [
                    'id' => 'hs-bth-02',
                    'modelCode' => 'HS-BTH-02/NER',
                    'name' => 'Aurelia Nero Marquina Freestanding Bath',
                    'category' => 'Bath & Wellness',
                    'discipline' => 'Bath & Wellness',
                    'tagline' => 'Hand-sculpted monolithic bath carved from a single continuous block of Spanish Nero Marquina marble.',
                    'price' => 'From £16,800',
                    'leadTime' => '6-8 Weeks',
                    'heroImage' => 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=1200&q=85',
                    'materials' => [
                        0 => 'Monolithic Nero Marquina Marble',
                        1 => 'Hand-Polished Satin Wax Finish',
                        2 => 'Concealed Brass Clicker Waste',
                    ],
                    'dimensions' => '1,820mm (L) × 880mm (W) × 580mm (H) — 480kg dry weight',
                    'warranty' => '25-Year Stone Integrity Warranty',
                    'specs' => [
                        0 => 'Carved from a singular 2.5-tonne Spanish marble quarry block with zero joint lines',
                        1 => 'Ergonomically contoured 28-degree internal recline slope tested for prolonged hydro-relaxation',
                        2 => 'Hand-finished with breathable hydrophobic microporous wax preventing mineral staining',
                        3 => 'Supplied with pre-drilled centered waste and flexible UK-compliant waste connection kit',
                    ],
                ],
                2 => [
                    'id' => 'hs-lgt-03',
                    'modelCode' => 'HS-LGT-03/BRZ',
                    'name' => 'Equinox Kinetic Brass Suspension Ring',
                    'category' => 'Sculptural Lighting',
                    'discipline' => 'Sculptural Lighting',
                    'tagline' => 'Precision-counterbalanced architectural mobile chandelier crafted from unlacquered solid brass and mouth-blown opaline globes.',
                    'price' => 'From £4,950',
                    'leadTime' => '3-4 Weeks',
                    'heroImage' => 'https://images.unsplash.com/photo-1513506003901-1e6a229e2d15?auto=format&fit=crop&w=1200&q=85',
                    'materials' => [
                        0 => 'Solid Architectural CZ121 Brass',
                        1 => 'Mouth-Blown Triplex Opal Glass',
                        2 => 'Braided Textile Suspension Cable',
                    ],
                    'dimensions' => '1,400mm (Dia) × Adjustable Drop 600mm-2,400mm',
                    'warranty' => '5-Year Electrical & LED Driver Guarantee',
                    'specs' => [
                        0 => 'Hand-turned solid brass articulation joints with 360-degree rotation axes',
                        1 => 'Fitted with 2700K warm-dim high CRI (95+) integrated architectural LED emitters',
                        2 => 'Compatible with standard UK leading/trailing edge and DALI / Lutron lighting control systems',
                        3 => 'Counterweighted brass discs hand-buffed with fine abrasive scotch-brite finish',
                    ],
                ],
                3 => [
                    'id' => 'hs-jnr-04',
                    'modelCode' => 'HS-JNR-04/BLG',
                    'name' => 'Belgravia Architectural Dressing Suite',
                    'category' => 'Architectural Joinery',
                    'discipline' => 'Architectural Joinery',
                    'tagline' => 'Floor-to-ceiling reeded timber wardrobe system with integrated sensor lighting and saddle leather drawer liners.',
                    'price' => 'From £12,200',
                    'leadTime' => '6-8 Weeks',
                    'heroImage' => 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=1200&q=85',
                    'materials' => [
                        0 => 'Quarter-Sawn English Oak',
                        1 => 'Full Grain Saddle Leather',
                        2 => 'Acoustic Felt Backing',
                        3 => 'Fluted Glass Doors',
                    ],
                    'dimensions' => 'Bespoke modular lengths, standard height 2,450mm to 2,800mm',
                    'warranty' => '10-Year Cabinetry Guarantee',
                    'specs' => [
                        0 => 'Full-height acoustic felt backing panels reduce room echo and reverberation',
                        1 => 'Integrated proximity sensors illuminate soft warm 2400K internal LED strips',
                        2 => 'Hand-stitched English saddle leather jewelry and watch organizer inserts',
                        3 => 'Precision CNC-milled finger-pull shadow gaps with zero visible screw heads',
                    ],
                ],
                4 => [
                    'id' => 'hs-hrd-05',
                    'modelCode' => 'HS-HRD-05/STR',
                    'name' => 'Strata Architectural Hardware Suite',
                    'category' => 'Hardware & Surfaces',
                    'discipline' => 'Hardware & Surfaces',
                    'tagline' => 'Turned from single billets of solid architectural brass with signature precision guilloche diamond knurling.',
                    'price' => 'From £420 per set',
                    'leadTime' => 'In UK Warehouse Stock',
                    'heroImage' => 'https://images.unsplash.com/photo-1558211553-d9326f10c561?auto=format&fit=crop&w=1200&q=85',
                    'materials' => [
                        0 => 'Solid Forged Single-Billet Brass',
                        1 => 'Guilloche Knurling',
                        2 => 'Unlacquered Living Patina',
                    ],
                    'dimensions' => 'Pull handles 280mm & 450mm; Door levers 145mm × 55mm rose',
                    'warranty' => 'Lifetime Mechanical Warranty',
                    'specs' => [
                        0 => 'Machined with aerospace precision from solid unlacquered CZ121 brass',
                        1 => 'Develops a rich, lustrous living patina unique to the environment over time',
                        2 => 'Heavy internal spring return mechanism engineered for 500,000 duty cycles',
                        3 => 'Universal mortice lock compatibility fitting standard UK door preparations',
                    ],
                ],
                5 => [
                    'id' => 'hs-ktc-06',
                    'modelCode' => 'HS-KTC-06/TRV',
                    'name' => 'The Cotswold Roman Travertine Scullery',
                    'category' => 'Kitchen',
                    'discipline' => 'Kitchen',
                    'tagline' => 'Monolithic honed Navona travertine scullery island with integrated fluted apron sink and concealed prep appliances.',
                    'price' => 'From £22,900',
                    'leadTime' => '7-9 Weeks',
                    'heroImage' => 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=1200&q=85',
                    'materials' => [
                        0 => 'Roman Navona Travertine',
                        1 => 'European Canaletto Walnut',
                        2 => 'Hand-Waxed Bronze Trims',
                    ],
                    'dimensions' => '3,600mm (L) × 1,200mm (W) × 915mm (H)',
                    'warranty' => '15-Year Stone & Cabinetry Warranty',
                    'specs' => [
                        0 => 'Carved from continuous travertine blocks sourced directly from Tivoli quarries',
                        1 => 'Triple-sealed with food-safe breathable fluoropolymer stone impregnator',
                        2 => 'Integrated carved undermount stone sink with solid brass waste disposal baffle',
                        3 => 'Built-in scullery electrical raceway with concealed soft-touch push panels',
                    ],
                ],
                6 => [
                    'id' => 'hs-bth-07',
                    'modelCode' => 'HS-BTH-07/SOL',
                    'name' => 'Solace Concealed Thermostatic Shower',
                    'category' => 'Bath & Wellness',
                    'discipline' => 'Bath & Wellness',
                    'tagline' => 'Triple-outlet thermostatic shower valve with 300mm ceiling deluge rose and knurled hand shower wand.',
                    'price' => 'From £2,850',
                    'leadTime' => 'In UK Warehouse Stock',
                    'heroImage' => 'https://images.unsplash.com/photo-1552321554-5fefe8c9ef14?auto=format&fit=crop&w=1200&q=85',
                    'materials' => [
                        0 => 'Solid Forged Brass Cartridge',
                        1 => 'PVD Brushed Gunmetal or Unlacquered Brass',
                    ],
                    'dimensions' => 'Faceplate 320mm × 120mm; Shower Rose 300mm Diameter',
                    'warranty' => '10-Year Valve Cartridge Warranty',
                    'specs' => [
                        0 => 'Vernet wax thermostatic element ensures instant 38°C anti-scald temperature lock',
                        1 => '3-way diverter controls overhead deluge, body jets, and knurled hand shower',
                        2 => 'High-flow rate delivering up to 32 liters/minute at standard 3.0 bar pressure',
                        3 => 'Includes rough-in concealed installation box with pre-tested waterproof seals',
                    ],
                ],
                7 => [
                    'id' => 'hs-lgt-08',
                    'modelCode' => 'HS-LGT-08/ALB',
                    'name' => 'Vapour Spanish Alabaster Sconce',
                    'category' => 'Sculptural Lighting',
                    'discipline' => 'Sculptural Lighting',
                    'tagline' => 'Hand-carved translucent Aragon alabaster disc back-illuminated with ambient warm-dim indirect glow.',
                    'price' => 'From £1,250',
                    'leadTime' => '1-2 Weeks',
                    'heroImage' => 'https://images.unsplash.com/photo-1540932239986-30128078f3c5?auto=format&fit=crop&w=1200&q=85',
                    'materials' => [
                        0 => 'Natural Aragon Alabaster Disc',
                        1 => 'Antiqued Bronze Wall Boss',
                        2 => 'CRI 98+ Diffused LED',
                    ],
                    'dimensions' => '300mm Diameter × 65mm Projection',
                    'warranty' => '5-Year LED & Stone Warranty',
                    'specs' => [
                        0 => 'Each alabaster disc is uniquely veined by nature with warm caramel mineral ribbons',
                        1 => 'Indirect perimeter wash illumination creates a floating celestial ring aesthetic',
                        2 => 'IP44 splashproof rating suitable for bathroom zones 2 and 3 as well as living rooms',
                        3 => 'Phase-cut dimmable with standard UK residential dimmer switches',
                    ],
                ],
                8 => [
                    'id' => 'hs-exp-01',
                    'modelCode' => 'HS-EXP-38/AST',
                    'name' => 'The Aster 38 Expandable Villa',
                    'category' => 'Expandable Homes',
                    'discipline' => 'Expandable Homes',
                    'tagline' => 'Bi-fold dual-wing expandable modular home offering 38.5m² of luxury living deployed in under 60 minutes.',
                    'price' => 'From £36,500',
                    'leadTime' => '8-10 Weeks to UK Delivery',
                    'heroImage' => 'https://images.unsplash.com/photo-1518780664697-55e3ad937233?auto=format&fit=crop&w=1200&q=85',
                    'materials' => [
                        0 => 'Q235B Galvanized Steel Monocoque Chassis',
                        1 => '100mm PIR Insulation (U ≤ 0.18)',
                        2 => 'Low-E Argon Double Glazing',
                    ],
                    'dimensions' => 'Expanded: 5.9m (L) × 6.3m (W) × 2.6m (H) — 38.5m² area',
                    'warranty' => '25-Year Structural Frame Warranty',
                    'specs' => [
                        0 => 'Pre-fitted turnkey ensuite with walk-in rainfall shower, vanity, and flush WC',
                        1 => 'Conforms to Caravan Sites Act 1968 Section 13(2) dimensions for streamlined planning',
                        2 => 'BS 7671 18th Edition certified consumer unit with RCBO protection',
                        3 => 'Fitted with climate inverter heat pump delivering year-round A+ thermal comfort',
                    ],
                ],
                9 => [
                    'id' => 'hs-exp-02',
                    'modelCode' => 'HS-EXP-20/HVN',
                    'name' => 'The Haven 20 Compact Studio',
                    'category' => 'Expandable Homes',
                    'discipline' => 'Expandable Homes',
                    'tagline' => 'Engineered for UK domestic gardens as an executive acoustic office, studio, or private luxury garden annex.',
                    'price' => 'From £24,800',
                    'leadTime' => '6-8 Weeks to UK Delivery',
                    'heroImage' => 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=85',
                    'materials' => [
                        0 => 'Galvanized Anti-Corrosion Steel',
                        1 => 'Architectural Timber Slat Facade',
                        2 => 'Acoustic Soundproofing 42dB',
                    ],
                    'dimensions' => 'Expanded: 5.9m × 3.5m × 2.6m — 20.5m² area',
                    'warranty' => '25-Year Structural Frame Warranty',
                    'specs' => [
                        0 => 'Under 2.5m eaves height permitting straightforward garden boundary placement',
                        1 => 'High-density 42dB acoustic wall insulation ideal for recording and video calls',
                        2 => 'Concealed Cat6 ethernet ports, USB-C rapid charging hubs, and climate inverter',
                        3 => 'Dispatched directly from UK Central Distribution Warehouse via Hiab crane',
                    ],
                ],
                10 => [
                    'id' => 'hs-exp-03',
                    'modelCode' => 'HS-EXP-40/EST',
                    'name' => 'The Estate 40 Triple-Wing Home',
                    'category' => 'Expandable Homes',
                    'discipline' => 'Expandable Homes',
                    'tagline' => 'Our flagship 74m² triple-wing expandable residence featuring 3 bedrooms, 2 bathrooms, and full architectural kitchen.',
                    'price' => 'From £45,900',
                    'leadTime' => '8-12 Weeks to UK Delivery',
                    'heroImage' => 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1200&q=85',
                    'materials' => [
                        0 => 'Heavy-Duty ISO Certified Chassis',
                        1 => 'Triple Glazed Argon Thermal Joinery',
                        2 => 'Full Turnkey Interior Fitout',
                    ],
                    'dimensions' => 'Expanded: 11.8m (L) × 6.3m (W) × 2.7m (H) — 74m² area',
                    'warranty' => '25-Year Structural Frame Warranty',
                    'specs' => [
                        0 => 'Master bedroom with private ensuite bathroom plus secondary family wetroom',
                        1 => 'Integrated luxury kitchen island with induction cooking and concealed dishwasher',
                        2 => 'Reinforced structural floor rated for underfloor hydronic heating coils',
                        3 => 'Fully transportable on standard UK highway low-loader trailers',
                    ],
                ],
            ],
            'catVideos' => [
                'Kitchen' => 'https://assets.mixkit.co/videos/preview/mixkit-modern-kitchen-island-and-living-room-41584-large.mp4',
                'Bath & Wellness' => 'https://assets.mixkit.co/videos/preview/mixkit-luxurious-bathroom-with-a-modern-bathtub-and-large-windows-41582-large.mp4',
                'Sculptural Lighting' => 'https://assets.mixkit.co/videos/preview/mixkit-glass-chandeliers-hanging-from-the-ceiling-42037-large.mp4',
                'Architectural Joinery' => 'https://assets.mixkit.co/videos/preview/mixkit-modern-walk-in-closet-with-clothes-41580-large.mp4',
                'Hardware & Surfaces' => 'https://assets.mixkit.co/videos/preview/mixkit-modern-walk-in-closet-with-clothes-41580-large.mp4',
                'Expandable Homes' => 'https://videos.pexels.com/video-files/3773486/3773486-hd_1920_1080_30fps.mp4',
            ],
            'catImages' => [
                'Kitchen' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=85',
                'Bath & Wellness' => 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=1200&q=85',
                'Sculptural Lighting' => 'https://images.unsplash.com/photo-1513506003901-1e6a229e2d15?auto=format&fit=crop&w=1200&q=85',
                'Architectural Joinery' => 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=1200&q=85',
                'Hardware & Surfaces' => 'https://images.unsplash.com/photo-1558211553-d9326f10c561?auto=format&fit=crop&w=1200&q=85',
                'Expandable Homes' => 'https://images.unsplash.com/photo-1518780664697-55e3ad937233?auto=format&fit=crop&w=1200&q=85',
            ],
            'optGroups' => [
                'config' => [
                    0 => [
                        'id' => 'standard',
                        'name' => 'Standard Bay (3.6m)',
                        'sub' => 'Full Height 2.8m',
                        'price' => 0,
                    ],
                    1 => [
                        'id' => 'extended',
                        'name' => 'Extended Suite (5.2m)',
                        'sub' => 'Walk-in configuration',
                        'price' => 6400,
                    ],
                ],
                'finish' => [
                    0 => [
                        'id' => 'oak',
                        'name' => 'Smoked Muted Oak',
                        'sub' => 'Dark Brushed Grain',
                        'swatch' => '#5A4636',
                        'price' => 0,
                    ],
                    1 => [
                        'id' => 'ash',
                        'name' => 'White Ash & Bone',
                        'sub' => 'Light Mineral Wash',
                        'swatch' => '#D8CFBC',
                        'price' => 1200,
                    ],
                    2 => [
                        'id' => 'charcoal',
                        'name' => 'Charcoal Composite',
                        'sub' => 'Matte Non-Reflective',
                        'swatch' => '#2D2D2D',
                        'price' => 900,
                    ],
                    3 => [
                        'id' => 'hinoki',
                        'name' => 'Japanese Hinoki',
                        'sub' => 'Pale Cedar Slats',
                        'swatch' => '#CBB296',
                        'price' => 2400,
                    ],
                ],
                'glazing' => [
                    0 => [
                        'id' => 'bronze',
                        'name' => 'Bronze Anodized Flush Profiles (Included)',
                        'sub' => 'Concealed soft-close pivots',
                        'price' => 0,
                        'tag' => 'Standard',
                    ],
                    1 => [
                        'id' => 'fluted',
                        'name' => 'Smoked Fluted Acoustic Glass Bays',
                        'sub' => 'Integrated 2700K perimeter LED extrusion',
                        'price' => 2800,
                        'tag' => '+£2,800',
                    ],
                ],
                'upgrade' => [
                    0 => [
                        'id' => 'led',
                        'name' => 'Smart Perimeter LED System',
                        'sub' => 'App-controlled 2200–4000K scenes',
                        'price' => 1450,
                    ],
                    1 => [
                        'id' => 'acoustic',
                        'name' => 'Acoustic Comfort Upgrade',
                        'sub' => 'Enhanced Rw-rated build-ups',
                        'price' => 1900,
                    ],
                    2 => [
                        'id' => 'hvac',
                        'name' => 'Heat-Pump Climate System',
                        'sub' => 'Whisper-quiet heating & cooling',
                        'price' => 3200,
                    ],
                    3 => [
                        'id' => 'solar',
                        'name' => 'Solar Array Preparation',
                        'sub' => 'Roof rails, inverter housing & cabling',
                        'price' => 2100,
                    ],
                ],
            ],
            'zones' => [
                0 => [
                    'id' => 'master',
                    'name' => 'Master Suite',
                    'desc' => 'Private sleeping quarter with fitted wardrobes and garden aspect.',
                    'dims' => '3.2 m × 3.0 m',
                    'x' => 55,
                    'y' => 45,
                    'w' => 150,
                    'h' => 140,
                ],
                1 => [
                    'id' => 'living',
                    'name' => 'Central Living & Dining',
                    'desc' => 'Open-plan day space wrapped in perimeter glazing.',
                    'dims' => '4.1 m × 3.2 m',
                    'x' => 215,
                    'y' => 45,
                    'w' => 230,
                    'h' => 270,
                ],
                2 => [
                    'id' => 'kitchen',
                    'name' => 'Kitchen & Prep Zone',
                    'desc' => 'Monolithic island with dual sinks, integrated induction and stone bar.',
                    'dims' => '3.8 m × 1.2 m',
                    'x' => 455,
                    'y' => 45,
                    'w' => 130,
                    'h' => 150,
                ],
                3 => [
                    'id' => 'bed2',
                    'name' => 'King Bed Suite',
                    'desc' => 'Second suite with quiet rear aspect and dressing wall.',
                    'dims' => '3.0 m × 2.8 m',
                    'x' => 55,
                    'y' => 195,
                    'w' => 150,
                    'h' => 120,
                ],
                4 => [
                    'id' => 'ensuite',
                    'name' => 'Ensuite & Utility',
                    'desc' => 'Walk-in shower, WC and concealed plant wall.',
                    'dims' => '2.0 m × 1.6 m',
                    'x' => 455,
                    'y' => 205,
                    'w' => 130,
                    'h' => 110,
                ],
            ],
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
                    'a' => 'You do not need an expensive poured concrete foundation. Most UK installations use galvanized helical ground screws or 6 to 8 concrete leveling pad piers set at key outrigger load points. The house features built-in heavy-duty hydraulic outriggers that allow precision leveling up to ±2mm even on gentle slopes.',
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
            'files' => [
                0 => [
                    'ref' => 'HS-KTC-01/SLN',
                    'suite' => 'THE SLOANE KITCHEN SUITE',
                    'title' => 'The Sloane Suite Brochure & Lookbook',
                    'format' => 'PDF Spec',
                    'size' => '18.4 MB',
                    'rev' => 'Q1 2025',
                ],
                1 => [
                    'ref' => 'HS-KTC-01/SLN',
                    'suite' => 'THE SLOANE KITCHEN SUITE',
                    'title' => 'Complete Technical Specification & Services Schedule',
                    'format' => 'PDF Spec',
                    'size' => '4.2 MB',
                    'rev' => '2025',
                ],
                2 => [
                    'ref' => 'HS-BTH-02/KNS',
                    'suite' => 'THE KENSINGTON FREESTANDING BATH & BASIN SUITE',
                    'title' => 'Kensington Bath Architectural Specification Sheet',
                    'format' => 'PDF Spec',
                    'size' => '3.6 MB',
                    'rev' => '2025',
                ],
                3 => [
                    'ref' => 'HS-LGT-03/AUR',
                    'suite' => 'AURA SCULPTURAL CHANDELIER',
                    'title' => 'Aura Chandelier Technical Data & Photometrics',
                    'format' => 'PDF Spec',
                    'size' => '2.8 MB',
                    'rev' => '2025',
                ],
                4 => [
                    'ref' => 'HS-EXP-38/AST',
                    'suite' => 'THE ASTER VILLA',
                    'title' => 'Aster Villa General Arrangement DWG',
                    'format' => 'CAD Drawing',
                    'size' => '12.1 MB',
                    'rev' => '2025',
                ],
                5 => [
                    'ref' => 'HS-EXP-74/NOV',
                    'suite' => 'THE NOVA GRAND ESTATE',
                    'title' => 'Nova Grand Structural Layout CAD',
                    'format' => 'CAD Drawing',
                    'size' => '15.7 MB',
                    'rev' => 'Q1 2025',
                ],
                6 => [
                    'ref' => 'HS-EXP-18/KOT',
                    'suite' => 'THE KOTO STUDIO POD',
                    'title' => 'Koto Studio Revit Family (LOD350)',
                    'format' => 'BIM/Revit',
                    'size' => '22.3 MB',
                    'rev' => '2025',
                ],
                7 => [
                    'ref' => 'HS-JNR-04/BLG',
                    'suite' => 'BELGRAVIA JOINERY SUITE',
                    'title' => 'Belgravia Joinery BIM Components',
                    'format' => 'BIM/Revit',
                    'size' => '9.8 MB',
                    'rev' => '2025',
                ],
                8 => [
                    'ref' => 'HS-EXP-38/AST',
                    'suite' => 'SITE INSTALLATION',
                    'title' => 'Expandable Home Unfolding & MEP Hookup Manual',
                    'format' => 'Installation Manual',
                    'size' => '6.4 MB',
                    'rev' => '2025',
                ],
                9 => [
                    'ref' => 'HS-HRD-05/BRS',
                    'suite' => 'UNLACQUERED BRASSWARE',
                    'title' => 'Stone, Timber & Brass Care Guide',
                    'format' => 'Care Guide',
                    'size' => '1.9 MB',
                    'rev' => '2025',
                ],
            ],
        ];

        // Categories (union of product categories + video map keys)
        $catNames = array_unique(array_merge(
            array_column($data['products'], 'category'),
            array_keys($data['catVideos'])
        ));
        $sort = 0;
        foreach ($catNames as $name) {
            Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'video_url' => $data['catVideos'][$name] ?? null,
                    'image' => $data['catImages'][$name] ?? null,
                    'meta_title' => $name.' | OriginSpaces Modular UK',
                    'status' => true,
                    'sort_order' => $sort++,
                ]
            );
        }
        $catIds = Category::pluck('id', 'name')->all();

        // Products
        $order = 0;
        foreach ($data['products'] as $p) {
            $price = (int) preg_replace('/[^0-9]/', '', $p['price'] ?? '');
            $product = Product::updateOrCreate(
                ['slug' => $p['id']],
                [
                    'category_id' => $catIds[$p['category']] ?? null,
                    'name' => $p['name'],
                    'model_code' => $p['modelCode'],
                    'tagline' => $p['tagline'] ?? null,
                    'dimensions' => $p['dimensions'] ?? null,
                    'lead_time' => $p['leadTime'] ?? null,
                    'warranty' => $p['warranty'] ?? null,
                    'base_price' => $price > 0 ? $price : null,
                    'hero_image' => $p['heroImage'] ?? null,
                    'meta_title' => ($p['name'] ?? '').' | OriginSpaces',
                    'meta_description' => $p['tagline'] ?? null,
                    'status' => true,
                    'sort_order' => $order++,
                ]
            );

            if ($product->materials()->count() === 0) {
                foreach ($p['materials'] ?? [] as $i => $m) {
                    $product->materials()->create(['name' => $m, 'sort_order' => $i]);
                }
            }
            if ($product->specs()->count() === 0) {
                foreach ($p['specs'] ?? [] as $i => $s) {
                    $product->specs()->create(['point' => $s, 'sort_order' => $i]);
                }
            }
            if ($product->techSpecs()->count() === 0) {
                foreach ([
                    ['label' => 'Insulation Thermal U-Value', 'value' => '0.16 W/m²K (Part L Passivhaus)', 'highlight' => true],
                    ['label' => 'Structural Chassis Steel', 'value' => 'Q235B Galvanized (C4 Marine)', 'highlight' => false],
                    ['label' => 'Acoustic Isolation', 'value' => '42 dB Soundstop', 'highlight' => false],
                    ['label' => 'Assembly Deployment', 'value' => '15 – 30 Minutes', 'highlight' => true],
                ] as $i => $row) {
                    $product->techSpecs()->create([...$row, 'sort_order' => $i]);
                }
            }
            foreach ($data['optGroups'] as $group => $opts) {
                foreach ($opts as $i => $o) {
                    $product->options()->firstOrCreate(
                        ['group' => $group, 'name' => $o['name']],
                        [
                            'subtitle' => $o['sub'] ?? null,
                            'price_delta' => ! empty($o['price']) ? $o['price'] : null,
                            'swatch_color' => $group === 'finish' ? ($o['swatch'] ?? null) : null,
                            'is_default' => $i === 0 && $group !== 'upgrade',
                            'status' => true,
                            'sort_order' => $i,
                        ]
                    );
                }
            }
        }

        // Floor zones
        foreach ($data['zones'] as $i => $z) {
            FloorZone::firstOrCreate(
                ['name' => $z['name']],
                ['dims' => $z['dims'] ?? null, 'desc' => $z['desc'] ?? null, 'status' => true, 'sort_order' => $i]
            );
        }

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

        // Downloads (file-less catalogue entries; staff attach real files later)
        $modelPrefixes = Product::all()->mapWithKeys(fn ($p) => [
            strtoupper(strtok($p->model_code, '/')) => $p->id,
        ])->all();
        $j = 0;
        foreach ($data['files'] as $f) {
            $prefix = strtoupper(strtok($f['ref'], '/'));
            Download::firstOrCreate(
                ['title' => $f['title']],
                [
                    'ref' => $f['ref'],
                    'product_id' => $modelPrefixes[$prefix] ?? null,
                    'file' => null,
                    'format' => $f['format'],
                    'size' => $f['size'] ?? null,
                    'rev' => $f['rev'] ?? null,
                    'status' => true,
                    'sort_order' => $j++,
                ]
            );
        }
    }
}

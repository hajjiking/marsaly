<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Banner;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\Driver;
use App\Models\Merchant;
use App\Models\MerchantHour;
use App\Models\MenuSection;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\PromoCode;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Realistic demo data for El Marsa (Laayoune), Morocco.
 * Prices in MAD, coordinates around El Marsa / Laayoune.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Settings ----------------------------------------------------
        Setting::set('tagline.ar', 'كلشي لحد باب دارك');
        Setting::set('tagline.fr', 'Tout, livré chez vous');
        Setting::set('tagline.en', 'Everything, delivered to your door');
        Setting::set('service_hours', '{"open":"09:00","close":"23:30"}', 'json');
        Setting::set('announcement_banner_enabled', '1', 'bool');

        // ---- Delivery zones ---------------------------------------------
        $elMarsa = DeliveryZone::firstOrCreate(
            ['name->en' => 'El Marsa'],
            [
                'name' => ['ar' => 'المرسى', 'fr' => 'El Marsa', 'en' => 'El Marsa'],
                'city' => 'El Marsa',
                'base_fee' => 10, 'per_km_fee' => 2.5, 'minimum_order' => 25,
                'service_radius_km' => 8, 'center_lat' => 27.7522, 'center_lng' => -13.6603,
            ]
        );
        $laayoune = DeliveryZone::firstOrCreate(
            ['name->en' => 'Laayoune City'],
            [
                'name' => ['ar' => 'العيون', 'fr' => 'Laâyoune', 'en' => 'Laayoune'],
                'city' => 'Laayoune',
                'base_fee' => 15, 'per_km_fee' => 3, 'minimum_order' => 40,
                'service_radius_km' => 15, 'center_lat' => 27.1536, 'center_lng' => -13.2033,
            ]
        );

        // ---- Categories ---------------------------------------------------
        $cats = [];
        foreach ([
            ['restaurants', ['ar' => 'مطاعم', 'fr' => 'Restaurants', 'en' => 'Restaurants'], 'restaurant', 15],
            ['cafes',       ['ar' => 'مقاهي', 'fr' => 'Cafés', 'en' => 'Cafés'], 'coffee', 10],
            ['groceries',   ['ar' => 'بقالة', 'fr' => 'Alimentation', 'en' => 'Groceries'], 'cart', 8],
            ['pharmacies',  ['ar' => 'صيدليات', 'fr' => 'Pharmacies', 'en' => 'Pharmacies'], 'medicine', 5],
            ['bakeries',    ['ar' => 'مخابز', 'fr' => 'Boulangeries', 'en' => 'Bakeries'], 'bakery', 10],
            ['general',     ['ar' => 'سلع عامة', 'fr' => 'Produits divers', 'en' => 'General goods'], 'goods', 12],
        ] as [$slug, $name, $icon, $commission]) {
            $cats[$slug] = Category::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'icon' => ['name' => $icon], 'commission_percent' => $commission, 'is_active' => true]
            );
        }

        // ---- Admin users --------------------------------------------------
        $adminRole   = Role::where('name', 'admin')->first();
        $supportRole = Role::where('name', 'support')->first();
        $financeRole = Role::where('name', 'finance')->first();
        $clientRole  = Role::where('name', 'client')->first();
        $merchRole   = Role::where('name', 'merchant')->first();
        $driverRole  = Role::where('name', 'driver')->first();

        User::firstOrCreate(['email' => 'admin@marsaly.ma'], [
            'name' => 'Super Admin', 'password' => 'password',
            'phone' => '+212600000001', 'role_id' => $adminRole->id, 'locale' => 'fr',
        ]);
        User::firstOrCreate(['email' => 'support@marsaly.ma'], [
            'name' => 'Support Agent', 'password' => 'password',
            'phone' => '+212600000002', 'role_id' => $supportRole->id, 'locale' => 'fr',
        ]);
        User::firstOrCreate(['email' => 'finance@marsaly.ma'], [
            'name' => 'Finance Agent', 'password' => 'password',
            'phone' => '+212600000003', 'role_id' => $financeRole->id, 'locale' => 'fr',
        ]);

        // ---- Demo client --------------------------------------------------
        $client = User::firstOrCreate(['phone' => '+212661112233'], [
            'name' => 'Fatna Zahra', 'role_id' => $clientRole->id, 'locale' => 'ar',
            'phone_verified_at' => now(),
        ]);

        // ---- Merchants ----------------------------------------------------
        $merchants = [
            [
                'user' => ['phone' => '+212661222334', 'name' => 'Ahmed Bouhaddu'],
                'business_name' => 'Restaurant Sidi Ahmed', 'slug_cat' => 'restaurants',
                'desc_fr' => 'Couscous, tajines et grillades au bord de l\'océan.',
                'desc_ar' => 'كسكوس وطواجن ومشويات على شاطئ المحيط.',
                'neighborhood' => 'Corniche', 'lat' => 27.7541, 'lng' => -13.6642,
                'prep' => 25, 'zone' => $elMarsa,
                'menu' => [
                    'Plats' => [
                        ['Couscous Royal', 'كسكوس ملكي', 'Légumes, viande, safran', 70, [
                            ['Supplément viande', [['Extra agneau', 15], ['Extra poisson', 20]]],
                        ]],
                        ['Tajine Poulet Citron', 'طاجج دجاج بالليمون', 'Poulet fermier, citrons confits', 55, []],
                        ['Grillade Mixte', 'مشوي مشكل', 'Brochettes et merguez', 65, [
                            ['Accompagnement', [['Frites', 0], ['Salade', 0], ['Pain', 0]]],
                        ]],
                    ],
                    'Boissons' => [
                        ['Jus d\'avocat', 'عصير أفوكا', 'Avocat, lait, amandes', 20, []],
                        ['Eau minérale 1.5L', 'ماء معدني', '', 6, []],
                    ],
                ],
            ],
            [
                'user' => ['phone' => '+212661222335', 'name' => 'Mariam Salem'],
                'business_name' => 'Café Atlas', 'slug_cat' => 'cafes',
                'desc_fr' => 'Café, thé à la menthe et pâtisseries.',
                'desc_ar' => 'قهوة وشاي بالنعناع وحلويات.',
                'neighborhood' => 'Centre-ville', 'lat' => 27.7505, 'lng' => -13.6551,
                'prep' => 10, 'zone' => $elMarsa,
                'menu' => [
                    'Café & Thé' => [
                        ['Café nous-nous', 'قهوة نص نص', 'Half coffee half milk', 10, []],
                        ['Thé à la menthe', 'شاي بالنعناع', 'Verre', 12, [
                            ['Taille', [['Petit', 0], ['Grand', 5]]],
                        ]],
                    ],
                    'Pâtisseries' => [
                        ['Msemen', 'مسمن', 'Crêpe feuilletée, 2 pièces', 8, []],
                        ['Baghrir', 'بغرير', 'Crêpe mille trous, 5 pièces', 10, []],
                    ],
                ],
            ],
            [
                'user' => ['phone' => '+212661222336', 'name' => 'Société Al Imtiaz'],
                'business_name' => 'Épicerie Al Imtiaz', 'slug_cat' => 'groceries',
                'desc_fr' => 'Épicerie, produits frais et domestiques.',
                'desc_ar' => 'بقالة ومنتجات طازجة ومنزلية.',
                'neighborhood' => 'Hay Salam', 'lat' => 27.7478, 'lng' => -13.6489,
                'prep' => 15, 'zone' => $elMarsa,
                'menu' => [
                    'Frais' => [
                        ['Tomates 1kg', 'طماطم 1كغ', '', 8, []],
                        ['Œufs (douzaine)', 'بيض (دستة)', '', 18, []],
                    ],
                    'Épicerie' => [
                        ['Huile de table 5L', 'زيت المائدة 5ل', '', 85, []],
                        ['Sucre 1kg', 'سكر 1كغ', '', 9, []],
                        ['Riz 1kg', 'أرز 1كغ', '', 16, []],
                    ],
                ],
            ],
            [
                'user' => ['phone' => '+212661222337', 'name' => 'Pharmacie El Chifae'],
                'business_name' => 'Pharmacie El Chifae', 'slug_cat' => 'pharmacies',
                'desc_fr' => 'Médicaments et parapharmacie, livraison rapide.',
                'desc_ar' => 'أدوية ومستحضرات، توصيل سريع.',
                'neighborhood' => 'Marché', 'lat' => 27.7492, 'lng' => -13.6575,
                'prep' => 10, 'zone' => $elMarsa,
                'menu' => [
                    'Médicaments' => [
                        ['Paracétamol 500mg', 'باراسيتامول', 'Boîte de 16', 25, []],
                        ['Vitamine C', 'فيتامين سي', '20 comprimés', 45, []],
                    ],
                    'Hygiène' => [
                        ['Gel hydroalcoolique', 'معقم يدين', '250ml', 30, []],
                    ],
                ],
            ],
            [
                'user' => ['phone' => '+212661222338', 'name' => 'Boulangerie Fourakh'],
                'business_name' => 'Boulangerie Fourakh', 'slug_cat' => 'bakeries',
                'desc_fr' => 'Pain cuit au four, viennoiseries et gâteaux.',
                'desc_ar' => 'خبز التنور ومعجنات وكعك.',
                'neighborhood' => 'La Plage', 'lat' => 27.7560, 'lng' => -13.6610,
                'prep' => 8, 'zone' => $elMarsa,
                'menu' => [
                    'Pain' => [
                        ['Pain blanc', 'خبز أبيض', 'Baguette', 2.5, []],
                        ['Pain complet', 'خبز كامل', '', 3, []],
                    ],
                    'Viennoiseries' => [
                        ['Croissant', 'كورديون', '', 6, [
                            ['Garniture', [['Nature', 0], ['Chocolat', 3], ['Fromage', 4]]],
                        ]],
                    ],
                ],
            ],
            [
                'user' => ['phone' => '+212661222339', 'name' => 'Comptoir du Sud'],
                'business_name' => 'Comptoir du Sud', 'slug_cat' => 'general',
                'desc_fr' => 'Quincaillerie, électroménager et cadeaux.',
                'desc_ar' => 'حديد وبلاستيك وأجهزة منزلية وهدايا.',
                'neighborhood' => 'Laayoune Centre', 'lat' => 27.1553, 'lng' => -13.2071,
                'prep' => 20, 'zone' => $laayoune,
                'menu' => [
                    'Maison' => [
                        ['Bouilloire électrique', 'غلاية كهربائية', '1.7L', 180, []],
                        ['Ampoule LED', 'مصباح ليد', '9W', 25, []],
                    ],
                ],
            ],
        ];

        foreach ($merchants as $m) {
            $u = User::firstOrCreate(['phone' => $m['user']['phone']], [
                'name' => $m['user']['name'], 'role_id' => $merchRole->id,
                'locale' => 'fr', 'phone_verified_at' => now(),
            ]);

            $merchant = Merchant::firstOrCreate(
                ['user_id' => $u->id],
                [
                    'category_id' => $cats[$m['slug_cat']]->id,
                    'delivery_zone_id' => $m['zone']->id,
                    'business_name' => $m['business_name'],
                    'description' => $m['desc_fr'],
                    'phone' => $m['user']['phone'],
                    'address' => $m['neighborhood'] . ', ' . $m['zone']->city,
                    'neighborhood' => $m['neighborhood'],
                    'lat' => $m['lat'], 'lng' => $m['lng'],
                    'status' => 'approved',
                    'approved_at' => now()->subDays(30),
                    'prep_time_minutes' => $m['prep'],
                    'rating_avg' => rand(38, 49) / 10,
                    'orders_count' => rand(10, 300),
                ]
            );

            // Opening hours: open every day 09:00-23:30 except Friday afternoon for pharmacy demo
            for ($day = 0; $day <= 6; $day++) {
                MerchantHour::firstOrCreate(
                    ['merchant_id' => $merchant->id, 'day_of_week' => $day],
                    ['opens_at' => '09:00', 'closes_at' => '23:30', 'is_closed' => false]
                );
            }

            if ($merchant->menuSections()->count() === 0) {
                $sort = 0;
                foreach ($m['menu'] as $sectionName => $products) {
                    $section = MenuSection::create([
                        'merchant_id' => $merchant->id,
                        'name' => ['fr' => $sectionName, 'ar' => $sectionName, 'en' => $sectionName],
                        'sort_order' => $sort++,
                    ]);
                    $psort = 0;
                    foreach ($products as [$nameFr, $nameAr, $desc, $price, $options]) {
                        $product = Product::create([
                            'menu_section_id' => $section->id,
                            'merchant_id' => $merchant->id,
                            'name' => ['fr' => $nameFr, 'ar' => $nameAr, 'en' => $nameFr],
                            'description' => $desc,
                            'price' => $price,
                            'in_stock' => true,
                            'sort_order' => $psort++,
                        ]);
                        foreach ($options as [$optName, $values]) {
                            $opt = ProductOption::create([
                                'product_id' => $product->id,
                                'name' => ['fr' => $optName, 'ar' => $optName, 'en' => $optName],
                                'type' => str_starts_with(strtolower($optName), 'suppl') || str_contains($optName, 'Garniture') ? 'multi' : 'single',
                                'required' => false,
                            ]);
                            foreach ($values as $i => [$vName, $delta]) {
                                ProductOptionValue::create([
                                    'product_option_id' => $opt->id,
                                    'name' => ['fr' => $vName, 'ar' => $vName, 'en' => $vName],
                                    'price_delta' => $delta,
                                    'is_default' => $i === 0,
                                    'sort_order' => $i,
                                ]);
                            }
                        }
                    }
                }
            }
        }

        // ---- Drivers --------------------------------------------------------
        foreach ([
            ['+212661333445', 'Houssein Mrabih', 'motorbike', 'Yamaha NMAX', '1234-A-25', 27.7515, -13.6590],
            ['+212661333446', 'Brahim Derhem', 'car', 'Dacia Logan', '5678-B-27', 27.7489, -13.6520],
            ['+212661333447', 'Said Amidan', 'motorbike', 'Honda Lead', '9012-C-27', 27.1540, -13.2050],
        ] as [$phone, $name, $vehicle, $model, $plate, $lat, $lng]) {
            $du = User::firstOrCreate(['phone' => $phone], [
                'name' => $name, 'role_id' => $driverRole->id, 'locale' => 'ar',
                'phone_verified_at' => now(),
            ]);
            Driver::firstOrCreate(
                ['user_id' => $du->id],
                [
                    'status' => 'approved', 'availability' => 'offline',
                    'vehicle_type' => $vehicle, 'vehicle_model' => $model,
                    'plate_number' => $plate,
                    'lat' => $lat, 'lng' => $lng, 'last_location_at' => now(),
                    'rating_avg' => rand(40, 50) / 10,
                    'deliveries_count' => rand(5, 120),
                    'approved_at' => now()->subDays(20),
                ]
            );
        }

        // ---- Promo codes ----------------------------------------------------
        PromoCode::firstOrCreate(['code' => 'MARSA10'], [
            'type' => 'percent', 'value' => 10, 'min_order_amount' => 50,
            'max_discount' => 30, 'per_user_limit' => 3, 'usage_limit' => 1000,
            'starts_at' => now()->subDay(), 'ends_at' => now()->addMonths(3), 'is_active' => true,
        ]);
        PromoCode::firstOrCreate(['code' => 'BIENVENUE'], [
            'type' => 'free_delivery', 'value' => 0, 'min_order_amount' => 40,
            'per_user_limit' => 1, 'usage_limit' => 5000,
            'starts_at' => now()->subDay(), 'ends_at' => null, 'is_active' => true,
        ]);

        // ---- Banners & announcements -----------------------------------------
        Banner::firstOrCreate(['title->fr' => 'Livraison offerte dès 100 MAD'], [
            'title' => ['ar' => 'توصيل مجاني ابتداء من 100 درهم', 'fr' => 'Livraison offerte dès 100 MAD', 'en' => 'Free delivery over 100 MAD'],
            'image_path' => '/brand/banner-placeholder.svg',
            'link_type' => 'promo', 'link_value' => 'MARSA10',
            'ends_at' => now()->addMonth(), 'is_active' => true,
        ]);

        Announcement::firstOrCreate(['title->fr' => 'Bienvenue sur Marsaly'], [
            'title' => ['ar' => 'مرحبا بكم في مرسالي', 'fr' => 'Bienvenue sur Marsaly', 'en' => 'Welcome to Marsaly'],
            'body' => ['ar' => 'كلشي لحد باب دارك في المرسى والعيون', 'fr' => 'Tout, livré chez vous à El Marsa et Laâyoune', 'en' => 'Everything delivered in El Marsa and Laayoune'],
            'audience' => 'all', 'is_active' => true, 'publish_at' => now()->subDay(),
        ]);

        $this->command?->info('Demo data seeded: 6 merchants w/ menus, 3 drivers, 1 client, 3 admins, promos, banners.');
    }
}

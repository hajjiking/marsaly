<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Marsaly central theme / brand configuration
|--------------------------------------------------------------------------
| Single source of truth for brand name, tagline, colors and business rules.
| Change branding here (or via .env) and it propagates to the API, admin
| panel, notifications, emails and SMS messages.
*/

return [

    'brand' => [
        'name'      => env('MARSALY_BRAND_NAME', 'Marsaly'),
        'name_ar'   => env('MARSALY_BRAND_NAME_AR', 'مرسالي'),
        'app_id'    => env('MARSALY_APP_ID', 'ma.marsaly.app'),
        // Editable per-language tagline (also overridable from the admin panel
        // through the `settings` table key "tagline.{locale}").
        'tagline' => [
            'ar' => env('MARSALY_TAGLINE_AR', 'كلشي لحد باب دارك'),
            'fr' => env('MARSALY_TAGLINE_FR', 'Tout, livré chez vous'),
            'en' => env('MARSALY_TAGLINE_EN', 'Everything, delivered to your door'),
        ],
        'colors' => [
            // Warm orange primary + teal accent (placeholder until real branding).
            'primary'    => env('MARSALY_COLOR_PRIMARY', '#F97316'),
            'secondary'  => env('MARSALY_COLOR_SECONDARY', '#0D9488'),
            'dark'       => env('MARSALY_COLOR_DARK', '#1C1917'),
            'light'      => env('MARSALY_COLOR_LIGHT', '#FFF7ED'),
            'logo_url'   => env('MARSALY_LOGO_URL', '/brand/logo.svg'),
        ],
    ],

    'localization' => [
        'default'   => 'ar',
        'supported' => ['ar', 'fr', 'en'],
    ],

    'money' => [
        'currency'       => 'MAD',
        'currency_symbol'=> 'د.م.', // درهم
        'decimal_digits' => 2,
    ],

    'phone' => [
        'country_code'    => '+212',
        'otp_length'      => 6,
        'otp_ttl_seconds' => (int) env('MARSALY_OTP_TTL', 300),
        'otp_resend_after'=> (int) env('MARSALY_OTP_RESEND_AFTER', 60),
        'max_attempts'    => 5,
    ],

    'otp' => [
        // Pluggable driver: log | twilio | whatsapp_cloud (see App\Support\Sms)
        'driver' => env('MARSALY_SMS_DRIVER', 'log'),
        // Message template; {code} and {brand} are replaced.
        'template' => env('MARSALY_SMS_TEMPLATE', '{brand}: your code is {code}'),
    ],

    'orders' => [
        'statuses' => [
            'pending', 'accepted', 'preparing', 'ready',
            'driver_assigned', 'picked_up', 'on_the_way', 'delivered',
            'cancelled', 'rejected', 'failed',
        ],
        // Allowed transitions (validated server-side in OrderStatusMachine).
        'transitions' => [
            'pending'         => ['accepted', 'rejected', 'cancelled'],
            'accepted'        => ['preparing', 'cancelled'],
            'preparing'       => ['ready', 'cancelled'],
            'ready'           => ['driver_assigned', 'cancelled'],
            'driver_assigned' => ['picked_up', 'cancelled', 'failed'],
            'picked_up'       => ['on_the_way', 'failed'],
            'on_the_way'      => ['delivered', 'failed'],
            'delivered'       => [],
            'cancelled'       => [],
            'rejected'        => [],
            'failed'          => [],
        ],
        'terminal_statuses' => ['delivered', 'cancelled', 'rejected', 'failed'],
        'active_statuses'   => ['pending', 'accepted', 'preparing', 'ready', 'driver_assigned', 'picked_up', 'on_the_way'],
    ],

    'fees' => [
        // Defaults; overridable per delivery zone from the admin panel.
        'base_delivery_fee'   => (float) env('MARSALY_BASE_DELIVERY_FEE', 10.0),
        'per_km_fee'          => (float) env('MARSALY_PER_KM_FEE', 2.5),
        'minimum_order'       => (float) env('MARSALY_MINIMUM_ORDER', 25.0),
        'driver_base_fee'     => (float) env('MARSALY_DRIVER_BASE_FEE', 8.0),
        'driver_per_km_fee'   => (float) env('MARSALY_DRIVER_PER_KM_FEE', 1.5),
        'default_commission'  => (float) env('MARSALY_DEFAULT_COMMISSION', 12.0), // percent
    ],

    'assignment' => [
        'offer_timeout_seconds' => (int) env('MARSALY_OFFER_TIMEOUT', 45),
        'search_radius_km'      => (float) env('MARSALY_ASSIGN_RADIUS_KM', 8.0),
    ],

    'geography' => [
        'city'      => 'El Marsa',
        'province'  => 'Laâyoune',
        'country'   => 'MA',
        'timezone'  => 'Africa/Casablanca',
        'center'    => ['lat' => 27.7522, 'lng' => -13.6603], // El Marsa beach area
        'default_zoom' => 13,
    ],

    'payments' => [
        'default' => 'cod',
        'methods' => ['cod'],            // card gateway (CMI) added later behind PaymentGateway contract
        'gateway' => env('MARSALY_PAYMENT_GATEWAY', 'cod'),
    ],

];

<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /** GET /api/v1/config — brand/theme/tagline bootstrap for the Flutter app. */
    public function config(Request $request)
    {
        $brand = config('marsaly.brand');
        $locale = app()->getLocale();

        $taglines = [];
        foreach (array_keys($brand['tagline']) as $lang) {
            $taglines[$lang] = \App\Models\Setting::get("tagline.{$lang}", $brand['tagline'][$lang]);
        }

        return ApiResponse::success([
            'app_name'      => $brand['name'],
            'app_name_ar'   => $brand['name_ar'],
            'tagline'       => $taglines[$locale] ?? $taglines['fr'],
            'taglines'      => $taglines,
            'colors'        => $brand['colors'],
            'logo_url'      => url($brand['colors']['logo_url']),
            'currency'      => config('marsaly.money.currency'),
            'currency_symbol' => config('marsaly.money.currency_symbol'),
            'default_locale'=> config('marsaly.localization.default'),
            'supported_locales' => config('marsaly.localization.supported'),
            'country_code'  => config('marsaly.phone.country_code'),
            'geography'     => config('marsaly.geography'),
            'payment_methods' => config('marsaly.payments.methods'),
            'broadcast'     => [
                'driver' => config('broadcasting.default'),
                'host'   => config('reverb.host'),
                'port'   => config('reverb.port'),
                'path'   => config('reverb.path', '/app'),
                'key'    => config('reverb.apps.key'),
            ],
        ]);
    }
}

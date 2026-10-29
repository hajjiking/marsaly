<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $supported = config('marsaly.localization.supported');
        $locale = $request->header('Accept-Language')
            ? explode(',', $request->header('Accept-Language'))[0]
            : null;
        if ($locale) {
            $locale = strtolower(trim(explode(';', $locale)[0]));
            $primary = explode('-', $locale)[0];
            if (in_array($primary, $supported, true)) {
                App::setLocale($primary);
            }
        }
        return $next($request);
    }
}

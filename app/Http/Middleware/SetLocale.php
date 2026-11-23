<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public const LOCALES = ['ar', 'en', 'fr'];

    public function handle(Request $request, Closure $next)
    {
        $locale = $request->session()->get('locale', config('app.locale'));
        app()->setLocale(in_array($locale, self::LOCALES, true) ? $locale : 'en');
        return $next($request);
    }
}

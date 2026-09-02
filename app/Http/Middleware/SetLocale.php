<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve the request locale.
 *
 * Order: ?lang= query parameter, then session, then the app default.
 *
 * The query parameter is not a convenience — it is the only reason the French
 * half of this platform can be indexed at all. Locale used to live purely in
 * the session, which meant every page had exactly ONE url and a crawler (which
 * carries no session) always saw English. Google, Perplexity, ChatGPT and every
 * other engine were being served an English-only site while 8,214 translation
 * keys sat unreachable behind a session flag, in a country where French is the
 * majority language.
 *
 * With ?lang=fr each page has a distinct, crawlable address, which is what the
 * hreflang alternates in layouts/public.blade.php point at. Google explicitly
 * supports query-parameter locale variants for hreflang.
 *
 * A visitor's explicit choice is still persisted to the session, so switching
 * language once keeps working across a browsing session exactly as before.
 */
class SetLocale
{
    /** Locales this platform actually ships, kept 1:1 in lang/en and lang/fr. */
    public const SUPPORTED = ['en', 'fr'];

    public function handle(Request $request, Closure $next): Response
    {
        $requested = $request->query('lang');

        if (is_string($requested) && in_array($requested, self::SUPPORTED, true)) {
            App::setLocale($requested);

            // Persist, so the choice survives navigation without the parameter.
            // Guarded because this middleware also runs on stateless API
            // requests, where there is no session to write to.
            if ($request->hasSession()) {
                Session::put('locale', $requested);
            }

            return $next($request);
        }

        if (Session::has('locale') && in_array(Session::get('locale'), self::SUPPORTED, true)) {
            App::setLocale(Session::get('locale'));
        }

        return $next($request);
    }
}

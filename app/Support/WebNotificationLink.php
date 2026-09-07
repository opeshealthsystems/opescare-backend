<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Only ever hand the web a link the web can actually open.
 *
 * A notification's `url` is written once, by whichever service raised it, and
 * then consumed by TWO surfaces with different route spaces: the web bell and
 * notification centre render it as an `<a href>`, while `Notify::toUser()`
 * also puts it in the provider push payload, where the patient/clinician APP
 * routes it.
 *
 * Most emitters write the APP's path, because that is where the work gets
 * done — `/labs/<order>`, `/clinician/results/lab-<order>`. Those are correct
 * for the app and are registered nowhere on the web, so every one of them was
 * a bell row that 404'd when clicked. `/clinician/results/lab-<id>` is the
 * clearest case: the acknowledge flow it points at exists only in the app, and
 * the doctor's web portal has no results screen at all.
 *
 * So the web asks the router instead of trusting the string. A path that
 * matches a registered GET route is rendered as a link; anything else renders
 * as a plain row — the notification still says what happened, it just does not
 * promise a page that is not there. The push payload is untouched: the app
 * keeps the path it needs.
 *
 * This is deliberately a check against the ROUTER rather than an allow-list of
 * prefixes: a list would go stale the first time somebody adds a screen, and
 * the failure would again be silent.
 */
final class WebNotificationLink
{
    /**
     * The url a web page may link to, or null.
     *
     * Refuses anything absolute (`http…`), protocol-relative (`//host`) or
     * unroutable, so a payload written by a service can never send a signed-in
     * user off-site.
     */
    public static function resolve(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '' || ! str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return null;
        }

        $path = strtok($url, '?#');

        if ($path === false || $path === '') {
            return null;
        }

        try {
            Route::getRoutes()->match(Request::create($path, 'GET'));
        } catch (NotFoundHttpException|HttpException|UrlGenerationException) {
            return null;
        }

        return $url;
    }
}

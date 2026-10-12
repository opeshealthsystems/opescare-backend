<?php

declare(strict_types=1);

namespace App\Modules\Legal\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Strips scripts, event handlers, javascript: URLs and anything else that is
 * not plain document markup from a legal document version before it is stored.
 *
 * The HTML is authored by platform admins and then rendered unescaped on the
 * PUBLIC legal pages (`public/legal/show`) for every visitor, so one pasted
 * `<script>` or `onload=` would be stored XSS served to the whole country.
 * Sanitising at write time (not render time) means the stored hash is of the
 * clean document and every renderer — web, PDF, API — gets the same bytes.
 */
final class LegalHtmlSanitizer
{
    private ?HTMLPurifier $purifier = null;

    public function clean(string $html): string
    {
        return $this->purifier()->purify($html);
    }

    private function purifier(): HTMLPurifier
    {
        if ($this->purifier) {
            return $this->purifier;
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.Allowed', implode(',', [
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'br', 'hr',
            'strong', 'b', 'em', 'i', 'u', 's', 'small', 'sub', 'sup', 'span', 'blockquote', 'pre', 'code',
            'ul', 'ol', 'li', 'dl', 'dt', 'dd',
            'table', 'thead', 'tbody', 'tfoot', 'tr', 'th[scope|colspan|rowspan]', 'td[colspan|rowspan]',
            'a[href|title|rel]', 'div',
        ]));
        $config->set('HTML.Nofollow', true);
        $config->set('HTML.TargetBlank', false);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('Attr.AllowedFrameTargets', []);
        $config->set('CSS.AllowedProperties', []); // no inline styles — the page styles the document
        // HTMLPurifier caches its parsed definitions on disk; the directory is
        // inside the git-ignored framework cache and is created on first use.
        $cacheDir = storage_path('framework/cache/htmlpurifier');
        \Illuminate\Support\Facades\File::ensureDirectoryExists($cacheDir);
        $config->set('Cache.SerializerPath', $cacheDir);

        return $this->purifier = new HTMLPurifier($config);
    }
}

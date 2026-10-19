<?php

namespace App\Services\Blog;

use Illuminate\Support\Str;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;

/**
 * Markdown -> safe HTML for blog posts.
 *
 * Raw HTML in a post is ESCAPED, not stripped and not passed through: a post
 * is content, and content must never be able to inject a <script> (the CSP
 * would block it anyway, but the page must not depend on that). Unsafe link
 * schemes (javascript:, data:, vbscript:) are dropped by CommonMark itself.
 *
 * External links get rel="noopener noreferrer"; internal ones are left alone.
 * h2/h3 headings get stable ids and become the table of contents; a stray h1
 * in the body is demoted to h2, because the article title is the page's only
 * h1. Images are lazy-loaded.
 */
class MarkdownRenderer
{
    /** @return array{html:string,toc:list<array{id:string,text:string,level:int}>,words:int} */
    public function render(string $markdown): array
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'opescare.com';

        $html = Str::markdown($markdown, [
            'html_input'         => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level'  => 20,
            'external_link'      => [
                'internal_hosts'     => [$host, 'opescare.com', 'www.opescare.com'],
                'open_in_new_window' => false,
                'html_class'         => '',
                'nofollow'           => '',
                'noopener'           => 'external',
                'noreferrer'         => 'external',
            ],
        ], [new ExternalLinkExtension()]);

        // One h1 per page: the title. Demote any in the body.
        $html = preg_replace(['/<h1(\s[^>]*)?>/', '/<\/h1>/'], ['<h2$1>', '</h2>'], $html);

        $toc = [];
        $used = [];
        $html = preg_replace_callback('/<h([23])>(.*?)<\/h\1>/s', function (array $m) use (&$toc, &$used) {
            $text = trim(html_entity_decode(strip_tags($m[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $base = Str::slug($text) ?: 'section';
            $id = $base;
            $n = 2;
            while (isset($used[$id])) {
                $id = $base . '-' . $n++;
            }
            $used[$id] = true;
            $toc[] = ['id' => $id, 'text' => $text, 'level' => (int) $m[1]];

            return '<h' . $m[1] . ' id="' . e($id) . '">' . $m[2] . '</h' . $m[1] . '>';
        }, $html);

        $html = preg_replace('/<img (?![^>]*loading=)/', '<img loading="lazy" decoding="async" ', $html);
        $html = preg_replace('/<table>/', '<div class="bl-table"><table>', $html);
        $html = str_replace('</table>', '</table></div>', $html);

        $words = str_word_count(strip_tags($html), 0, 'ÀÂÄÇÉÈÊËÎÏÔÖÙÛÜŸàâäçéèêëîïôöùûüÿœŒæÆ\'-');

        return ['html' => $html, 'toc' => $toc, 'words' => $words];
    }
}

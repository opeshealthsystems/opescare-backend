<?php

namespace App\Support\Blog;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Turns a web diagram into an SVG file dompdf can draw.
 *
 * dompdf's SVG engine (php-svg-lib) understands presentation attributes and
 * single-class CSS selectors only: no descendant selectors, no markers, no
 * custom properties. The web diagrams lean on all three, so this takes the
 * rendered SVG, writes the colours the stylesheets would have applied straight
 * onto each element, removes the decorative motion paths, and draws each
 * arrowhead as a small polygon at the end of its path.
 *
 * The files are written to storage (inside dompdf's chroot) and referenced by
 * path from the PDF view.
 */
final class DiagramPdf
{
    /** [element classes (all required), ancestor class or null, attributes]. Later rows win. */
    private const RULES = [
        [['dg-lane-box'], null, ['fill' => '#0F4C81']],
        [['dg-lifeline'], null, ['stroke' => '#C9D7E6', 'stroke-width' => '1.5', 'stroke-dasharray' => '4 5']],
        [['dg-line'], null, ['fill' => 'none', 'stroke' => '#0F4C81', 'stroke-width' => '1.75']],
        [['dg-line'], 'dg-res', ['stroke' => '#64748B', 'stroke-dasharray' => '6 5']],
        [['dg-line'], 'dg-fail', ['stroke' => '#B42318', 'stroke-dasharray' => '6 5']],
        [['dg-line'], 'dg-overview', ['stroke' => '#7C9CBC', 'stroke-width' => '1.5']],
        [['dg-label'], null, ['font-size' => '12.5', 'font-weight' => 'bold', 'fill' => '#1E293B']],
        [['dg-label'], 'dg-fail', ['fill' => '#B42318']],
        [['dg-lane-title'], null, ['font-size' => '13.5', 'font-weight' => 'bold', 'fill' => '#FFFFFF']],
        [['dg-lane-sub'], null, ['font-size' => '11', 'fill' => '#D6E4F2']],
        [['dg-num'], null, ['font-size' => '11.5', 'font-weight' => 'bold', 'fill' => '#0F4C81']],
        [['dg-num'], 'dg-fail', ['fill' => '#B42318']],
        [['dg-num-bg'], null, ['fill' => '#FFFFFF', 'stroke' => '#D6E4F2', 'stroke-width' => '1.5']],
        [['dg-num-bg'], 'dg-fail', ['stroke' => '#F5C2BC']],
        [['dg-group-label'], null, ['font-size' => '10.5', 'font-weight' => 'bold', 'fill' => '#64748B']],
        [['dg-box-title'], 'dg-box', ['font-size' => '13', 'font-weight' => 'bold', 'fill' => '#0F172A']],
        [['dg-box-sub'], 'dg-box', ['font-size' => '10.5', 'fill' => '#64748B']],
        [['dg-box-title'], 'dg-tone-edge', ['fill' => '#FFFFFF']],
        [['dg-box-sub'], 'dg-tone-edge', ['fill' => '#D6E4F2']],
        [['dg-box-title'], 'dg-tone-gate', ['fill' => '#0B5E3C']],
        [['dg-box-title'], 'dg-tone-data', ['fill' => '#FFFFFF']],
        [['dg-box-sub'], 'dg-tone-data', ['fill' => '#94A3B8']],
    ];

    /** Rect colours inside an overview group / box, by the parent's class. */
    private const RECTS = [
        'dg-group'       => ['fill' => '#FFFFFF', 'stroke' => '#D6E1EC', 'stroke-width' => '1.25', 'stroke-dasharray' => '5 5'],
        'dg-group-host'  => ['fill' => '#F2F7FC', 'stroke' => '#D6E4F2', 'stroke-dasharray' => 'none'],
        'dg-box'         => ['fill' => '#FFFFFF', 'stroke' => '#CBD5E1', 'stroke-width' => '1.25'],
        'dg-tone-client' => ['stroke' => '#94A3B8'],
        'dg-tone-edge'   => ['fill' => '#0F4C81', 'stroke' => '#0F4C81'],
        'dg-tone-gate'   => ['fill' => '#E8F5EE', 'stroke' => '#7CC4A0'],
        'dg-tone-svc'    => ['stroke' => '#5EA3DC'],
        'dg-tone-data'   => ['fill' => '#0F172A', 'stroke' => '#0F172A'],
    ];

    /**
     * Render a whitelisted diagram for print and return the SVG file path, or
     * null for an unknown name.
     */
    public static function file(string $name, string $locale): ?string
    {
        if (! BlogDiagrams::exists($name)) {
            return null;
        }

        $html = BlogDiagrams::render($name, true);
        $svg = self::printable($html);
        if ($svg === null) {
            return null;
        }

        $dir = storage_path('app/blog-pdf/svg');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $path = $dir . DIRECTORY_SEPARATOR . $name . '-' . $locale . '-' . substr(md5($svg), 0, 12) . '.svg';
        if (! is_file($path)) {
            file_put_contents($path, $svg);
        }

        return $path;
    }

    /** The first <svg> in $html, rewritten for php-svg-lib. */
    public static function printable(string $html): ?string
    {
        if (! preg_match('/<svg\b.*?<\/svg>/s', $html, $m)) {
            return null;
        }

        $doc = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $ok = $doc->loadXML('<?xml version="1.0" encoding="UTF-8"?>' . $m[0]);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if (! $ok) {
            return null;
        }

        $svg = $doc->documentElement;
        $svg->setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        [, , $w, $h] = array_map('floatval', preg_split('/[\s,]+/', trim($svg->getAttribute('viewBox'))) + [0, 0, 960, 400]);
        $svg->setAttribute('width', (string) $w);
        $svg->setAttribute('height', (string) $h);
        $svg->setAttribute('font-family', 'DejaVu Sans');
        foreach (['class', 'role', 'aria-labelledby', 'preserveAspectRatio'] as $a) {
            $svg->removeAttribute($a);
        }
        // Context classes that live on the <figure>, outside the <svg>.
        $context = preg_match('/class="dg dg-overview/', $html) ? ['dg-overview'] : [];

        $xp = new DOMXPath($doc);
        $xp->registerNamespace('s', 'http://www.w3.org/2000/svg');

        foreach (iterator_to_array($xp->query('//*[local-name()="defs" or local-name()="title" or local-name()="desc"]')) as $n) {
            $n->parentNode->removeChild($n);
        }
        foreach (iterator_to_array($xp->query('//*[contains(concat(" ", normalize-space(@class), " "), " dg-pulse ")]')) as $n) {
            $n->parentNode->removeChild($n);
        }

        foreach (iterator_to_array($xp->query('//*')) as $el) {
            /** @var DOMElement $el */
            $classes = self::classes($el);
            $ancestors = $context;
            for ($p = $el->parentNode; $p instanceof DOMElement; $p = $p->parentNode) {
                $ancestors = array_merge($ancestors, self::classes($p));
            }

            foreach (self::RULES as [$need, $anc, $attrs]) {
                if (array_diff($need, $classes) === [] && ($anc === null || in_array($anc, $ancestors, true))) {
                    foreach ($attrs as $k => $v) {
                        $el->setAttribute($k, $v);
                    }
                }
            }

            if ($el->localName === 'rect' && $el->parentNode instanceof DOMElement) {
                foreach (self::classes($el->parentNode) as $pc) {
                    foreach (self::RECTS[$pc] ?? [] as $k => $v) {
                        $el->setAttribute($k, $v);
                    }
                }
            }

            if ($el->localName === 'text') {
                $el->removeAttribute('stroke');
            }

            if ($el->hasAttribute('marker-end')) {
                $el->removeAttribute('marker-end');
                if ($el->localName === 'path' && ($head = self::arrowhead($el->getAttribute('d')))) {
                    $poly = $doc->createElementNS('http://www.w3.org/2000/svg', 'polygon');
                    $poly->setAttribute('points', $head);
                    $poly->setAttribute('fill', $el->getAttribute('stroke') ?: '#0F4C81');
                    $el->parentNode->insertBefore($poly, $el->nextSibling);
                }
            }
            $el->removeAttribute('pathLength');
        }

        foreach (iterator_to_array($xp->query('//@class | //@data-step | //@tabindex')) as $attr) {
            $attr->ownerElement->removeAttributeNode($attr);
        }

        return $doc->saveXML($svg);
    }

    /** @return list<string> */
    private static function classes(DOMElement $el): array
    {
        $c = trim($el->getAttribute('class'));

        return $c === '' ? [] : preg_split('/\s+/', $c);
    }

    /** Polygon points for an arrowhead at the end of path $d (M/L/H/V/C, absolute or relative). */
    public static function arrowhead(string $d): ?string
    {
        preg_match_all('/([MLHVCmlhvc])([^MLHVCmlhvc]*)/', $d, $segs, PREG_SET_ORDER);
        $x = $y = 0.0;
        $px = $py = 0.0;
        foreach ($segs as [, $cmd, $args]) {
            $n = array_map('floatval', preg_split('/[\s,]+/', trim($args), -1, PREG_SPLIT_NO_EMPTY));
            $rel = ctype_lower($cmd);
            switch (strtoupper($cmd)) {
                case 'M':
                case 'L':
                    for ($i = 0; $i + 1 < count($n); $i += 2) {
                        [$px, $py] = [$x, $y];
                        $x = $rel ? $x + $n[$i] : $n[$i];
                        $y = $rel ? $y + $n[$i + 1] : $n[$i + 1];
                    }
                    break;
                case 'H':
                    foreach ($n as $v) {
                        [$px, $py] = [$x, $y];
                        $x = $rel ? $x + $v : $v;
                    }
                    break;
                case 'V':
                    foreach ($n as $v) {
                        [$px, $py] = [$x, $y];
                        $y = $rel ? $y + $v : $v;
                    }
                    break;
                case 'C':
                    for ($i = 0; $i + 5 < count($n); $i += 6) {
                        $px = $rel ? $x + $n[$i + 2] : $n[$i + 2];
                        $py = $rel ? $y + $n[$i + 3] : $n[$i + 3];
                        $nx = $rel ? $x + $n[$i + 4] : $n[$i + 4];
                        $ny = $rel ? $y + $n[$i + 5] : $n[$i + 5];
                        [$x, $y] = [$nx, $ny];
                    }
                    break;
            }
        }

        $dx = $x - $px;
        $dy = $y - $py;
        $len = sqrt($dx * $dx + $dy * $dy);
        if ($len < 0.001) {
            return null;
        }
        [$ux, $uy] = [$dx / $len, $dy / $len];
        $bx = $x - 8 * $ux;
        $by = $y - 8 * $uy;
        $f = fn ($v) => rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');

        return $f($x) . ',' . $f($y) . ' '
            . $f($bx - 4 * $uy) . ',' . $f($by + 4 * $ux) . ' '
            . $f($bx + 4 * $uy) . ',' . $f($by - 4 * $ux);
    }
}

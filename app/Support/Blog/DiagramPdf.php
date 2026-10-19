<?php

namespace App\Support\Blog;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Turns a web diagram into an SVG file dompdf can draw.
 *
 * The flow diagrams (App\Support\Diagrams\Specs, drawn by
 * resources/views/docs/diagrams/flow.blade.php) already carry every colour,
 * dash and font as presentation attributes, from the same palette and icon
 * set as the web. dompdf's SVG engine (php-svg-lib) has no markers and no
 * CSS, so this only removes what it cannot draw (title, desc, defs, motion
 * paths, classes and data attributes) and draws each arrowhead as a small
 * polygon at the end of its path, in the path's own colour.
 *
 * The files are written to storage (inside dompdf's chroot) and referenced by
 * path from the PDF view.
 */
final class DiagramPdf
{
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

        foreach (iterator_to_array($xp->query('//@class | //@data-steps | //@data-node | //@data-w | //@tabindex')) as $attr) {
            $attr->ownerElement->removeAttributeNode($attr);
        }

        return $doc->saveXML($svg);
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

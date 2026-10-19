<?php

namespace App\Support\Blog;

use App\Support\Diagrams\Specs;

/**
 * Diagrams a blog post may embed with a line of its own: {{diagram:NAME}}.
 *
 * Only the names below exist. MarkdownRenderer turns a whitelisted line into
 * an inert marker comment and drops any other name, so a post can never
 * smuggle markup or pick an arbitrary view: the marker is resolved here, to a
 * fixed Blade partial, when the article (or its PDF) is rendered.
 *
 * Every diagram is a spec in App\Support\Diagrams\Specs, the same source the
 * developer docs, the PDF and /docs/flows.json use; each card cites the route,
 * class or setting it is drawn from, and DiagramSpecTest proves they resolve.
 */
final class BlogDiagrams
{
    /** Marker the renderer writes in place of a whitelisted embed line. */
    public const MARKER = '/<!--opescare-diagram:([a-z0-9-]+)-->/';

    /** Embed name => diagram id in App\Support\Diagrams\Specs. */
    public const NAMES = [
        'consent'               => 'consent',
        'fhir'                  => 'fhir',
        'mpi'                   => 'mpi',
        'qr'                    => 'qr',
        'architecture'          => 'overview',
        'record-is-the-product' => 'record-is-the-product',
        'one-app-five-roles'    => 'one-app-five-roles',
        'family-act-as'         => 'family-act-as',
        'offline-outbox'        => 'offline-outbox',
        'pharmacy-dispense'     => 'pharmacy-dispense',
        'lab-release'           => 'lab-release',
        'blood-request-queue'   => 'blood-request-queue',
        'facility-go-live'      => 'facility-go-live',
    ];

    public static function exists(string $name): bool
    {
        return isset(self::NAMES[$name]);
    }

    /**
     * Split rendered post HTML into prose and diagram segments.
     *
     * @return list<array{html:string}|array{diagram:string}>
     */
    public static function segments(string $html): array
    {
        $out = [];
        $parts = preg_split(self::MARKER, $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $i => $part) {
            if ($i % 2 === 1) {
                if (self::exists($part)) {
                    $out[] = ['diagram' => $part];
                }
            } elseif (trim($part) !== '') {
                $out[] = ['html' => $part];
            }
        }

        return $out;
    }

    /** Translated title of a diagram, for figure captions. */
    public static function title(string $name): string
    {
        return self::exists($name) ? __(Specs::get(self::NAMES[$name])['title']) : '';
    }

    public static function has(string $html): bool
    {
        return (bool) preg_match(self::MARKER, $html);
    }

    /** Render one whitelisted diagram to HTML. Unknown names render nothing. */
    public static function render(string $name, bool $static = false): string
    {
        if (! self::exists($name)) {
            return '';
        }

        return view('docs.diagrams.flow', ['diagram' => self::NAMES[$name], 'static' => $static])->render();
    }
}

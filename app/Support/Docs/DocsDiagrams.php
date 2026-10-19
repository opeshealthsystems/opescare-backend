<?php

namespace App\Support\Docs;

use App\Support\Diagrams\Specs;

/**
 * The /docs flow pages. Every diagram, step list and code reference on them
 * comes from App\Support\Diagrams\Specs; this class only names the pages and
 * their nav icons.
 */
final class DocsDiagrams
{
    /** Flow pages served at /docs/flows/{flow}. */
    public const FLOWS = Specs::DOCS_FLOWS;

    /** Lucide icon per flow, for nav and cards. */
    public const ICONS = [
        'consent'  => 'shield-check',
        'fhir'     => 'database-zap',
        'mpi'      => 'git-merge',
        'webhooks' => 'webhook',
        'qr'       => 'qr-code',
    ];

    /**
     * The code a flow is drawn from: the union of its cards' evidence, in
     * card order. Proven to resolve by tests/Feature/Diagrams/DiagramSpecTest.
     *
     * @return list<string>
     */
    public static function code(string $flow): array
    {
        $refs = [];
        foreach (Specs::get($flow)['nodes'] as $node) {
            foreach ($node[5] as $ref) {
                $refs[$ref] = true;
            }
        }

        return array_keys($refs);
    }
}

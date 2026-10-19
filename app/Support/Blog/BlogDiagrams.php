<?php

namespace App\Support\Blog;

/**
 * Diagrams a blog post may embed with a line of its own: {{diagram:NAME}}.
 *
 * Only the names below exist. MarkdownRenderer turns a whitelisted line into
 * an inert marker comment and drops any other name, so a post can never
 * smuggle markup or pick an arbitrary view: the marker is resolved here, to a
 * fixed Blade partial, when the article (or its PDF) is rendered.
 *
 * Every box in the four "board" diagrams names something that exists in the
 * backend or the mobile app; the source is noted beside each one. Labels are
 * translation keys under blog_diagrams.{name}.*.
 */
final class BlogDiagrams
{
    /** Marker the renderer writes in place of a whitelisted embed line. */
    public const MARKER = '/<!--opescare-diagram:([a-z0-9-]+)-->/';

    /** name => [view, data]. */
    public const NAMES = [
        // Reused from the developer docs (resources/views/docs/diagrams).
        'consent'      => ['docs.diagrams.sequence', ['flow' => 'consent']],
        'fhir'         => ['docs.diagrams.sequence', ['flow' => 'fhir']],
        'mpi'          => ['docs.diagrams.sequence', ['flow' => 'mpi']],
        'qr'           => ['docs.diagrams.sequence', ['flow' => 'qr']],
        'architecture' => ['docs.diagrams.overview', []],
        // Built for the blog (resources/views/blog/diagrams/board.blade.php).
        'record-is-the-product' => ['blog.diagrams.board', ['name' => 'record-is-the-product']],
        'one-app-five-roles'    => ['blog.diagrams.board', ['name' => 'one-app-five-roles']],
        'family-act-as'         => ['blog.diagrams.board', ['name' => 'family-act-as']],
        'offline-outbox'        => ['blog.diagrams.board', ['name' => 'offline-outbox']],
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
        if (! self::exists($name)) {
            return '';
        }
        [$view, $data] = self::NAMES[$name];

        return match ($view) {
            'docs.diagrams.sequence' => __('docs_ui.dg.' . $data['flow'] . '.title'),
            'docs.diagrams.overview' => __('docs_ui.dg.overview.title'),
            default                  => __('blog_diagrams.' . $name . '.title'),
        };
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
        [$view, $data] = self::NAMES[$name];

        return view($view, $data + ['static' => $static])->render();
    }

    /**
     * Layout of the blog-only diagrams.
     *
     * boxes: [key, x, y, w, h, tone, step]   tone: client|edge|gate|svc|data|fail
     * edges: [path, step, kind]              kind: req|res|fail
     * groups: [key, x, y, w, h]
     *
     * @return array{w:int,h:int,boxes:list<array>,edges:list<array>,groups:list<array>,steps:int}
     */
    public static function board(string $name): array
    {
        return match ($name) {
            // The record at the centre, four layers around it.
            'record-is-the-product' => [
                'w' => 960, 'h' => 520, 'steps' => 5,
                'groups' => [
                    ['market', 250, 16, 460, 124],
                    ['family', 16, 160, 224, 204],
                    ['clinic', 720, 160, 224, 204],
                    ['governance', 250, 380, 460, 124],
                ],
                'boxes' => [
                    // Patient model, Health ID (PatientIdentityService), access log.
                    ['record', 370, 205, 220, 110, 'data', 1],
                    // routes: public.network.medicine-finder / blood-finder / care-map.
                    ['medicine', 266, 50, 136, 74, 'svc', 2],
                    ['blood', 412, 50, 136, 74, 'svc', 2],
                    ['care', 558, 50, 136, 74, 'svc', 2],
                    // ConsentGrant ledger, access log, break-glass (emergency_access).
                    ['ledger', 266, 414, 136, 74, 'gate', 3],
                    ['accesslog', 412, 414, 136, 74, 'gate', 3],
                    ['breakglass', 558, 414, 136, 74, 'gate', 3],
                    // FamilyLink act-as (X-OpesCare-Dependent), invitations.
                    ['guardian', 32, 192, 192, 48, 'client', 4],
                    ['actas', 32, 248, 192, 48, 'client', 4],
                    ['invites', 32, 304, 192, 48, 'client', 4],
                    // config/staff_capabilities.php roles.
                    ['doctor', 736, 192, 192, 48, 'client', 5],
                    ['desk', 736, 248, 192, 48, 'client', 5],
                    ['pharmlab', 736, 304, 192, 48, 'client', 5],
                ],
                'edges' => [
                    ['M480 140 V203', 2, 'req'],
                    ['M480 380 V317', 3, 'req'],
                    ['M240 262 H368', 4, 'req'],
                    ['M720 262 H592', 5, 'req'],
                ],
            ],

            // The same record behind five role experiences.
            'one-app-five-roles' => [
                'w' => 960, 'h' => 400, 'steps' => 7,
                'groups' => [],
                'boxes' => [
                    ['patient', 24, 24, 168, 80, 'client', 1],
                    ['doctor', 210, 24, 168, 80, 'client', 2],
                    ['desk', 396, 24, 168, 80, 'client', 3],
                    ['pharmacist', 582, 24, 168, 80, 'client', 4],
                    ['lab', 768, 24, 168, 80, 'client', 5],
                    // capability: middleware + App\Support\ClinicalRelationshipGate.
                    ['gate', 24, 170, 912, 60, 'gate', 6],
                    ['record', 24, 296, 912, 80, 'data', 7],
                ],
                'edges' => [
                    ['M108 104 V168', 1, 'req'],
                    ['M294 104 V168', 2, 'req'],
                    ['M480 104 V168', 3, 'req'],
                    ['M666 104 V168', 4, 'req'],
                    ['M852 104 V168', 5, 'req'],
                    ['M480 230 V294', 6, 'req'],
                ],
            ],

            // Guardian -> act-as context -> family-link check -> dependent's record;
            // adults join by invitation only (accept / decline).
            'family-act-as' => [
                'w' => 960, 'h' => 430, 'steps' => 8,
                'groups' => [
                    ['actas', 12, 12, 936, 196],
                    ['invite', 12, 226, 936, 192],
                ],
                'boxes' => [
                    ['guardian', 28, 48, 190, 76, 'client', 1],
                    // X-OpesCare-Dependent header (mobile lib/api/types.ts).
                    ['header', 266, 48, 190, 76, 'edge', 2],
                    ['check', 504, 48, 190, 76, 'gate', 3],
                    ['record', 742, 48, 190, 76, 'data', 4],
                    ['banner', 266, 144, 666, 44, 'svc', 4],
                    // POST /mobile/family/invitations, …/{id}/accept|decline.
                    ['invite', 28, 262, 190, 70, 'client', 5],
                    ['pending', 266, 262, 190, 70, 'svc', 6],
                    ['accepted', 504, 262, 190, 70, 'gate', 7],
                    ['active', 742, 262, 190, 70, 'data', 7],
                    ['declined', 504, 350, 190, 56, 'fail', 8],
                ],
                'edges' => [
                    ['M218 86 H264', 2, 'req'],
                    ['M456 86 H502', 3, 'req'],
                    ['M694 86 H740', 4, 'req'],
                    ['M218 297 H264', 6, 'req'],
                    ['M456 297 H502', 7, 'req'],
                    ['M694 297 H740', 7, 'req'],
                    ['M361 332 V378 H502', 8, 'fail'],
                ],
            ],

            // Offline: policy-scoped encrypted cache; failed writes -> outbox ->
            // encrypted server queue -> reconciliation job + audit.
            'offline-outbox' => [
                'w' => 960, 'h' => 420, 'steps' => 9,
                'groups' => [
                    ['device', 12, 12, 500, 396],
                    ['server', 528, 12, 420, 396],
                ],
                'boxes' => [
                    // POST /mobile/offline/policies (SyncService::createPolicy).
                    ['policy', 548, 48, 380, 70, 'gate', 1],
                    // mobile lib/offline/cache.ts — one encrypted record per scope.
                    ['cache', 32, 48, 220, 70, 'data', 2],
                    ['wipe', 32, 312, 220, 70, 'fail', 9],
                    ['write', 32, 180, 220, 70, 'client', 3],
                    // mobile lib/offline/outbox.ts — auth requests never captured.
                    ['outbox', 272, 180, 220, 70, 'svc', 4],
                    // POST /mobile/offline/policies/{policy}/queue.
                    ['queue', 548, 180, 380, 70, 'data', 6],
                    ['job', 548, 312, 180, 70, 'gate', 7],
                    ['audit', 748, 312, 180, 70, 'gate', 8],
                    ['settle', 272, 312, 220, 70, 'edge', 5],
                ],
                'edges' => [
                    ['M546 83 H254', 2, 'req'],
                    ['M252 215 H270', 4, 'req'],
                    ['M382 250 V310', 5, 'req'],
                    ['M492 347 H520 V215 H546', 6, 'req'],
                    ['M638 250 V310', 7, 'req'],
                    ['M838 250 V310', 8, 'req'],
                ],
            ],

            default => ['w' => 960, 'h' => 100, 'steps' => 0, 'groups' => [], 'boxes' => [], 'edges' => []],
        };
    }
}

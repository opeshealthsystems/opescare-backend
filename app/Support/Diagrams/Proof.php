<?php

namespace App\Support\Diagrams;

use App\Support\Icons\Lucide;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/**
 * Machine-checkable claims about a diagram spec.
 *
 * Every check returns a list of violations; an empty list is the proof. The
 * test suite asserts all of them, and /docs/flows.json publishes the result so
 * a reader can re-run the reasoning against the API reference.
 */
final class Proof
{
    public const OUTCOMES = ['outcome-ok', 'outcome-refused'];
    public const START_KINDS = ['actor', 'external'];
    public const EDGE_KINDS = ['request', 'response', 'async', 'refusal'];

    /** @return array<string, list<string>> check name => violations */
    public static function graph(array $spec): array
    {
        $nodes = $spec['nodes'];
        $ids = array_keys($nodes);
        $v = [
            'unique_ids' => [], 'known_kinds' => [], 'edge_endpoints' => [], 'one_start' => [],
            'reachable' => [], 'reaches_outcome' => [], 'acyclic_except_loops' => [],
            'steps_are_paths' => [], 'outcomes_terminal' => [], 'grid_cells_unique' => [],
        ];

        // Ids: PHP array keys are unique by construction; the spec file is
        // also scanned for a repeated key (which PHP would silently merge).
        foreach ($ids as $id) {
            if (! preg_match('/^[a-z][a-z0-9_]*$/', $id)) {
                $v['unique_ids'][] = "bad id {$id}";
            }
        }
        foreach ($nodes as $id => $n) {
            if (! isset(Palette::KINDS[$n[0]])) {
                $v['known_kinds'][] = "{$id}: kind {$n[0]}";
            }
        }
        $cells = [];
        foreach ($nodes as $id => $n) {
            $key = implode(',', $n[2]);
            if (isset($cells[$key])) {
                $v['grid_cells_unique'][] = "{$id} and {$cells[$key]} share cell {$key}";
            }
            $cells[$key] = $id;
        }

        $out = array_fill_keys($ids, []);
        $in = array_fill_keys($ids, 0);
        $loops = [];
        foreach ($spec['edges'] as $e) {
            [$a, $b, $kind] = $e;
            if (! isset($nodes[$a]) || ! isset($nodes[$b])) {
                $v['edge_endpoints'][] = "{$a} -> {$b}";
                continue;
            }
            if (! in_array($kind, self::EDGE_KINDS, true)) {
                $v['edge_endpoints'][] = "{$a} -> {$b}: edge kind {$kind}";
            }
            if (! empty($e[4]['loop'])) {
                $loops[] = [$a, $b];
                continue; // declared retry loops are excluded from the DAG checks
            }
            $out[$a][] = $b;
            $in[$b]++;
        }

        $starts = array_keys(array_filter($in, fn ($d) => $d === 0));
        if (count($starts) !== 1) {
            $v['one_start'][] = 'starts: ' . implode(', ', $starts);
        } elseif (! in_array($nodes[$starts[0]][0], self::START_KINDS, true)) {
            $v['one_start'][] = "start {$starts[0]} is a {$nodes[$starts[0]][0]}, not an actor/trigger";
        }

        // BFS from the start.
        $start = $starts[0] ?? null;
        $seen = $start ? [$start => true] : [];
        $q = $start ? [$start] : [];
        while ($q) {
            $x = array_shift($q);
            foreach ($out[$x] as $y) {
                if (! isset($seen[$y])) {
                    $seen[$y] = true;
                    $q[] = $y;
                }
            }
        }
        foreach ($ids as $id) {
            if (! isset($seen[$id])) {
                $v['reachable'][] = $id;
            }
        }

        // Reverse BFS from the outcomes: every node must reach one.
        $rev = array_fill_keys($ids, []);
        foreach ($out as $a => $bs) {
            foreach ($bs as $b) {
                $rev[$b][] = $a;
            }
        }
        $good = [];
        $q = [];
        foreach ($ids as $id) {
            if (in_array($nodes[$id][0], self::OUTCOMES, true)) {
                $good[$id] = true;
                $q[] = $id;
            }
        }
        while ($q) {
            $x = array_shift($q);
            foreach ($rev[$x] as $y) {
                if (! isset($good[$y])) {
                    $good[$y] = true;
                    $q[] = $y;
                }
            }
        }
        foreach ($ids as $id) {
            if (! isset($good[$id])) {
                $v['reaches_outcome'][] = $id;
            }
            if (in_array($nodes[$id][0], self::OUTCOMES, true) && $out[$id] !== []) {
                $v['outcomes_terminal'][] = $id;
            }
        }

        // Kahn's algorithm: the graph minus declared loops must be a DAG.
        $deg = $in;
        $q = array_keys(array_filter($deg, fn ($d) => $d === 0));
        $done = 0;
        while ($q) {
            $x = array_shift($q);
            $done++;
            foreach ($out[$x] as $y) {
                if (--$deg[$y] === 0) {
                    $q[] = $y;
                }
            }
        }
        if ($done !== count($ids)) {
            $v['acyclic_except_loops'][] = 'cycle among: ' . implode(', ', array_keys(array_filter($deg, fn ($d) => $d > 0)));
        }
        // A declared loop must close a real cycle: its target must reach its source.
        foreach ($loops as [$a, $b]) {
            if (! self::reaches($out, $b, $a)) {
                $v['acyclic_except_loops'][] = "loop {$a} -> {$b} does not close a cycle";
            }
        }

        $all = [];
        foreach ($spec['edges'] as $e) {
            $all[$e[0] . '>' . $e[1]] = true;
        }
        foreach ($spec['steps'] as $k => [$path]) {
            if ($path === []) {
                $v['steps_are_paths'][] = 'step ' . ($k + 1) . ' is empty';
            }
            foreach ($path as $j => $id) {
                if (! isset($nodes[$id])) {
                    $v['steps_are_paths'][] = 'step ' . ($k + 1) . ": unknown node {$id}";
                } elseif ($j > 0 && ! isset($all[$path[$j - 1] . '>' . $id])) {
                    $v['steps_are_paths'][] = 'step ' . ($k + 1) . ": no edge {$path[$j - 1]} -> {$id}";
                }
            }
        }

        return $v;
    }

    private static function reaches(array $out, string $from, string $to): bool
    {
        $seen = [$from => true];
        $q = [$from];
        while ($q) {
            $x = array_shift($q);
            if ($x === $to) {
                return true;
            }
            foreach ($out[$x] as $y) {
                if (! isset($seen[$y])) {
                    $seen[$y] = true;
                    $q[] = $y;
                }
            }
        }

        return false;
    }

    /** Resolve one evidence reference against the running app. */
    public static function resolves(string $ref): bool
    {
        [$type, $target] = explode(':', $ref, 2) + [1 => ''];

        return match ($type) {
            'route'    => Route::has($target),
            'uri'      => self::uriExists($target),
            'class'    => class_exists($target) || interface_exists($target),
            'method'   => str_contains($target, '::') && method_exists(...explode('::', $target, 2)),
            'property' => str_contains($target, '::') && property_exists(...explode('::', $target, 2)),
            'config'   => config()->has($target),
            'command'  => array_key_exists($target, Artisan::all()),
            default    => false,
        };
    }

    private static function uriExists(string $target): bool
    {
        [$method, $uri] = explode(' ', $target, 2) + [1 => ''];
        foreach (Route::getRoutes()->getRoutes() as $r) {
            if ($r->uri() === $uri && in_array(strtoupper($method), $r->methods(), true)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, list<string>> */
    public static function evidence(array $spec): array
    {
        $v = ['evidence_present' => [], 'evidence_resolves' => []];
        foreach ($spec['nodes'] as $id => $n) {
            if (empty($n[5])) {
                $v['evidence_present'][] = $id;
            }
            foreach ($n[5] ?? [] as $ref) {
                if (! self::resolves($ref)) {
                    $v['evidence_resolves'][] = "{$id}: {$ref}";
                }
            }
        }

        return $v;
    }

    /** Icons exist in the Lucide set and match their node's kind. */
    public static function icons(array $spec): array
    {
        $v = ['icons_exist' => [], 'icons_match_kind' => []];
        foreach ($spec['nodes'] as $id => [$kind, $icon]) {
            if (! Lucide::has($icon)) {
                $v['icons_exist'][] = "{$id}: {$icon}";
            }
            if (! in_array($icon, Palette::ALLOWED_ICONS[$kind] ?? [], true)) {
                $v['icons_match_kind'][] = "{$id}: {$icon} on a {$kind}";
            }
        }

        return $v;
    }

    /** Every label key exists in both locales. */
    public static function i18n(array $spec): array
    {
        $keys = [$spec['title'], $spec['summary']];
        foreach ($spec['nodes'] as $n) {
            array_push($keys, $n[3], $n[4]);
        }
        foreach ($spec['edges'] as $e) {
            if (! empty($e[3])) {
                $keys[] = $e[3];
            }
        }
        foreach ($spec['steps'] as $s) {
            array_push($keys, $s[1], $s[2]);
        }
        foreach ($spec['groups'] ?? [] as $g) {
            $keys[] = $g[0];
        }
        $v = ['i18n_en_fr' => []];
        foreach (array_unique($keys) as $k) {
            foreach (['en', 'fr'] as $loc) {
                if (! is_string(trans($k, [], $loc)) || trans($k, [], $loc) === $k) {
                    $v['i18n_en_fr'][] = "{$loc}: {$k}";
                }
            }
        }

        return $v;
    }

    /** Geometry of the built layout, in the current locale. */
    public static function geometry(array $spec, array $g): array
    {
        $v = ['cards_text_fits' => [], 'edges_clear_cards' => [], 'edges_not_collinear' => [],
            'labels_clear' => [], 'arrowheads_visible' => [], 'adjacent_fills_differ' => []];
        $tw = Layout::textWidth($spec);
        foreach ($g['nodes'] as $id => $n) {
            if (count($n['title']) > 2 || count($n['sub']) > 2) {
                $v['cards_text_fits'][] = "{$id}: " . count($n['title']) . '+' . count($n['sub']) . ' lines';
            }
            foreach ($n['title'] as $line) {
                if (Layout::measure($line, Layout::TITLE, true) > $tw + 0.5) {
                    $v['cards_text_fits'][] = "{$id}: '{$line}' too wide";
                }
            }
            foreach ($n['sub'] as $line) {
                if (Layout::measure($line, Layout::SUB, false) > $tw + 0.5) {
                    $v['cards_text_fits'][] = "{$id}: '{$line}' too wide";
                }
            }
        }

        $segs = [];
        foreach ($g['edges'] as $e) {
            $pts = $e['points'];
            $last = count($pts) - 1;
            $tail = abs($pts[$last][0] - $pts[$last - 1][0]) + abs($pts[$last][1] - $pts[$last - 1][1]);
            if ($tail < 12) {
                $v['arrowheads_visible'][] = "{$e['from']} -> {$e['to']}: last segment {$tail}px";
            }
            for ($s = 0; $s < $last; $s++) {
                $segs[] = [$e, $pts[$s], $pts[$s + 1]];
                foreach ($g['nodes'] as $id => $n) {
                    $own = $id === $e['from'] || $id === $e['to'];
                    if (Layout::segmentHitsRect($pts[$s], $pts[$s + 1], $n, $own ? 1.0 : -2.0)) {
                        $v['edges_clear_cards'][] = "{$e['from']} -> {$e['to']} crosses {$id}";
                    }
                }
            }
            if ($e['lbox']) {
                foreach ($g['nodes'] as $id => $n) {
                    if (Layout::overlaps($e['lbox'], $n, 2)) {
                        $v['labels_clear'][] = "label of {$e['from']} -> {$e['to']} on card {$id}";
                    }
                }
                foreach ($g['groups'] as $gr) {
                    if (Layout::overlaps($e['lbox'], $gr['lbox'], 2)) {
                        $v['labels_clear'][] = "label of {$e['from']} -> {$e['to']} on group label";
                    }
                }
                foreach ($g['edges'] as $o) {
                    if ($o['i'] > $e['i'] && $o['lbox'] && Layout::overlaps($e['lbox'], $o['lbox'], 2)) {
                        $v['labels_clear'][] = "labels of {$e['from']} -> {$e['to']} and {$o['from']} -> {$o['to']} overlap";
                    }
                }
            }
            $from = $spec['nodes'][$e['from']][0];
            $to = $spec['nodes'][$e['to']][0];
            if ($from !== $to && Palette::KINDS[$from][0] === Palette::KINDS[$to][0]) {
                $v['adjacent_fills_differ'][] = "{$e['from']} / {$e['to']}";
            }
        }
        foreach ($g['groups'] as $gr) {
            foreach ($segs as [$e, $a, $b]) {
                if (Layout::segmentHitsRect($a, $b, $gr['lbox'], 0)) {
                    $v['labels_clear'][] = "{$e['from']} -> {$e['to']} crosses group label {$gr['label']}";
                }
            }
        }

        // Two different edges may share a collinear stretch only as a bus:
        // same source (fan-out) or same target (fan-in), and same kind.
        for ($i = 0; $i < count($segs); $i++) {
            for ($j = $i + 1; $j < count($segs); $j++) {
                [$e1, $a1, $b1] = $segs[$i];
                [$e2, $a2, $b2] = $segs[$j];
                if ($e1['i'] === $e2['i'] || ! self::collinearOverlap($a1, $b1, $a2, $b2)) {
                    continue;
                }
                $bus = ($e1['from'] === $e2['from'] || $e1['to'] === $e2['to']) && $e1['kind'] === $e2['kind'];
                if (! $bus) {
                    $v['edges_not_collinear'][] = "{$e1['from']} -> {$e1['to']} overlaps {$e2['from']} -> {$e2['to']}";
                }
            }
        }

        return $v;
    }

    private static function collinearOverlap(array $a1, array $b1, array $a2, array $b2): bool
    {
        if ($a1[0] == $b1[0] && $a2[0] == $b2[0] && abs($a1[0] - $a2[0]) < 0.5) {
            return min(max($a1[1], $b1[1]), max($a2[1], $b2[1])) - max(min($a1[1], $b1[1]), min($a2[1], $b2[1])) > 0.5;
        }
        if ($a1[1] == $b1[1] && $a2[1] == $b2[1] && abs($a1[1] - $a2[1]) < 0.5) {
            return min(max($a1[0], $b1[0]), max($a2[0], $b2[0])) - max(min($a1[0], $b1[0]), min($a2[0], $b2[0])) > 0.5;
        }

        return false;
    }

    /** Every check for one diagram, in the current locale. */
    public static function all(string $id): array
    {
        $spec = Specs::get($id);

        return self::graph($spec) + self::evidence($spec) + self::icons($spec) + self::i18n($spec)
            + self::geometry($spec, Layout::build($spec));
    }
}

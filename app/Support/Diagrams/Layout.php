<?php

namespace App\Support\Diagrams;

/**
 * Geometry for one diagram spec: card rectangles on a fixed grid, orthogonal
 * connectors between ports, collision-avoiding edge labels, group frames and
 * an in-figure legend. Pure arithmetic; the Blade view and the PDF only draw
 * what this returns, and the geometry tests check it (no connector crosses a
 * card, no label sits on a card or another label, every card's text fits).
 */
final class Layout
{
    public const CW = 184;      // card width
    public const CH = 84;       // card height
    public const CG = 88;       // gap between columns (room for an edge label)
    public const RG = 72;       // gap between rows
    public const BADGE = 30;    // icon badge
    public const TITLE = 12.5;  // title font size
    public const SUB = 10.5;    // sublabel font size
    public const ELABEL = 10.5; // edge label font size
    public const MARGIN = 16;

    /** Per-diagram card width override (narrow cards for 5-column diagrams). */
    public static function cardWidth(array $spec): int
    {
        return (int) ($spec['cw'] ?? self::CW);
    }

    public static function colGap(array $spec): int
    {
        return (int) ($spec['cg'] ?? self::CG);
    }

    /** Width of the text column inside a card. */
    public static function textWidth(array $spec): float
    {
        return self::cardWidth($spec) - 12 - self::BADGE - 10 - 12;
    }

    /**
     * Estimated rendered width of $text in px. Per-glyph classes calibrated on
     * Inter / DejaVu Sans; deliberately generous so "fits" is conservative.
     */
    public static function measure(string $text, float $size, bool $bold = false): float
    {
        $w = 0.0;
        foreach (mb_str_split($text) as $ch) {
            $w += match (true) {
                str_contains("iljtfr.,:;'!|() ", $ch)       => 0.32,
                str_contains('mwMW@', $ch)                    => 0.86,
                ctype_upper($ch) || str_contains('ABCDEFGHKNOQRUVXYZ', $ch) => 0.68,
                ctype_digit($ch)                              => 0.58,
                default                                       => 0.56,
            };
        }

        return $w * $size * ($bold ? 1.07 : 1.0);
    }

    /** Greedy word wrap. @return list<string> */
    public static function wrap(string $text, float $max, float $size, bool $bold): array
    {
        $lines = [];
        $line = '';
        $words = [];
        foreach (preg_split('/\s+/u', trim($text)) as $w) {
            // A token wider than the card breaks after a hyphen or before a
            // bracket; the pieces rejoin without a space when they share a line.
            if (self::measure($w, $size, $bold) > $max) {
                foreach (preg_split('/(?<=-)|(?=\()/u', $w, -1, PREG_SPLIT_NO_EMPTY) as $k => $part) {
                    $words[] = [$part, $k === 0 ? ' ' : ''];
                }
            } else {
                $words[] = [$w, ' '];
            }
        }
        foreach ($words as [$word, $glue]) {
            $try = $line === '' ? $word : $line . $glue . $word;
            if ($line !== '' && self::measure($try, $size, $bold) > $max) {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $try;
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }

    /** @return array{x:float,y:float,w:float,h:float} */
    private static function cell(array $spec, int $c, int $r): array
    {
        $cw = self::cardWidth($spec);

        return ['x' => $c * ($cw + self::colGap($spec)), 'y' => $r * (self::CH + self::RG), 'w' => $cw, 'h' => self::CH];
    }

    /** Port point on a card side: t b l r, optional - / + offset. */
    private static function port(array $n, string $spec): array
    {
        $side = $spec[0];
        $o = match ($spec[1] ?? '') { '-' => -1, '+' => 1, default => 0 };
        $cx = $n['x'] + $n['w'] / 2;
        $cy = $n['y'] + $n['h'] / 2;

        return match ($side) {
            't' => [$cx + $o * $n['w'] * 0.25, $n['y']],
            'b' => [$cx + $o * $n['w'] * 0.25, $n['y'] + $n['h']],
            'l' => [$n['x'], $cy + $o * $n['h'] * 0.25],
            'r' => [$n['x'] + $n['w'], $cy + $o * $n['h'] * 0.25],
        };
    }

    /** Default ports from the relative position of two cards. */
    private static function autoPorts(array $a, array $b): array
    {
        if ($b['y'] >= $a['y'] + $a['h']) {
            return ['b', 't'];
        }
        if ($b['y'] + $b['h'] <= $a['y']) {
            return ['t', 'b'];
        }

        return $b['x'] >= $a['x'] + $a['w'] ? ['r', 'l'] : ['l', 'r'];
    }

    /** Orthogonal route between two ports. @return list<array{float,float}> */
    public static function route(array $a, array $b, string $out, string $in, int $cg = self::CG): array
    {
        $P = self::port($a, $out);
        $Q = self::port($b, $in);
        $stub = fn (array $p, string $s) => match ($s[0]) {
            't' => [$p[0], $p[1] - self::RG / 2],
            'b' => [$p[0], $p[1] + self::RG / 2],
            'l' => [$p[0] - $cg / 2, $p[1]],
            'r' => [$p[0] + $cg / 2, $p[1]],
        };
        $P2 = $stub($P, $out);
        $Q2 = $stub($Q, $in);
        // Same orientation: cross over at the stub level (a bus in the gap).
        // Different orientation: run out to the target's level, then turn (an L).
        $vOut = in_array($out[0], ['t', 'b'], true);
        $vIn = in_array($in[0], ['t', 'b'], true);
        if ($vOut === $vIn) {
            $corner = $vOut ? [$Q2[0], $P2[1]] : [$P2[0], $Q2[1]];
        } else {
            $corner = $vOut ? [$P2[0], $Q2[1]] : [$Q2[0], $P2[1]];
        }

        return self::simplify([$P, $P2, $corner, $Q2, $Q]);
    }

    /** Drop repeated and collinear points. */
    private static function simplify(array $pts): array
    {
        $out = [];
        foreach ($pts as $p) {
            $p = [round($p[0], 2), round($p[1], 2)];
            if ($out && end($out) == $p) {
                continue;
            }
            $out[] = $p;
        }
        $changed = true;
        while ($changed) {
            $changed = false;
            for ($i = 1; $i < count($out) - 1; $i++) {
                [$a, $b, $c] = [$out[$i - 1], $out[$i], $out[$i + 1]];
                if (($a[0] == $b[0] && $b[0] == $c[0]) || ($a[1] == $b[1] && $b[1] == $c[1])) {
                    array_splice($out, $i, 1);
                    $changed = true;
                    break;
                }
            }
        }

        return $out;
    }

    public static function pathD(array $pts): string
    {
        $f = fn ($v) => rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
        $d = 'M' . $f($pts[0][0]) . ' ' . $f($pts[0][1]);
        for ($i = 1; $i < count($pts); $i++) {
            $d .= $pts[$i][0] == $pts[$i - 1][0] ? ' V' . $f($pts[$i][1]) : ' H' . $f($pts[$i][0]);
        }

        return $d;
    }

    public static function overlaps(array $r1, array $r2, float $pad = 0): bool
    {
        return $r1['x'] < $r2['x'] + $r2['w'] + $pad && $r2['x'] < $r1['x'] + $r1['w'] + $pad
            && $r1['y'] < $r2['y'] + $r2['h'] + $pad && $r2['y'] < $r1['y'] + $r1['h'] + $pad;
    }

    /** Does the axis-aligned segment a-b pass through the rectangle's interior? */
    public static function segmentHitsRect(array $a, array $b, array $r, float $inset = 1.0): bool
    {
        $x1 = min($a[0], $b[0]);
        $x2 = max($a[0], $b[0]);
        $y1 = min($a[1], $b[1]);
        $y2 = max($a[1], $b[1]);

        return $x2 > $r['x'] + $inset && $x1 < $r['x'] + $r['w'] - $inset
            && $y2 > $r['y'] + $inset && $y1 < $r['y'] + $r['h'] - $inset;
    }

    /**
     * Everything the renderer needs, in the current locale.
     */
    public static function build(array $spec): array
    {
        $tw = self::textWidth($spec);
        $nodes = [];
        foreach ($spec['nodes'] as $id => [$kind, $icon, [$c, $r], $label, $sub]) {
            $box = self::cell($spec, $c, $r);
            $title = self::wrap(__($label), $tw, self::TITLE, true);
            $subl = self::wrap(__($sub), $tw, self::SUB, false);
            $nodes[$id] = $box + [
                'id' => $id, 'kind' => $kind, 'icon' => $icon, 'title' => $title, 'sub' => $subl,
                'steps' => [],
            ];
        }

        // Connectors first: group labels and edge labels are placed around them.
        $routes = [];
        foreach ($spec['edges'] as $i => $e) {
            [$from, $to] = $e;
            $opt = $e[4] ?? [];
            [$out, $in] = self::autoPorts($nodes[$from], $nodes[$to]);
            $routes[$i] = self::route($nodes[$from], $nodes[$to], $opt['out'] ?? $out, $opt['in'] ?? $in, self::colGap($spec));
        }

        // Groups: a frame around a block of cells; the label goes in the
        // first corner band (top-left, top-right, bottom-left, bottom-right)
        // that no connector crosses.
        $groups = [];
        foreach ($spec['groups'] ?? [] as [$key, $c0, $r0, $c1, $r1]) {
            $a = self::cell($spec, $c0, $r0);
            $b = self::cell($spec, $c1, $r1);
            $rect = ['x' => $a['x'] - 14, 'y' => $a['y'] - 28, 'w' => $b['x'] + $b['w'] - $a['x'] + 28, 'h' => $b['y'] + $b['h'] - $a['y'] + 56];
            $text = mb_strtoupper(__($key));
            $lw = self::measure($text, 10, true) * 1.12 + 4;
            $spots = [];
            foreach ([$rect['y'] + 5, $rect['y'] + $rect['h'] - 21] as $sy) {
                for ($sx = $rect['x'] + 10; $sx <= $rect['x'] + $rect['w'] - 10 - $lw; $sx += 16) {
                    $spots[] = [$sx, $sy];
                }
            }
            $pick = $spots[0];
            foreach ($spots as $sp) {
                $box = ['x' => $sp[0], 'y' => $sp[1], 'w' => $lw, 'h' => 16];
                $hit = false;
                foreach ($routes as $pts) {
                    for ($k = 0; $k < count($pts) - 1; $k++) {
                        if (self::segmentHitsRect($pts[$k], $pts[$k + 1], $box, -3)) {
                            $hit = true;
                            break 2;
                        }
                    }
                }
                if (! $hit) {
                    $pick = $sp;
                    break;
                }
            }
            $groups[] = $rect + ['label' => $text, 'lx' => $pick[0] + 2, 'ly' => $pick[1] + 12,
                'lbox' => ['x' => $pick[0], 'y' => $pick[1], 'w' => $lw, 'h' => 16]];
        }

        $obstacles = array_values(array_map(fn ($n) => ['x' => $n['x'], 'y' => $n['y'], 'w' => $n['w'], 'h' => $n['h']], $nodes));
        foreach ($groups as $g) {
            $obstacles[] = $g['lbox'];
        }

        $edges = [];
        $placed = [];
        foreach ($spec['edges'] as $i => $e) {
            [$from, $to, $kind] = $e;
            $label = $e[3] ?? null;
            $opt = $e[4] ?? [];
            $pts = $routes[$i];

            $lbox = null;
            $text = null;
            if ($label) {
                $text = __($label);
                $w = self::measure($text, self::ELABEL, true) + 14;
                $h = 18;
                $cands = [];
                for ($s = 0; $s < count($pts) - 1; $s++) {
                    [$a, $b] = [$pts[$s], $pts[$s + 1]];
                    $len = abs($a[0] - $b[0]) + abs($a[1] - $b[1]);
                    foreach ([0.5, 0.3, 0.7] as $t) {
                        $mx = $a[0] + ($b[0] - $a[0]) * $t;
                        $my = $a[1] + ($b[1] - $a[1]) * $t;
                        $cands[] = [$len * (1 - abs($t - 0.5)), ['x' => $mx - $w / 2, 'y' => $my - $h / 2, 'w' => $w, 'h' => $h]];
                    }
                }
                usort($cands, fn ($p, $q) => $q[0] <=> $p[0]);
                foreach ($cands as [, $box]) {
                    $clear = true;
                    foreach (array_merge($obstacles, $placed) as $o) {
                        if (self::overlaps($box, $o, 3)) {
                            $clear = false;
                            break;
                        }
                    }
                    if ($clear) {
                        $lbox = $box;
                        break;
                    }
                }
                $lbox ??= $cands[0][1];
                $placed[] = $lbox;
            }

            $edges[$i] = [
                'i' => $i, 'from' => $from, 'to' => $to, 'kind' => $kind, 'loop' => (bool) ($opt['loop'] ?? false),
                'points' => $pts, 'd' => self::pathD($pts), 'label' => $text, 'lbox' => $lbox, 'steps' => [],
            ];
        }

        // Steps: mark the nodes and edges each step walks.
        $steps = [];
        foreach ($spec['steps'] as $k => [$path, $title, $text]) {
            $n = $k + 1;
            foreach ($path as $j => $id) {
                $nodes[$id]['steps'][] = $n;
                if ($j > 0) {
                    foreach ($edges as $ei => $ed) {
                        if ($ed['from'] === $path[$j - 1] && $ed['to'] === $id) {
                            $edges[$ei]['steps'][] = $n;
                            break;
                        }
                    }
                }
            }
            $kind = 'request';
            foreach ($edges as $ed) {
                if (in_array($n, $ed['steps'], true) && $ed['kind'] === 'refusal') {
                    $kind = 'refusal';
                }
            }
            $steps[] = ['n' => $n, 'title' => __($title), 'text' => __($text), 'kind' => $kind, 'path' => $path];
        }

        // Bounding box of everything drawn.
        $xs = [];
        $ys = [];
        foreach ($nodes as $n) {
            array_push($xs, $n['x'], $n['x'] + $n['w']);
            array_push($ys, $n['y'], $n['y'] + $n['h']);
        }
        foreach ($groups as $g) {
            array_push($xs, $g['x'], $g['x'] + $g['w']);
            array_push($ys, $g['y'], $g['y'] + $g['h']);
        }
        foreach ($edges as $ed) {
            foreach ($ed['points'] as [$x, $y]) {
                $xs[] = $x;
                $ys[] = $y;
            }
            if ($ed['lbox']) {
                array_push($xs, $ed['lbox']['x'], $ed['lbox']['x'] + $ed['lbox']['w']);
                array_push($ys, $ed['lbox']['y'], $ed['lbox']['y'] + $ed['lbox']['h']);
            }
        }
        $minX = min($xs) - self::MARGIN;
        $minY = min($ys) - self::MARGIN;
        $width = max($xs) - $minX + self::MARGIN;
        $bottom = max($ys) + 20;

        // Legend: one row (wrapping) of the kinds and edge kinds this diagram uses.
        $usedKinds = array_values(array_unique(array_column($nodes, 'kind')));
        $usedKinds = array_values(array_filter(Palette::kinds(), fn ($k) => in_array($k, $usedKinds, true)));
        $usedEdges = array_values(array_filter(array_keys(Palette::EDGES), fn ($k) => in_array($k, array_column($edges, 'kind'), true)));
        $items = [];
        foreach ($usedKinds as $k) {
            $items[] = ['type' => 'node', 'kind' => $k, 'text' => __('flows.kind.' . $k)];
        }
        foreach ($usedEdges as $k) {
            $items[] = ['type' => 'edge', 'kind' => $k, 'text' => __('flows.edge_kind.' . $k)];
        }
        $lx = $minX + self::MARGIN;
        $ly = $bottom + 4;
        $maxX = $minX + $width - self::MARGIN;
        foreach ($items as &$it) {
            $iw = 40 + self::measure($it['text'], 11, false) + 18;
            if ($lx + $iw > $maxX && $lx > $minX + self::MARGIN) {
                $lx = $minX + self::MARGIN;
                $ly += 26;
            }
            $it['x'] = $lx;
            $it['y'] = $ly;
            $lx += $iw;
        }
        unset($it);
        $legendTitleY = $bottom - 2;
        $height = $ly + 22 - $minY + self::MARGIN / 2;

        return [
            'id' => $spec['id'], 'title' => __($spec['title']), 'summary' => __($spec['summary']),
            'vb' => [$minX, $minY, $width, $height],
            'nodes' => $nodes, 'edges' => $edges, 'groups' => $groups, 'steps' => $steps,
            'legend' => $items, 'legend_y' => $legendTitleY, 'legend_x' => $minX + self::MARGIN,
            'legend_top' => $bottom - 14,
        ];
    }
}

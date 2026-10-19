<?php

namespace App\Support\Diagrams;

/**
 * The one colour system for every flow diagram (web, PDF, JSON).
 *
 * Built on brand #0F4C81. Purple is forbidden by the brand, so the async
 * colour is teal/cyan. Colour is never the only signal: every kind also has
 * its own Lucide icon and border style (see BORDER), and every edge kind its
 * own dash pattern.
 *
 * The guarantees are not claimed, they are computed: contrast() implements
 * WCAG 2.x relative luminance, deltaE2000() implements CIEDE2000, and
 * tests/Feature/Diagrams/DiagramSpecTest.php asserts
 *   - text and sublabel on every fill   >= 4.5:1
 *   - every pair of kind fills          ΔE2000 >= 15
 *   - every edge colour on the canvas   >= 3:1
 */
final class Palette
{
    /** Canvas behind the cards (web and PDF). */
    public const CANVAS = '#FFFFFF';

    /** kind => [fill, stroke, text, sub-text]. */
    public const KINDS = [
        'actor'           => ['#FFFFFF', '#475569', '#0F172A', '#475569'],
        'external'        => ['#94A3B8', '#334155', '#0F172A', '#1E293B'],
        'service'         => ['#0F4C81', '#0F4C81', '#FFFFFF', '#DCE8F4'],
        'store'           => ['#1E293B', '#0F172A', '#FFFFFF', '#CBD5E1'],
        'gate'            => ['#FDE68A', '#B45309', '#451A03', '#78350F'],
        'decision'        => ['#A5F3FC', '#0E7490', '#083344', '#164E63'],
        'outcome-ok'      => ['#BBF7D0', '#15803D', '#052E16', '#14532D'],
        'outcome-refused' => ['#FECACA', '#B91C1C', '#450A0A', '#7F1D1D'],
    ];

    /**
     * Border style per kind, so a kind is readable without colour:
     * solid | dashed | double | dotted | pill (terminator, ISO 5807).
     */
    public const BORDER = [
        'actor'           => 'solid',
        'external'        => 'dashed',
        'service'         => 'solid',
        'store'           => 'solid',
        'gate'            => 'double',
        'decision'        => 'dotted',
        'outcome-ok'      => 'pill',
        'outcome-refused' => 'pill',
    ];

    /** Default icon per kind (a node may name a more specific one). */
    public const KIND_ICONS = [
        'actor'           => 'user-round',
        'external'        => 'globe',
        'service'         => 'server',
        'store'           => 'database',
        'gate'            => 'shield-check',
        'decision'        => 'git-branch',
        'outcome-ok'      => 'circle-check',
        'outcome-refused' => 'circle-x',
    ];

    /** Icons a node of each kind may use: the icon must say what the kind is. */
    public const ALLOWED_ICONS = [
        'actor'           => ['user-round', 'user', 'users', 'user-check', 'user-plus', 'smartphone', 'stethoscope', 'monitor', 'calendar-clock', 'scan-line', 'cloud-off', 'qr-code', 'zap', 'pill', 'flask-conical'],
        'external'        => ['globe', 'server', 'building-2', 'plug', 'webhook'],
        'service'         => ['server', 'braces', 'search', 'pill', 'droplet', 'map-pin', 'bell', 'send', 'key-round', 'refresh-cw', 'inbox', 'list-ordered', 'git-merge', 'flask-conical', 'heart-pulse', 'file-check', 'credit-card', 'webhook', 'id-card', 'link', 'mail', 'clock', 'timer', 'undo-2', 'monitor', 'user-plus', 'phone'],
        'store'           => ['database', 'hard-drive', 'scroll-text', 'heart-pulse', 'list-ordered', 'inbox'],
        'gate'            => ['shield-check', 'shield', 'shield-alert', 'key-round', 'lock', 'lock-keyhole', 'fingerprint', 'user-check'],
        'decision'        => ['git-branch', 'users', 'search'],
        'outcome-ok'      => ['circle-check', 'check-circle', 'file-check', 'user-check', 'undo-2'],
        'outcome-refused' => ['circle-x', 'ban', 'trash-2', 'refresh-cw'],
    ];

    /** edge kind => [colour, dash pattern or null, stroke width]. */
    public const EDGES = [
        'request'  => ['#0F4C81', null, 1.75],
        'response' => ['#475569', '2 4', 1.75],
        'async'    => ['#0E7490', '8 5', 1.75],
        'refusal'  => ['#B91C1C', '6 3 2 3', 1.75],
    ];

    public static function kinds(): array
    {
        return array_keys(self::KINDS);
    }

    // ── Colour science ────────────────────────────────────────────────────

    /** @return array{float,float,float} sRGB 0..1 */
    public static function rgb(string $hex): array
    {
        $h = ltrim($hex, '#');

        return [hexdec(substr($h, 0, 2)) / 255, hexdec(substr($h, 2, 2)) / 255, hexdec(substr($h, 4, 2)) / 255];
    }

    private static function linear(float $c): float
    {
        return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
    }

    /** WCAG 2.x relative luminance. */
    public static function luminance(string $hex): float
    {
        [$r, $g, $b] = array_map(self::linear(...), self::rgb($hex));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /** WCAG contrast ratio (1..21). */
    public static function contrast(string $a, string $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /** CIE L*a*b* (D65). @return array{float,float,float} */
    public static function lab(string $hex): array
    {
        [$r, $g, $b] = array_map(self::linear(...), self::rgb($hex));
        $x = ($r * 0.4124564 + $g * 0.3575761 + $b * 0.1804375) / 0.95047;
        $y = ($r * 0.2126729 + $g * 0.7151522 + $b * 0.0721750) / 1.00000;
        $z = ($r * 0.0193339 + $g * 0.1191920 + $b * 0.9503041) / 1.08883;
        $f = fn (float $t) => $t > 216 / 24389 ? $t ** (1 / 3) : (24389 / 27 * $t + 16) / 116;
        [$fx, $fy, $fz] = [$f($x), $f($y), $f($z)];

        return [116 * $fy - 16, 500 * ($fx - $fy), 200 * ($fy - $fz)];
    }

    /** CIEDE2000 colour difference (Sharma, Wu, Dalal 2005). */
    public static function deltaE2000(string $hex1, string $hex2): float
    {
        return self::deltaE2000Lab(self::lab($hex1), self::lab($hex2));
    }

    /** CIEDE2000 on two L*a*b* triples (verified against the Sharma test pairs). */
    public static function deltaE2000Lab(array $lab1, array $lab2): float
    {
        [$L1, $a1, $b1] = $lab1;
        [$L2, $a2, $b2] = $lab2;

        $C1 = hypot($a1, $b1);
        $C2 = hypot($a2, $b2);
        $Cb7 = (($C1 + $C2) / 2) ** 7;
        $G = 0.5 * (1 - sqrt($Cb7 / ($Cb7 + 25 ** 7)));
        $a1p = (1 + $G) * $a1;
        $a2p = (1 + $G) * $a2;
        $C1p = hypot($a1p, $b1);
        $C2p = hypot($a2p, $b2);
        $h = function (float $b, float $a): float {
            if ($a == 0.0 && $b == 0.0) {
                return 0.0;
            }
            $d = rad2deg(atan2($b, $a));

            return $d < 0 ? $d + 360 : $d;
        };
        $h1p = $h($b1, $a1p);
        $h2p = $h($b2, $a2p);

        $dLp = $L2 - $L1;
        $dCp = $C2p - $C1p;
        if ($C1p * $C2p == 0.0) {
            $dhp = 0.0;
        } elseif (abs($h2p - $h1p) <= 180) {
            $dhp = $h2p - $h1p;
        } elseif ($h2p - $h1p > 180) {
            $dhp = $h2p - $h1p - 360;
        } else {
            $dhp = $h2p - $h1p + 360;
        }
        $dHp = 2 * sqrt($C1p * $C2p) * sin(deg2rad($dhp / 2));

        $Lbp = ($L1 + $L2) / 2;
        $Cbp = ($C1p + $C2p) / 2;
        if ($C1p * $C2p == 0.0) {
            $hbp = $h1p + $h2p;
        } elseif (abs($h1p - $h2p) <= 180) {
            $hbp = ($h1p + $h2p) / 2;
        } elseif ($h1p + $h2p < 360) {
            $hbp = ($h1p + $h2p + 360) / 2;
        } else {
            $hbp = ($h1p + $h2p - 360) / 2;
        }
        $T = 1 - 0.17 * cos(deg2rad($hbp - 30)) + 0.24 * cos(deg2rad(2 * $hbp))
            + 0.32 * cos(deg2rad(3 * $hbp + 6)) - 0.20 * cos(deg2rad(4 * $hbp - 63));
        $dTheta = 30 * exp(-((($hbp - 275) / 25) ** 2));
        $Cbp7 = $Cbp ** 7;
        $Rc = 2 * sqrt($Cbp7 / ($Cbp7 + 25 ** 7));
        $Sl = 1 + (0.015 * (($Lbp - 50) ** 2)) / sqrt(20 + (($Lbp - 50) ** 2));
        $Sc = 1 + 0.045 * $Cbp;
        $Sh = 1 + 0.015 * $Cbp * $T;
        $Rt = -sin(deg2rad(2 * $dTheta)) * $Rc;

        return sqrt(
            ($dLp / $Sl) ** 2 + ($dCp / $Sc) ** 2 + ($dHp / $Sh) ** 2 + $Rt * ($dCp / $Sc) * ($dHp / $Sh)
        );
    }

    /**
     * Every number the palette promises, for the proof summary and the JSON.
     *
     * @return array{kinds: array, pairs: array, edges: array}
     */
    public static function report(): array
    {
        $kinds = [];
        foreach (self::KINDS as $k => [$fill, $stroke, $text, $sub]) {
            $kinds[$k] = [
                'fill' => $fill, 'stroke' => $stroke, 'text' => $text, 'sub' => $sub,
                'border' => self::BORDER[$k], 'icon' => self::KIND_ICONS[$k],
                'text_contrast' => round(self::contrast($text, $fill), 2),
                'sub_contrast'  => round(self::contrast($sub, $fill), 2),
            ];
        }
        $pairs = [];
        $names = array_keys(self::KINDS);
        foreach ($names as $i => $a) {
            foreach (array_slice($names, $i + 1) as $b) {
                $pairs["{$a}|{$b}"] = round(self::deltaE2000(self::KINDS[$a][0], self::KINDS[$b][0]), 2);
            }
        }
        $edges = [];
        foreach (self::EDGES as $k => [$c, $dash]) {
            $edges[$k] = ['colour' => $c, 'dash' => $dash, 'contrast' => round(self::contrast($c, self::CANVAS), 2)];
        }

        return ['kinds' => $kinds, 'pairs' => $pairs, 'edges' => $edges];
    }
}

<?php

namespace Tests\Feature\Diagrams;

use App\Support\Blog\BlogDiagrams;
use App\Support\Blog\DiagramPdf;
use App\Support\Diagrams\Layout;
use App\Support\Diagrams\Palette;
use App\Support\Diagrams\Proof;
use App\Support\Diagrams\Specs;
use App\Support\Docs\DocsDiagrams;
use App\Support\Icons\Lucide;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Proves every flow and architecture diagram (App\Support\Diagrams\Specs).
 *
 * For each diagram, in English and in French:
 *   graph     ids unique, one start (an actor or partner), BFS reaches every
 *             card, reverse BFS from the outcomes reaches every card (no dead
 *             ends), outcomes are terminal, Kahn's algorithm finds no cycle
 *             once the declared retry loops are set aside (and each declared
 *             loop really closes a cycle), every numbered step is a path
 *   evidence  every route name, METHOD+URI, class, method, property, config
 *             key and artisan command a card cites exists in this app
 *   icons     every icon is in the Lucide set and conventional for its kind
 *   i18n      every label exists in EN and FR
 *   geometry  labels fit their cards, no connector crosses a card, no two
 *             unrelated connectors overlap, no label covers a card or label
 *   palette   WCAG contrast and CIEDE2000 separation, computed
 *
 * A per-diagram proof summary is printed to STDERR after the run.
 */
class DiagramSpecTest extends TestCase
{
    /** @var array<string, string> */
    private static array $summary = [];

    public static function tearDownAfterClass(): void
    {
        if (self::$summary !== []) {
            ksort(self::$summary);
            fwrite(STDERR, "\n\nDiagram proof summary\n" . implode("\n", self::$summary) . "\n");
        }
        parent::tearDownAfterClass();
    }

    public static function diagrams(): array
    {
        $out = [];
        foreach (Specs::ids() as $id) {
            foreach (['en', 'fr'] as $loc) {
                $out["{$id} ({$loc})"] = [$id, $loc];
            }
        }

        return $out;
    }

    #[DataProvider('diagrams')]
    public function test_every_diagram_is_proven(string $id, string $locale): void
    {
        app()->setLocale($locale);
        $checks = Proof::all($id);
        $failed = array_filter($checks);

        $spec = Specs::get($id);
        $refs = array_sum(array_map(fn ($n) => count($n[5]), $spec['nodes']));
        self::$summary["{$id} [{$locale}]"] = sprintf(
            '%-22s %-2s  %2d nodes  %2d edges  %2d steps  %2d evidence refs  %d/%d checks%s',
            $id, $locale, count($spec['nodes']), count($spec['edges']), count($spec['steps']), $refs,
            count($checks) - count($failed), count($checks),
            $failed ? '  FAILED: ' . implode('; ', array_map(fn ($k, $v) => $k . ' ' . implode(', ', $v), array_keys($failed), $failed)) : '  PROVEN'
        );

        $this->assertSame([], $failed, "{$id} [{$locale}] has violations");
        $this->assertCount(21, $checks, 'the full set of checks ran');
    }

    /** PHP silently merges a repeated array key; read the source to rule it out. */
    public function test_spec_source_has_no_repeated_node_ids(): void
    {
        $file = file((new \ReflectionClass(Specs::class))->getFileName());
        foreach (Specs::ids() as $id) {
            $method = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $id))));
            $m = new ReflectionMethod(Specs::class, $method);
            $src = implode('', array_slice($file, $m->getStartLine() - 1, $m->getEndLine() - $m->getStartLine() + 1));
            $block = substr($src, strpos($src, "'nodes' => ["), strpos($src, "'edges' => [") - strpos($src, "'nodes' => ["));
            preg_match_all("/^\s*'([a-z0-9_]+)'\s*=>\s*\['/m", $block, $mm);
            $this->assertSame(array_unique($mm[1]), $mm[1], "{$id}: a node id is declared twice");
            $this->assertCount(count(Specs::get($id)['nodes']), $mm[1], "{$id}: source and spec node counts differ");
        }
    }

    public function test_ciede2000_matches_the_published_reference_pairs(): void
    {
        // Sharma, Wu & Dalal (2005), Table 1: pairs 1, 7, 17, 25 and 34.
        foreach ([
            [[50.0, 2.6772, -79.7751], [50.0, 0.0, -82.7485], 2.0425],
            [[50.0, 0.0, 0.0], [50.0, -1.0, 2.0], 2.3669],
            [[50.0, 2.5, 0.0], [73.0, 25.0, -18.0], 27.1492],
            [[60.2574, -34.0099, 36.2677], [60.4626, -34.1751, 39.4387], 1.2644],
            [[90.8027, -2.0831, 1.441], [91.1528, -1.6435, 0.0447], 1.4441],
        ] as [$a, $b, $expected]) {
            $this->assertEqualsWithDelta($expected, Palette::deltaE2000Lab($a, $b), 0.0001);
        }
        $this->assertEqualsWithDelta(21.0, Palette::contrast('#000000', '#FFFFFF'), 0.001);
    }

    public function test_palette_has_no_collisions_and_meets_wcag(): void
    {
        $r = Palette::report();
        foreach ($r['kinds'] as $kind => $k) {
            $this->assertGreaterThanOrEqual(4.5, $k['text_contrast'], "{$kind} label contrast");
            $this->assertGreaterThanOrEqual(4.5, $k['sub_contrast'], "{$kind} sublabel contrast");
            // The icon is drawn in the kind's stroke colour on a white badge.
            $this->assertGreaterThanOrEqual(3.0, Palette::contrast($k['stroke'], '#FFFFFF'), "{$kind} icon contrast");
        }
        foreach ($r['pairs'] as $pair => $de) {
            $this->assertGreaterThanOrEqual(15.0, $de, "ΔE2000 {$pair}");
        }
        foreach ($r['edges'] as $kind => $e) {
            $this->assertGreaterThanOrEqual(3.0, $e['contrast'], "{$kind} edge on canvas");
            $this->assertGreaterThanOrEqual(3.0, Palette::contrast($e['colour'], '#F8FAFC'), "{$kind} edge on a group frame");
        }
        // Fills are unique per kind, so connected cards of different kinds can never share one.
        $fills = array_column(Palette::KINDS, 0);
        $this->assertSame(array_unique($fills), $fills);
        $edgeColours = array_column(Palette::EDGES, 0);
        $this->assertSame(array_unique($edgeColours), $edgeColours);
    }

    public function test_purple_is_never_used(): void
    {
        $all = array_merge(...array_values(Palette::KINDS));
        $all = array_merge($all, array_column(Palette::EDGES, 0));
        foreach ($all as $hex) {
            [$r, $g, $b] = Palette::rgb($hex);
            $max = max($r, $g, $b);
            $min = min($r, $g, $b);
            if ($max - $min < 0.15) {
                continue; // grey
            }
            $h = match ($max) {
                $r => fmod(60 * (($g - $b) / ($max - $min)) + 360, 360),
                $g => 60 * (($b - $r) / ($max - $min)) + 120,
                default => 60 * (($r - $g) / ($max - $min)) + 240,
            };
            $this->assertFalse($h > 255 && $h < 335, "{$hex} is purple (hue {$h})");
        }
    }

    public function test_colour_is_never_the_only_signal(): void
    {
        $signatures = [];
        foreach (Palette::kinds() as $k) {
            $signatures[] = Palette::BORDER[$k] . '|' . Palette::KIND_ICONS[$k];
            $this->assertTrue(Lucide::has(Palette::KIND_ICONS[$k]), $k);
        }
        $this->assertSame(array_unique($signatures), $signatures, 'border + icon identify every kind');
        $dashes = array_map(fn ($e) => (string) $e[1], Palette::EDGES);
        $this->assertSame(array_unique($dashes), $dashes, 'every edge kind has its own dash pattern');
    }

    public function test_web_svg_and_pdf_are_drawn_from_the_same_spec(): void
    {
        foreach (BlogDiagrams::NAMES as $embed => $id) {
            $spec = Specs::get($id);
            $html = BlogDiagrams::render($embed);
            $this->assertSame(count($spec['nodes']), substr_count($html, 'data-node="'), $id);
            $this->assertSame(count($spec['edges']), substr_count($html, 'class="dg-edge '), $id);
            $this->assertSame(count($spec['steps']), substr_count($html, 'class="dg-step '), $id);
            $this->assertStringContainsString('class="dg-verify"', $html);
            foreach ($spec['nodes'] as $n) {
                $this->assertStringContainsString(str_replace('currentColor', '', explode('"', Lucide::paths($n[1]))[1]), $html, "{$id}: icon {$n[1]}");
            }

            $print = DiagramPdf::printable(BlogDiagrams::render($embed, true));
            $this->assertNotNull($print, $id);
            $this->assertStringNotContainsString('marker-end', $print);
            $this->assertStringNotContainsString('class=', $print);
            $this->assertStringNotContainsString('currentColor', $print);
            $legendEdges = count(array_filter(Layout::build($spec)['legend'], fn ($i) => $i['type'] === 'edge'));
            $this->assertSame(count($spec['edges']) + $legendEdges, substr_count($print, '<polygon'), "{$id}: one arrowhead per arrow");
            $this->assertStringNotContainsString('class="dg-step', BlogDiagrams::render($embed, true));
        }
    }

    public function test_flows_json_publishes_every_proven_flow(): void
    {
        $res = $this->getJson(route('docs.flows.json'))->assertOk()
            ->assertJsonPath('schema', 'opescare.flows/1')
            ->assertJsonStructure(['palette' => ['kinds', 'pairs', 'edges'], 'diagrams']);
        $this->assertSame(Specs::ids(), array_keys($res->json('diagrams')));
        foreach ($res->json('diagrams') as $id => $d) {
            $this->assertSame($d['proof']['total'], $d['proof']['passed'], $id);
            foreach ($d['nodes'] as $n) {
                foreach ($n['evidence'] as $e) {
                    $this->assertTrue($e['resolves'], "{$id}/{$n['id']}: {$e['ref']}");
                }
            }
        }

        $this->get(route('docs.architecture'))->assertOk()
            ->assertSee(route('docs.flows.json'), false)
            ->assertSee('data-node="clients"', false);
    }

    public function test_docs_flow_pages_explain_how_they_are_verified(): void
    {
        foreach (DocsDiagrams::FLOWS as $flow) {
            $page = $this->get(route('docs.flows', $flow))->assertOk();
            $page->assertSee(__('flows.verify.title'));
            foreach (DocsDiagrams::code($flow) as $ref) {
                $page->assertSee($ref);
            }
        }
    }
}

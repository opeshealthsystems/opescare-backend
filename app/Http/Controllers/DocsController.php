<?php

namespace App\Http\Controllers;

use App\Support\Diagrams\Palette;
use App\Support\Diagrams\Proof;
use App\Support\Diagrams\Specs;
use App\Support\Docs\DocsDiagrams;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Public developer documentation controller.
 * All routes are publicly accessible — no auth middleware.
 *
 * Every page receives $docsBase (config docs.public_url, https://opescare.com)
 * so code samples never print a local or staging host.
 */
class DocsController extends Controller
{
    public function index(): View          { return $this->page('docs.index'); }
    public function authentication(): View { return $this->page('docs.authentication'); }
    public function api(): View            { return $this->page('docs.api'); }
    public function sdk(): View            { return $this->page('docs.sdk'); }
    public function bridge(): View         { return $this->page('docs.bridge'); }
    public function widget(): View         { return $this->page('docs.widget'); }
    public function webhooks(): View       { return $this->page('docs.webhooks'); }
    public function errors(): View         { return $this->page('docs.errors'); }
    public function playground(): View     { return $this->page('docs.playground'); }
    public function changelog(): View      { return $this->page('docs.changelog'); }
    public function architecture(): View   { return $this->page('docs.architecture'); }

    public function flow(string $flow): View
    {
        abort_unless(in_array($flow, DocsDiagrams::FLOWS, true), 404);

        return $this->page('docs.flow', ['flow' => $flow]);
    }

    /**
     * Every flow and architecture diagram as machine-readable JSON: the same
     * spec the SVG, the step list and the PDF are drawn from, each card's
     * evidence with whether it resolves in this running app, the graph and
     * layout proofs, and the palette with its contrast and ΔE2000 numbers.
     */
    public function flowsJson(): JsonResponse
    {
        $diagrams = [];
        foreach (Specs::ids() as $id) {
            $spec = Specs::get($id);
            $checks = Proof::all($id);
            $diagrams[$id] = [
                'title'   => __($spec['title']),
                'summary' => __($spec['summary']),
                'nodes'   => array_map(fn ($id, $n) => [
                    'id' => $id, 'kind' => $n[0], 'icon' => $n[1], 'cell' => $n[2],
                    'label' => __($n[3]), 'sublabel' => __($n[4]),
                    'evidence' => array_map(fn ($r) => ['ref' => $r, 'resolves' => Proof::resolves($r)], $n[5]),
                ], array_keys($spec['nodes']), $spec['nodes']),
                'edges' => array_map(fn ($e) => [
                    'from' => $e[0], 'to' => $e[1], 'kind' => $e[2],
                    'label' => isset($e[3]) ? __($e[3]) : null,
                    'loop' => (bool) ($e[4]['loop'] ?? false),
                ], $spec['edges']),
                'steps' => array_map(fn ($s) => ['path' => $s[0], 'title' => __($s[1]), 'text' => __($s[2])], $spec['steps']),
                'proof' => [
                    'passed' => count(array_filter($checks, fn ($v) => $v === [])),
                    'total'  => count($checks),
                    'checks' => array_map(fn ($v) => ['ok' => $v === [], 'violations' => $v], $checks),
                ],
                'page' => in_array($id, DocsDiagrams::FLOWS, true) ? route('docs.flows', $id) : ($id === 'overview' ? route('docs.architecture') : null),
            ];
        }

        return response()->json([
            'schema'   => 'opescare.flows/1',
            'locale'   => app()->getLocale(),
            'source'   => 'App\Support\Diagrams\Specs',
            'palette'  => Palette::report(),
            'diagrams' => $diagrams,
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ->header('Cache-Control', 'public, max-age=300');
    }

    private function page(string $view, array $data = []): View
    {
        return view($view, $data + ['docsBase' => (string) config('docs.public_url', 'https://opescare.com')]);
    }
}

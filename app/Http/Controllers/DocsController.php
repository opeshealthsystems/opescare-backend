<?php

namespace App\Http\Controllers;

use App\Support\Docs\DocsDiagrams;
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

    private function page(string $view, array $data = []): View
    {
        return view($view, $data + ['docsBase' => (string) config('docs.public_url', 'https://opescare.com')]);
    }
}

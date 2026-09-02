<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Public developer documentation controller.
 * All routes are publicly accessible — no auth middleware.
 */
class DocsController extends Controller
{
    public function index(): View      { return view('docs.index'); }
    public function authentication(): View { return view('docs.authentication'); }
    public function api(): View        { return view('docs.api'); }
    public function sdk(): View        { return view('docs.sdk'); }
    public function bridge(): View     { return view('docs.bridge'); }
    public function widget(): View     { return view('docs.widget'); }
    public function webhooks(): View   { return view('docs.webhooks'); }
    public function errors(): View     { return view('docs.errors'); }
    public function playground(): View { return view('docs.playground'); }
    public function changelog(): View  { return view('docs.changelog'); }
}

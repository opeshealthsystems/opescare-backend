<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Public "Request a demo" funnel. Captures B2B interest into the leads
 * pipeline. No authentication — CSRF + validation protect the endpoint.
 */
class RequestDemoController extends Controller
{
    public function show(Request $request)
    {
        $type = $request->query('type');
        if (! in_array($type, Lead::ORGANIZATION_TYPES, true)) {
            $type = null;
        }

        return view('public.request-demo', [
            'preselectedType' => $type,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'organization_name' => ['required', 'string', 'max:255'],
            'organization_type' => ['required', Rule::in(Lead::ORGANIZATION_TYPES)],
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'email', 'max:255'],
            'phone'             => ['nullable', 'string', 'max:50'],
            'message'           => ['nullable', 'string', 'max:5000'],
        ]);

        $source = $request->input('source') === 'pricing' ? 'pricing' : 'request_demo';

        Lead::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'phone'             => $validated['phone'] ?? null,
            'organization_name' => $validated['organization_name'],
            'organization_type' => $validated['organization_type'],
            'message'           => $validated['message'] ?? null,
            'source'            => $source,
            'status'            => 'new',
        ]);

        return redirect()
            ->route('public.request-demo')
            ->with('demo_success', true);
    }
}

<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Public "Solutions" catalogue: the OPES health systems listed on
 * opesware.com/health (config/solutions.php + lang/{en,fr}/solutions.php),
 * one detail page each, and a request form that files a lead (source
 * 'solutions') into the existing admin leads desk — to buy a system, or to
 * ask for one free as a donation.
 */
class SolutionsController extends Controller
{
    /** @return array<string, array<string, mixed>> */
    public static function systems(): array
    {
        return (array) config('solutions.systems', []);
    }

    /** @return list<string> */
    public static function slugs(): array
    {
        return array_keys(self::systems());
    }

    public function index(): View
    {
        $grouped = [];
        foreach (self::systems() as $slug => $system) {
            $grouped[$system['category']][$slug] = $system;
        }

        return view('public.solutions.index', [
            'grouped'    => $grouped,
            'systems'    => self::systems(),
            'categories' => (array) config('solutions.categories', []),
        ]);
    }

    public function show(string $slug): View
    {
        $systems = self::systems();
        abort_unless(isset($systems[$slug]), 404);

        return view('public.solutions.show', [
            'slug'   => $slug,
            'system' => $systems[$slug],
        ]);
    }

    public function requestForm(Request $request): View
    {
        // Pre-select a system from the detail page's CTA. Only a configured
        // slug is accepted; nothing personal ever travels in the query string.
        $system = (string) $request->query('system', '');
        $kind   = (string) $request->query('kind', '');

        return view('public.solutions.request', [
            'systems'           => self::systems(),
            'facilityTypes'     => (array) config('solutions.facility_types', []),
            'preselectedSystem' => in_array($system, self::slugs(), true) ? $system : '',
            'preselectedKind'   => in_array($kind, Lead::REQUEST_KINDS, true) ? $kind : 'purchase',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Honeypot: a bot that fills the hidden field gets the same thank-you
        // and nothing is stored.
        if (filled($request->input('website'))) {
            return redirect()->route('public.solutions.request')->with('solutions_success', true);
        }

        $validated = $request->validate([
            'system'        => ['nullable', 'string', Rule::in(self::slugs())],
            'request_kind'  => ['required', Rule::in(Lead::REQUEST_KINDS)],
            'facility_name' => ['required', 'string', 'max:255'],
            'facility_type' => ['required', Rule::in((array) config('solutions.facility_types', []))],
            'city'          => ['required', 'string', 'max:120'],
            'contact_name'  => ['required', 'string', 'max:255'],
            'phone'         => ['required', 'string', 'max:50'],
            'email'         => ['required', 'email', 'max:255'],
            'message'       => ['nullable', 'string', 'max:5000'],
            'has_system'    => ['required', Rule::in(['yes', 'no'])],
        ]);

        Lead::create([
            'name'              => $validated['contact_name'],
            'email'             => $validated['email'],
            'phone'             => $validated['phone'],
            'organization_name' => $validated['facility_name'],
            'organization_type' => match ($validated['facility_type']) {
                'pharmacy'   => 'pharmacy',
                'laboratory' => 'lab',
                'other'      => 'other',
                default      => 'facility',
            },
            'message'       => $validated['message'] ?? null,
            'source'        => Lead::SOURCE_SOLUTIONS,
            'request_kind'  => $validated['request_kind'],
            'solution_slug' => $validated['system'] ?? null,
            'facility_type' => $validated['facility_type'],
            'city'          => $validated['city'],
            'has_system'    => $validated['has_system'] === 'yes',
            'status'        => 'new',
        ]);

        return redirect()->route('public.solutions.request')->with('solutions_success', true);
    }
}

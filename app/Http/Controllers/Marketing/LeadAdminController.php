<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin leads inbox. Lists captured leads and lets admins move them through
 * the pipeline (new -> contacted -> qualified -> won/lost). Mounted behind the
 * full portal/admin middleware stack in routes/marketing.php.
 */
class LeadAdminController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        if (! in_array($status, Lead::STATUSES, true)) {
            $status = null;
        }

        $leads = Lead::query()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $counts = Lead::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('portals.admin.leads.index', [
            'leads'         => $leads,
            'activeStatus'  => $status,
            'counts'        => $counts,
            'totalCount'    => (int) $counts->sum(),
        ]);
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(Lead::STATUSES)],
            'note'   => ['nullable', 'string', 'max:5000'],
        ]);

        $lead->status = $validated['status'];

        if (! empty($validated['note'])) {
            $stamp = now()->format('Y-m-d H:i');
            $entry = "[{$stamp}] " . $validated['note'];
            $lead->notes = $lead->notes
                ? $lead->notes . "\n" . $entry
                : $entry;
        }

        $lead->save();

        return redirect()
            ->route('portals.admin.leads')
            ->with('success', __('leads.admin.flash_updated'));
    }
}

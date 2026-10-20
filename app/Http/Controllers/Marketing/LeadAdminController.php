<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Services\Facility\StaffInviteService;
use App\Services\Marketing\LeadConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // Solutions requests (/solutions/request): purchase vs donation.
        $kind = $request->query('kind');
        if (! in_array($kind, Lead::REQUEST_KINDS, true)) {
            $kind = null;
        }

        $leads = Lead::query()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($kind, fn ($q) => $q->where('source', Lead::SOURCE_SOLUTIONS)->where('request_kind', $kind))
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
            'activeKind'    => $kind,
            'counts'        => $counts,
            'totalCount'    => (int) $counts->sum(),
        ]);
    }

    /**
     * GET /admin/leads/{lead} — the lead on its own page: the message as it
     * was sent, the dated notes the status changes left behind (the inbox
     * never showed them), and the tenant it became if it was converted.
     */
    public function show(Lead $lead)
    {
        return view('portals.admin.leads.show', [
            'lead'   => $lead,
            'tenant' => $lead->isConverted() ? $lead->convertedFacility : null,
        ]);
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            // `converted` is written by the conversion only, never by hand.
            'status' => ['required', Rule::in(array_diff(Lead::STATUSES, ['converted']))],
            'note'   => ['nullable', 'string', 'max:5000'],
        ]);

        if ($lead->isConverted()) {
            return redirect()->route('portals.admin.leads')
                ->with('error', __('leads.conversion.error_already_converted'));
        }

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

    // ── Lead → tenant (P-6) ────────────────────────────────────────────────

    /**
     * GET /admin/leads/{lead}/convert — the form. Re-submitted with ?type,
     * ?city, ?q to refresh the short list of unlinked listings to link.
     */
    public function convertForm(Request $request, Lead $lead, LeadConversionService $conversion)
    {
        $type = (string) $request->query('type', old('facility_type', LeadConversionService::suggestedTypeFor($lead->organization_type) ?? ''));
        $city = (string) $request->query('city', old('city', ''));
        $name = (string) $request->query('q', old('name', $lead->organization_name ?? ''));

        if (! in_array($type, LeadConversionService::TYPES, true)) {
            $type = '';
        }

        return view('portals.admin.leads.convert', [
            'lead'        => $lead,
            'tenant'      => $lead->isConverted() ? $lead->convertedFacility : null,
            'types'       => LeadConversionService::TYPES,
            'type'        => $type,
            'city'        => $city,
            'name'        => $name,
            'candidates'  => $type !== '' && ! $lead->isConverted() ? $conversion->candidateListings($type, $city, $name) : collect(),
            'roleLabel'   => $type !== '' ? StaffInviteService::roleLabel(LeadConversionService::managerRoleFor($type)) : null,
        ]);
    }

    /** POST /admin/leads/{lead}/convert — one transaction, see LeadConversionService. */
    public function convert(Request $request, Lead $lead, LeadConversionService $conversion)
    {
        if ($lead->isConverted()) {
            return redirect()->route('portals.admin.leads.convert', $lead)
                ->with('error', __('leads.conversion.error_already_converted'));
        }

        $validated = $request->validate(LeadConversionService::rules());

        try {
            $result = $conversion->convert($lead, $validated, Auth::user(), $request->ip());
        } catch (\RuntimeException $e) {
            $key = match ($e->getMessage()) {
                'LEAD_ALREADY_CONVERTED' => 'error_already_converted',
                'LEAD_NO_EMAIL'          => 'error_no_email',
                'LEAD_EMAIL_TAKEN'       => 'error_email_taken',
                'LISTING_NOT_FOUND',
                'LISTING_TYPE_MISMATCH'  => 'error_listing_not_offered',
                'LISTING_ALREADY_LINKED' => 'error_listing_linked',
                'ROLE_UNKNOWN'           => 'error_role_unknown',
                default                  => null,
            };

            if ($key === null) {
                throw $e;
            }

            return redirect()->route('portals.admin.leads.convert', $lead)
                ->withInput()
                ->with('error', __('leads.conversion.' . $key));
        }

        return redirect()
            ->route('portals.admin.leads.convert', $lead)
            ->with('success', __('leads.conversion.flash_converted', ['name' => $result['facility']->name]))
            ->with('mail_status', $result['mail']['message'])
            ->with('mail_status_ok', $result['mail']['ok'])
            ->with('invite_link', route('invite.accept', $result['rawToken']))
            ->with('invite_link_for', $result['invite']->email);
    }
}

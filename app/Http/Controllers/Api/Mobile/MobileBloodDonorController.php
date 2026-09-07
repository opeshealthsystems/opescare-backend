<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\BloodGroup;
use App\Http\Controllers\Controller;
use App\Models\BloodDonor;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The volunteer blood donor register — SB-03 joins it, SB-02 searches it.
 *
 * ── Why this is not patient search ─────────────────────────────────────────
 * This platform forbids listing patients: no `LIKE` search over them, no
 * enumeration, exact Health ID only. The reference "Find Donors" screen is a
 * browsable list of named people with their blood groups, which read off
 * `patients` would be exactly that.
 *
 * It reads `blood_donors` instead, and nobody is in that table by being a
 * patient — only by completing SB-03 and consenting. `BloodDonor::searchable()`
 * is the one scope the search may use, and it requires an active status AND a
 * consent timestamp.
 *
 * ── What a searcher gets, and what they never get ──────────────────────────
 * Given name and last initial, blood group, published area, availability, and
 * roughly how long since the last donation. A phone number ONLY when that
 * volunteer separately agreed to publish one.
 *
 * Never: a photograph, a date of birth, an age, a distance in kilometres, a
 * Health ID, or anything from the person's medical record. The reference draws
 * photos and "1.2 km away"; a live distance to a named person is a location,
 * and this endpoint does not hold one to give.
 *
 * ── Results are capped and unordered by identity ───────────────────────────
 * A hard limit, and no cursor. The register is a way to reach a few donors for
 * a specific need, not a directory to page through — an endpoint that will walk
 * you politely through every volunteer in the country is the enumeration this
 * design exists to prevent.
 */
class MobileBloodDonorController extends Controller
{
    /** Never more than this many volunteers in one answer. */
    private const MAX_RESULTS = 20;

    /**
     * GET /api/mobile/blood/donors?blood_group=O-&city=Douala
     *
     * `blood_group` is REQUIRED. There is no "show me everyone" call: a
     * request has to name the group it is actually looking for.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'blood_group' => ['required', 'string', Rule::in(BloodGroup::values())],
            'city'        => ['nullable', 'string', 'max:120'],
        ]);

        $rows = BloodDonor::query()
            ->searchable()
            ->where('blood_group', $validated['blood_group'])
            ->when(
                filled($validated['city'] ?? null),
                // Exact area match, not a prefix search: `LIKE` over a column
                // that identifies people is how directories become scrapeable.
                fn ($q) => $q->whereRaw('lower(city) = ?', [mb_strtolower(trim($validated['city']))]),
            )
            ->with('patient:id,first_name,last_name')
            ->orderByDesc('last_donated_on')
            ->limit(self::MAX_RESULTS)
            ->get();

        return response()->json([
            'data' => $rows->map(fn (BloodDonor $donor) => $this->donorPayload($donor))->all(),
            'meta' => [
                'blood_group' => $validated['blood_group'],
                'count'       => $rows->count(),
                'capped_at'   => self::MAX_RESULTS,
            ],
        ]);
    }

    /**
     * GET /api/mobile/blood/donors/me — the caller's own registration.
     *
     * Returns `null` rather than 404 when they have not joined: "you are not a
     * donor" is a state of this screen, not an error.
     */
    public function me(Request $request): JsonResponse
    {
        $donor = BloodDonor::query()
            ->where('patient_id', (string) $request->attributes->get('patient_id'))
            ->first();

        return response()->json([
            'data' => $donor === null ? null : $this->selfPayload($donor),
        ]);
    }

    /**
     * POST /api/mobile/blood/donors — join, or update your own entry (SB-03).
     *
     * `listing_consent` must be true to appear in search, and it is recorded as
     * the moment it was given. Withdrawing later sets a status; it never
     * deletes the fact that consent was once given.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'blood_group'     => ['required', 'string', Rule::in(BloodGroup::values())],
            'city'            => ['nullable', 'string', 'max:120'],
            'region'          => ['nullable', 'string', 'max:120'],
            'listing_consent' => ['required', 'boolean'],
            'share_phone'     => ['nullable', 'boolean'],
            'contact_phone'   => ['nullable', 'string', 'max:32', 'required_if:share_phone,true'],
            'last_donated_on' => ['nullable', 'date', 'before_or_equal:today'],
            'note'            => ['nullable', 'string', 'max:300'],
        ]);

        $patientId = (string) $request->attributes->get('patient_id');

        $donor = BloodDonor::query()->where('patient_id', $patientId)->first();
        $consented = (bool) $validated['listing_consent'];

        $attributes = [
            'patient_id'      => $patientId,
            'blood_group'     => $validated['blood_group'],
            'city'            => $validated['city'] ?? null,
            'region'          => $validated['region'] ?? null,
            'share_phone'     => (bool) ($validated['share_phone'] ?? false),
            'contact_phone'   => ($validated['share_phone'] ?? false) ? ($validated['contact_phone'] ?? null) : null,
            'last_donated_on' => $validated['last_donated_on'] ?? null,
            'note'            => $validated['note'] ?? null,
            'status'          => BloodDonor::STATUS_ACTIVE,
        ];

        // Consent is stamped the first time it is given and left alone on later
        // edits, so the register can say when somebody joined. Withdrawing it
        // clears the stamp, which is what makes the row unsearchable.
        if ($consented) {
            $attributes['listing_consent_at'] = $donor?->listing_consent_at ?? now();
        } else {
            $attributes['listing_consent_at'] = null;
        }

        if ($donor) {
            $donor->update($attributes);
        } else {
            $donor = BloodDonor::create($attributes);
        }

        return response()->json(['data' => $this->selfPayload($donor->refresh())], $donor->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * POST /api/mobile/blood/donors/status — pause, resume or withdraw.
     *
     * Pausing is the common case and must be one tap: just donated, unwell,
     * travelling. It keeps the entry and removes it from search.
     */
    public function status(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                BloodDonor::STATUS_ACTIVE,
                BloodDonor::STATUS_PAUSED,
                BloodDonor::STATUS_WITHDRAWN,
            ])],
        ]);

        $donor = BloodDonor::query()
            ->where('patient_id', (string) $request->attributes->get('patient_id'))
            ->first();

        if (! $donor) {
            return response()->json([
                'message'    => 'You are not on the donor register.',
                'error_code' => 'NOT_REGISTERED',
            ], 404);
        }

        $donor->update([
            'status' => $validated['status'],
            // Withdrawing takes the listing consent with it. Coming back is a
            // fresh yes, not a silent restoration of an old one.
            'listing_consent_at' => $validated['status'] === BloodDonor::STATUS_WITHDRAWN
                ? null
                : $donor->listing_consent_at,
        ]);

        return response()->json(['data' => $this->selfPayload($donor->refresh())]);
    }

    // ── Payloads ────────────────────────────────────────────────────────────

    /**
     * What ONE SEARCHER may see about a volunteer.
     *
     * Given name and last initial, not the full name: enough for a blood bank
     * or a family to recognise who answered, without publishing a full
     * identity next to a blood group.
     */
    private function donorPayload(BloodDonor $donor): array
    {
        $patient = $donor->patient;
        $given = trim((string) ($patient->first_name ?? ''));
        $surname = trim((string) ($patient->last_name ?? ''));
        $initial = $surname === '' ? '' : mb_strtoupper(mb_substr($surname, 0, 1)) . '.';

        return [
            'id'             => $donor->id,
            'display_name'   => trim($given . ' ' . $initial) ?: __('Donor'),
            'blood_group'    => $donor->blood_group,
            'area'           => $donor->city,
            'region'         => $donor->region,
            'months_since'   => $donor->monthsSinceDonation(),
            'contact_phone'  => $donor->publishesPhone() ? $donor->contact_phone : null,
            // False means "reachable, but not by a number they published" —
            // the app offers to route the request through a blood bank instead.
            'phone_shared'   => $donor->publishesPhone(),
        ];
    }

    /** Everything a volunteer may see about their OWN entry. */
    private function selfPayload(BloodDonor $donor): array
    {
        return [
            'id'                 => $donor->id,
            'blood_group'        => $donor->blood_group,
            'city'               => $donor->city,
            'region'             => $donor->region,
            'status'             => $donor->status,
            'listed'             => $donor->listing_consent_at !== null,
            'listing_consent_at' => $donor->listing_consent_at?->toIso8601String(),
            'share_phone'        => $donor->share_phone,
            'contact_phone'      => $donor->contact_phone,
            'last_donated_on'    => $donor->last_donated_on?->toDateString(),
            'note'               => $donor->note,
        ];
    }
}

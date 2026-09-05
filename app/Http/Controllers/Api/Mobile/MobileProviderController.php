<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\CareFacility;
use App\Models\ProviderCredential;
use App\Models\StaffProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mobile Patient API — Clinician / Specialist Directory.
 *
 * Surfaces the clinicians a patient can actually be seen by. Before this
 * controller, `appointment_slots.provider_id` existed and was even echoed to
 * the client by MobileFacilityController::slots(), but nothing ever resolved
 * it to a human — the patient booked "a 09:00 slot", never "Dr Sow at 09:00".
 *
 * ── What is (and is not) returned ──────────────────────────────────────────
 * This is *directory* data — a clinician's public professional identity at a
 * publicly-listed facility (name, job title, department, active licences).
 * It is **not** patient clinical data, so no ConsentGrant gate applies here
 * (contrast MobileLabController / MobilePrescriptionController, which are
 * patient-scoped). The one patient-scoped field on this surface,
 * `messaging_appointment_id`, is resolved strictly from the caller's own
 * `patient_id` in `$request->attributes` — never from request input.
 *
 * ── Scoping ───────────────────────────────────────────────────────────────
 * Staff-to-facility scoping follows the exact pattern already established by
 * App\Modules\Staff\Services\StaffService::listStaff() —
 * `StaffProfile::where('facility_id', $facilityId)` — no new mechanism is
 * invented here. The facility id is taken from the route parameter and
 * resolved through the public `care_facilities` directory the patient
 * browsed, then through its linked internal `facilities` row (the same hop
 * MobileFacilityController::slots() makes). Only `status = active`,
 * `staff_category = clinical` profiles with a linked `users` row are listed —
 * a provider is only addressable if `appointment_slots.provider_id` can point
 * at them.
 *
 * ── Column reality check (verified against the migrations, not guessed) ────
 * `staff_profiles` (2026_05_27_000002_create_staff_hr_tables.php) has
 * first_name, last_name, job_title, department, staff_category,
 * employment_type, status — and NO bio or photo column. `professional_licenses`
 * carries `profession` (doctor/nurse/pharmacist/...) and `issuing_body`;
 * `provider_credentials` (2026_05_28_007000) carries credential_type /
 * issuing_body keyed by `provider_id` (users.id). The profile therefore reads
 * as title + department + verified credentials rather than as a prose bio —
 * there is no bio to serve and none is fabricated.
 */
class MobileProviderController extends Controller
{
    /** Only clinicians are patient-facing; admin/support staff are never listed. */
    private const CLINICAL_CATEGORY = 'clinical';

    /**
     * Why a facility's clinician list is empty. The client needs the three
     * cases apart, because the honest next step differs in each:
     *
     *   not_connected  — the listing has no `facilities` row behind it. Nothing
     *                    will be published here until the operator signs up.
     *                    Offer the phone number.
     *   none_published — the operator IS on OpesCare but has published no
     *                    clinical staff profiles. If the calendar has open
     *                    slots the patient can still book (a slot is bound to a
     *                    facility, not to a named clinician), so offer booking.
     *   published      — there is a roster; it is in `data`.
     */
    private const ROSTER_NOT_CONNECTED  = 'not_connected';
    private const ROSTER_NONE_PUBLISHED = 'none_published';
    private const ROSTER_PUBLISHED      = 'published';

    /**
     * GET /api/mobile/facilities/{careFacilityId}/providers
     *
     * Lists the active clinicians at one directory facility. Mirrors the
     * contract of MobileFacilityController::slots(): `facility_id` is the
     * internal `facilities` id (null when the directory entry is unlinked),
     * so the client can correlate a provider with the `provider_id` carried
     * on each slot.
     *
     * ── Why the empty case now carries more than `[]` ─────────────────────
     * Of 2 728 active directory listings only 468 carry a `facility_id`; of
     * those 468, zero currently publish a clinical staff profile while 121 do
     * have open slots. An empty `data` array therefore meant three very
     * different things and the app could not tell them apart, so it showed the
     * same dead end for all three. `roster_status`, `connected` and
     * `open_slot_count` split them, and `message` states the reason in the
     * caller's language. None of it invents a clinician — an empty roster is
     * still reported as empty.
     */
    public function index(string $careFacilityId): JsonResponse
    {
        $careFacility = CareFacility::where('listing_status', 'active')
            ->findOrFail($careFacilityId);

        // Directory entry with no linked internal facility has no staff roster.
        if (! $careFacility->facility_id) {
            return response()->json([
                'facility_id'      => null,
                'care_facility_id' => $careFacility->id,
                'facility_name'    => $careFacility->facility_name,
                'connected'        => false,
                'roster_status'    => self::ROSTER_NOT_CONNECTED,
                'provider_count'   => 0,
                'open_slot_count'  => 0,
                'message'          => __('facility_registry.roster.not_connected'),
                'data'             => [],
            ]);
        }

        $profiles = StaffProfile::where('facility_id', $careFacility->facility_id)
            ->where('status', 'active')
            ->where('staff_category', self::CLINICAL_CATEGORY)
            ->whereNotNull('user_id')
            ->with(['licenses'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $credentials = $this->activeCredentialsFor($profiles->pluck('user_id')->all());

        $openSlotCount = $this->openSlotCountFor($careFacility->facility_id);
        $rosterStatus  = $profiles->isNotEmpty() ? self::ROSTER_PUBLISHED : self::ROSTER_NONE_PUBLISHED;

        return response()->json([
            'facility_id'      => $careFacility->facility_id,
            'care_facility_id' => $careFacility->id,
            'facility_name'    => $careFacility->facility_name,
            'connected'        => true,
            'roster_status'    => $rosterStatus,
            'provider_count'   => $profiles->count(),
            // A slot belongs to a facility, not to a name. When the roster is
            // empty but the calendar is not, booking still works — so say so
            // instead of leaving the patient on a dead screen.
            'open_slot_count'  => $openSlotCount,
            'message'          => $this->rosterMessage($rosterStatus, $profiles->count(), $openSlotCount),
            'data'             => $profiles
                ->map(fn (StaffProfile $p) => $this->formatProvider(
                    $p,
                    $careFacility,
                    $credentials->get($p->user_id, collect())->all(),
                ))
                ->values(),
        ]);
    }

    /**
     * GET /api/mobile/providers/{providerId}
     *
     * One clinician's directory profile plus their next open slots.
     *
     * `providerId` is a `users.id` — the same value the client already has
     * from `AppointmentSlotOption.provider_id`. Resolution deliberately runs
     * through the clinical-staff-profile + active-listing filter rather than
     * `User::findOrFail()`, so probing arbitrary user UUIDs cannot enumerate
     * non-clinical accounts: anything that is not a listed clinician is a 404.
     */
    public function show(Request $request, string $providerId): JsonResponse
    {
        // staff_profiles.user_id is UNIQUE — at most one profile per user.
        $profile = StaffProfile::where('user_id', $providerId)
            ->where('status', 'active')
            ->where('staff_category', self::CLINICAL_CATEGORY)
            ->with(['licenses'])
            ->first();

        $careFacility = $profile
            ? CareFacility::where('facility_id', $profile->facility_id)
                ->where('listing_status', 'active')
                ->first()
            : null;

        if (! $profile || ! $careFacility) {
            return response()->json([
                'error_code' => 'PROVIDER_NOT_FOUND',
                'message'    => 'No listed clinician matches that id.',
            ], 404);
        }

        $credentials = $this->activeCredentialsFor([$providerId])
            ->get($providerId, collect())
            ->all();

        $provider = $this->formatProvider($profile, $careFacility, $credentials);

        // Next open slots for THIS clinician — same query shape as
        // MobileFacilityController::slots(), narrowed by provider_id.
        $slots = AppointmentSlot::where('facility_id', $profile->facility_id)
            ->where('provider_id', $providerId)
            ->where('status', 'open')
            ->where('starts_at', '>=', now())
            ->whereRaw('booked_count < capacity')
            ->orderBy('starts_at')
            ->limit(10)
            ->get();

        $provider['next_slots'] = $slots->map(fn (AppointmentSlot $s) => [
            'id'              => $s->id,
            'starts_at'       => $s->starts_at->toIso8601String(),
            'ends_at'         => $s->ends_at->toIso8601String(),
            'available_count' => $s->capacity - $s->booked_count,
            'provider_id'     => $s->provider_id,
        ])->values();

        // Messaging requires proof of an existing care relationship (see
        // MobileMessagingController::start(), which validates the appointment
        // belongs to the caller). Resolve the caller's own most recent
        // appointment with this clinician so the app can show a working
        // "Message" action instead of a button that 404s. patient_id comes
        // from the auth middleware attribute, never from request input.
        $patientId = $request->attributes->get('patient_id');
        $provider['messaging_appointment_id'] = $patientId
            ? Appointment::where('patient_id', $patientId)
                ->where('provider_id', $providerId)
                ->orderByDesc('scheduled_at')
                ->value('id')
            : null;

        return response()->json(['data' => $provider]);
    }

    // -------------------------------------------------------------------------

    /**
     * Open, future, not-yet-full slots at one internal facility.
     *
     * Same predicate as MobileFacilityController::slots(), so "121 facilities
     * are bookable" and "this facility is bookable" can never disagree.
     */
    private function openSlotCountFor(string $internalFacilityId): int
    {
        return AppointmentSlot::where('facility_id', $internalFacilityId)
            ->where('status', 'open')
            ->where('starts_at', '>=', now())
            ->whereColumn('booked_count', '<', 'capacity')
            ->count();
    }

    /** The reason line for a roster, in the caller's language. */
    private function rosterMessage(string $rosterStatus, int $providerCount, int $openSlotCount): string
    {
        if ($rosterStatus === self::ROSTER_PUBLISHED) {
            return trans_choice('facility_registry.roster.published', $providerCount, ['count' => $providerCount]);
        }

        return $openSlotCount > 0
            ? __('facility_registry.roster.slots_without_roster')
            : __('facility_registry.roster.none_published');
    }

    /**
     * Active ProviderCredential rows for a set of users, keyed by provider_id.
     *
     * @param  array<int, string>  $providerIds
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, ProviderCredential>>
     */
    private function activeCredentialsFor(array $providerIds): \Illuminate\Support\Collection
    {
        if ($providerIds === []) {
            return collect();
        }

        return ProviderCredential::whereIn('provider_id', $providerIds)
            ->where('status', 'active')
            ->orderBy('credential_type')
            ->get()
            ->groupBy('provider_id');
    }

    /**
     * Directory shape for one clinician.
     *
     * `id` is the users.id — deliberately the same identifier the client sees
     * on a slot's `provider_id`, so "who is this slot with?" is answerable
     * client-side without another round trip.
     *
     * @param  array<int, ProviderCredential>  $credentials
     * @return array<string, mixed>
     */
    private function formatProvider(StaffProfile $profile, CareFacility $careFacility, array $credentials): array
    {
        // professional_licenses.profession is the closest thing the schema has
        // to "is this a doctor or a nurse" — prefer an active licence.
        $licenses = $profile->licenses ?? collect();
        $activeLicense = $licenses->firstWhere('status', 'active') ?? $licenses->first();

        return [
            'id'               => $profile->user_id,
            'staff_profile_id' => $profile->id,
            'name'             => $profile->full_name,
            'job_title'        => $profile->job_title,
            'department'       => $profile->department,
            'profession'       => $activeLicense?->profession,
            'employment_type'  => $profile->employment_type,
            'facility_id'      => $profile->facility_id,
            'care_facility_id' => $careFacility->id,
            'facility_name'    => $careFacility->facility_name,
            'city'             => $careFacility->city,
            // Verified professional standing — the patient-facing substitute
            // for a bio, which this schema simply does not store.
            'credentials'      => array_values(array_map(fn (ProviderCredential $c) => [
                'type'         => $c->credential_type,
                'issuing_body' => $c->issuing_body,
            ], $credentials)),
            'licenses'         => $licenses
                ->where('status', 'active')
                ->map(fn ($l) => [
                    'profession'   => $l->profession,
                    'issuing_body' => $l->issuing_body,
                ])
                ->values(),
        ];
    }
}

<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\CareFacility;
use App\Models\ProviderCredential;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
     * ══════════════════════════════════════════════════════════════════════
     *  WHAT THIS APP CALLS "A CLINICIAN AT A FACILITY"
     * ══════════════════════════════════════════════════════════════════════
     *
     * This used to be one thing: an active, clinical `staff_profiles` row. It
     * was the wrong thing, and the doctor finder was empty because of it.
     *
     * `staff_profiles` is the HR record, and almost nothing writes to it — the
     * whole dev database holds ONE row, and it is a receptionist. Meanwhile
     * `POST /api/mobile/auth/provider/login` happily signs clinicians in, and
     * the web portal has posted them at facilities for months, because BOTH of
     * those resolve a posting through App\Services\Portal\FacilityMembershipService:
     *
     *     1. an ACTIVE `facility_role_assignments` row  (the modelled posting)
     *     2. an ACTIVE `staff_profiles` row             (the HR record)
     *     3. `users.primary_facility_id`                (the single-facility default)
     *
     * So a real clinician could sign in at Hôpital Central de Yaoundé, write
     * prescriptions there, and still not exist as far as the patient-facing
     * directory was concerned. `provider_count` was 0 on every row of the
     * register — which is exactly why "Find a doctor" degraded into a list of
     * pharmacies with "None listed" beside each one.
     *
     * A clinician is therefore now: a user who HOLDS A POSTING at the facility
     * (any of the three forms above) AND is clinical — either by the HR record
     * (`staff_category = 'clinical'`) or by the role their posting carries.
     *
     * This invents nobody. Each of the three sources is an existing, written
     * record of a person working at a named facility; all that changed is that
     * the directory stopped looking at only one of them.
     */

    /**
     * Role keys a patient can be SEEN BY — the clinical half of
     * `roles.dashboard_profile_key`.
     *
     * Deliberately narrower than `User::isProfessional()`, which the provider
     * LOGIN uses: that question is "may this account act inside a facility?"
     * and its answer rightly includes receptionists, cashiers, records
     * officers and insurance reviewers. This question is "would a patient
     * looking for care be seen by this person?", and the answer there is
     * doctors, nurses and midwives.
     *
     * Pharmacists, lab technicians and sample collectors are clinical but work
     * a counter or a bench rather than a consultation, so they are not listed
     * as findable clinicians; students and interns are supervised trainees and
     * are not published either. Both sets are still perfectly able to sign in —
     * this constant governs the patient-facing DIRECTORY only.
     *
     * @var list<string>
     */
    public const CLINICAL_ROLE_KEYS = [
        'doctor',
        'specialist_doctor',
        'resident_doctor',
        'medical_supervisor',
        'nurse',
        'nurse_supervisor',
        'triage_nurse',
        'ward_nurse',
        'midwife',
    ];

    /**
     * `roles.dashboard_profile_key` → `professional_licenses.profession`.
     *
     * A clinician known only by their role has no licence row to read a
     * profession off, so the role supplies it — using the SAME vocabulary the
     * licence column uses, because the client maps that vocabulary to a label
     * (app/doctors.tsx PROFESSION_KEYS). Anything not in this map is reported
     * as `null`, never guessed.
     *
     * @var array<string, string>
     */
    private const PROFESSION_FOR_ROLE = [
        'doctor'             => 'doctor',
        'specialist_doctor'  => 'doctor',
        'resident_doctor'    => 'doctor',
        'medical_supervisor' => 'doctor',
        'nurse'              => 'nurse',
        'nurse_supervisor'   => 'nurse',
        'triage_nurse'       => 'nurse',
        'ward_nurse'         => 'nurse',
        'midwife'            => 'midwife',
    ];

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
     * Only 468 of 2 728 active directory listings carry a `facility_id`. An
     * empty `data` array therefore meant three very different things and the
     * app could not tell them apart, so it showed the same dead end for all
     * three. `roster_status`, `connected` and `open_slot_count` split them,
     * and `message` states the reason in the caller's language. None of it
     * invents a clinician — an empty roster is still reported as empty.
     *
     * ── Where the roster comes from ───────────────────────────────────────
     * Both halves of the shared definition above: the HR records
     * (`staff_profiles`), which carry a job title, a department and licences,
     * and the clinicians known only by their POSTING — a clinical role plus
     * `users.primary_facility_id` or an active `facility_role_assignments`
     * row. The second half is how a clinician who can sign in and prescribe
     * here finally appears here too. Deduplicated by user id: a person with
     * both records is one clinician, described by the richer one.
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

        // Clinicians whose only record of working here is the posting itself.
        // `->diff()` is the deduplication: an HR row already describes them
        // better than a role name can, so it wins.
        $postedOnly = User::with('role')
            ->whereIn(
                'id',
                collect(self::clinicianUserIdsByFacility([$careFacility->facility_id])[$careFacility->facility_id] ?? [])
                    ->diff($profiles->pluck('user_id'))
                    ->values()
                    ->all(),
            )
            ->orderBy('name')
            ->get();

        $credentials = $this->activeCredentialsFor(
            $profiles->pluck('user_id')->merge($postedOnly->pluck('id'))->all()
        );

        $openSlotCount = $this->openSlotCountFor($careFacility->facility_id);
        $providerCount = $profiles->count() + $postedOnly->count();
        $rosterStatus  = $providerCount > 0 ? self::ROSTER_PUBLISHED : self::ROSTER_NONE_PUBLISHED;

        return response()->json([
            'facility_id'      => $careFacility->facility_id,
            'care_facility_id' => $careFacility->id,
            'facility_name'    => $careFacility->facility_name,
            'connected'        => true,
            'roster_status'    => $rosterStatus,
            'provider_count'   => $providerCount,
            // A slot belongs to a facility, not to a name. When the roster is
            // empty but the calendar is not, booking still works — so say so
            // instead of leaving the patient on a dead screen.
            'open_slot_count'  => $openSlotCount,
            'message'          => $this->rosterMessage($rosterStatus, $providerCount, $openSlotCount),
            'data'             => $profiles
                ->map(fn (StaffProfile $p) => $this->formatProvider(
                    $p,
                    $careFacility,
                    $credentials->get($p->user_id, collect())->all(),
                ))
                ->concat($postedOnly->map(fn (User $u) => $this->formatPostedClinician(
                    $u,
                    $careFacility,
                    $credentials->get($u->getKey(), collect())->all(),
                )))
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
     * through the same clinician + active-listing filter the roster uses
     * rather than `User::findOrFail()`, so probing arbitrary user UUIDs cannot
     * enumerate non-clinical accounts: anything that is not a listed clinician
     * is a 404. A clinician known only by their posting resolves here for the
     * same reason they are listed there — otherwise every row the roster
     * gained would open onto a 404.
     */
    public function show(Request $request, string $providerId): JsonResponse
    {
        // staff_profiles.user_id is UNIQUE — at most one profile per user.
        $profile = StaffProfile::where('user_id', $providerId)
            ->where('status', 'active')
            ->where('staff_category', self::CLINICAL_CATEGORY)
            ->with(['licenses'])
            ->first();

        // Every facility this person is a listed clinician at, in the order
        // the roster would have listed them. `show()` describes ONE posting,
        // so the first listed one is the one described — the roster row the
        // patient tapped always resolves, and an unlisted account never does.
        $postings = $profile
            ? [$profile->facility_id]
            : $this->clinicianFacilityIdsFor($providerId);

        $careFacility = CareFacility::whereIn('facility_id', array_filter($postings))
            ->where('listing_status', 'active')
            ->orderBy('facility_name')
            ->first();

        $postedUser = $profile ? null : User::with('role')->find($providerId);

        if (! $careFacility || (! $profile && ! $postedUser)) {
            return response()->json([
                'error_code' => 'PROVIDER_NOT_FOUND',
                'message'    => 'No listed clinician matches that id.',
            ], 404);
        }

        $credentials = $this->activeCredentialsFor([$providerId])
            ->get($providerId, collect())
            ->all();

        $provider = $profile
            ? $this->formatProvider($profile, $careFacility, $credentials)
            : $this->formatPostedClinician($postedUser, $careFacility, $credentials);

        // Next open slots for THIS clinician — same query shape as
        // MobileFacilityController::slots(), narrowed by provider_id.
        $slots = AppointmentSlot::where('facility_id', $careFacility->facility_id)
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
    // The shared clinician definition
    //
    // MobileFacilityController calls into these so that "3 clinicians" on a
    // directory row and the three names on the roster screen are computed from
    // ONE predicate. A badge can never promise a roster the next screen does
    // not show — the class docblock has always claimed that; now it is
    // structural rather than a duplicated WHERE clause.
    // -------------------------------------------------------------------------

    /**
     * The internal facility ids one user is a listed clinician at.
     *
     * The mirror image of clinicianUserIdsByFacility(): same three sources,
     * same predicates, asked from the other end. `show()` uses it so a
     * clinician who appears on a roster can always be opened.
     *
     * @return list<string>
     */
    private function clinicianFacilityIdsFor(string $userId): array
    {
        $ids = self::staffProfileClinicians(DB::query())
            ->where('staff_profiles.user_id', $userId)
            ->pluck('staff_profiles.facility_id')
            ->merge(
                self::primaryPostingClinicians(DB::query())
                    ->where('users.id', $userId)
                    ->pluck('users.primary_facility_id')
            )
            ->merge(
                self::assignedPostingClinicians(DB::query())
                    ->where('facility_role_assignments.user_id', $userId)
                    ->pluck('facility_role_assignments.facility_id')
            );

        return $ids->filter()->map(fn ($id) => (string) $id)->unique()->values()->all();
    }

    /**
     * `WHERE this row's facility has at least one clinician`.
     *
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $facilityIdColumn  fully-qualified column on the OUTER query
     *                                    holding the internal `facilities` id
     */
    public static function whereHasClinicians($query, string $facilityIdColumn): void
    {
        $query->where(function ($outer) use ($facilityIdColumn) {
            $outer
                ->whereExists(fn ($sub) => self::staffProfileClinicians($sub)
                    ->whereColumn('staff_profiles.facility_id', $facilityIdColumn))
                ->orWhereExists(fn ($sub) => self::primaryPostingClinicians($sub)
                    ->whereColumn('users.primary_facility_id', $facilityIdColumn))
                ->orWhereExists(fn ($sub) => self::assignedPostingClinicians($sub)
                    ->whereColumn('facility_role_assignments.facility_id', $facilityIdColumn));
        });
    }

    /**
     * Distinct clinician user ids per internal facility id.
     *
     * Three plain queries merged in PHP rather than one UNION subquery: the
     * counts are wanted per facility AND deduplicated by person (a clinician
     * with both an HR record and a role assignment is one clinician), and a
     * set union in PHP says that far more legibly than SQL does.
     *
     * @param  array<int, string>  $internalFacilityIds
     * @return array<string, list<string>>
     */
    public static function clinicianUserIdsByFacility(array $internalFacilityIds): array
    {
        if ($internalFacilityIds === []) {
            return [];
        }

        /** @var array<string, array<string, true>> $byFacility */
        $byFacility = [];

        $collect = function ($rows) use (&$byFacility): void {
            foreach ($rows as $row) {
                if ($row->facility_id === null || $row->user_id === null) {
                    continue;
                }
                $byFacility[(string) $row->facility_id][(string) $row->user_id] = true;
            }
        };

        $collect(
            self::staffProfileClinicians(DB::query())
                ->whereIn('staff_profiles.facility_id', $internalFacilityIds)
                ->get(['staff_profiles.facility_id', 'staff_profiles.user_id'])
        );

        $collect(
            self::primaryPostingClinicians(DB::query())
                ->whereIn('users.primary_facility_id', $internalFacilityIds)
                ->get([DB::raw('users.primary_facility_id as facility_id'), DB::raw('users.id as user_id')])
        );

        $collect(
            self::assignedPostingClinicians(DB::query())
                ->whereIn('facility_role_assignments.facility_id', $internalFacilityIds)
                ->get(['facility_role_assignments.facility_id', 'facility_role_assignments.user_id'])
        );

        return array_map(
            static fn (array $ids) => array_keys($ids),
            $byFacility,
        );
    }

    /**
     * Clinicians recorded by the HR table: an active, clinical `staff_profiles`
     * row with a user behind it (a provider is only addressable if
     * `appointment_slots.provider_id` can point at them).
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Query\Builder
     */
    private static function staffProfileClinicians($query)
    {
        return $query->from('staff_profiles')
            ->where('staff_profiles.status', 'active')
            ->where('staff_profiles.staff_category', self::CLINICAL_CATEGORY)
            ->whereNotNull('staff_profiles.user_id');
    }

    /**
     * Clinicians recorded by `users.primary_facility_id` — the single-facility
     * posting the seeders and the portal both write, and the one that makes
     * `provider/login` work for accounts with no HR row at all.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Query\Builder
     */
    private static function primaryPostingClinicians($query)
    {
        return $query->from('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('users.status', 'active')
            ->whereIn('roles.dashboard_profile_key', self::CLINICAL_ROLE_KEYS);
    }

    /**
     * Clinicians recorded by an active `facility_role_assignments` row — the
     * modelled posting, and the only one that can describe a visiting
     * consultant at a SECOND facility. Same active/expiry predicate as
     * FacilityRoleAssignment::scopeActive(), so a posting that lets someone
     * sign in is exactly a posting that lists them.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Query\Builder
     */
    private static function assignedPostingClinicians($query)
    {
        return $query->from('facility_role_assignments')
            ->join('roles', 'roles.id', '=', 'facility_role_assignments.role_id')
            ->join('users', 'users.id', '=', 'facility_role_assignments.user_id')
            ->where('facility_role_assignments.is_active', true)
            ->where('users.status', 'active')
            ->whereIn('roles.dashboard_profile_key', self::CLINICAL_ROLE_KEYS)
            ->where(fn ($w) => $w
                ->whereNull('facility_role_assignments.expires_at')
                ->orWhere('facility_role_assignments.expires_at', '>', now()));
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

    /**
     * Directory shape for a clinician known only by their POSTING.
     *
     * The same contract as formatProvider() — same keys, same `id` (users.id),
     * so the client cannot tell the two apart and does not have to. What it
     * CANNOT be is richer than the record behind it: there is no HR row here,
     * so `job_title`, `department` and `employment_type` are null rather than
     * invented, `staff_profile_id` says plainly that there is no profile, and
     * `licenses` is empty because professional_licenses hangs off a staff
     * profile and this person has none.
     *
     * `profession` is the one derived field, and it is derived from the ROLE
     * their posting carries — the same fact that made them a clinician in the
     * first place — into the same vocabulary a licence would have used.
     *
     * @param  array<int, ProviderCredential>  $credentials
     * @return array<string, mixed>
     */
    private function formatPostedClinician(User $user, CareFacility $careFacility, array $credentials): array
    {
        $roleKey = $user->role?->dashboard_profile_key;

        return [
            'id'               => $user->getKey(),
            'staff_profile_id' => null,
            'name'             => $user->name,
            'job_title'        => null,
            'department'       => null,
            'profession'       => self::PROFESSION_FOR_ROLE[$roleKey] ?? null,
            'employment_type'  => null,
            'facility_id'      => $careFacility->facility_id,
            'care_facility_id' => $careFacility->id,
            'facility_name'    => $careFacility->facility_name,
            'city'             => $careFacility->city,
            'credentials'      => array_values(array_map(fn (ProviderCredential $c) => [
                'type'         => $c->credential_type,
                'issuing_body' => $c->issuing_body,
            ], $credentials)),
            'licenses'         => [],
        ];
    }
}

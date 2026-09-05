<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\PatientReferralRequestStatus;
use App\Http\Controllers\Api\Mobile\Concerns\InteractsWithPatientWrites;
use App\Http\Controllers\Controller;
use App\Models\CareFacility;
use App\Models\PatientReferralRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Mobile Patient API — asking to be referred.
 *
 * Companion to MobileReferralController, which is read-only over
 * `referral_cases` (the referrals a CLINICIAN issued). That controller is
 * untouched. This one owns `patient_referral_requests` and writes nowhere else:
 * it cannot create a `referral_cases` row, and its model does not make `status`
 * or `referral_case_id` fillable, so nothing here can turn a request into a
 * referral.
 *
 * ── The one rule this file exists to enforce ────────────────────────────────
 *
 * A referral request is a REQUEST. It must not appear as an issued referral
 * until a clinician acts on it. Concretely, in every response:
 *
 *   - the object's `object` field is literally `"referral_request"`;
 *   - `is_referral` is `false` until a clinician has issued one, and only then
 *     does `referral_case_id` become non-null;
 *   - the statuses avoid every word that reads as "you have been referred" —
 *     there is no `approved` and no `sent`;
 *   - `reported_urgency` is named and labelled as the PATIENT's own sense of
 *     urgency, never clinical triage.
 *
 * ── Isolation ───────────────────────────────────────────────────────────────
 *
 * `patient_id` comes only from `$request->attributes`, set by
 * AuthenticateMobilePatient from the bearer token. No validator here declares a
 * `patient_id` key, so one supplied in the body is never read.
 */
class PatientReferralRequestController extends Controller
{
    use InteractsWithPatientWrites;

    /**
     * Suggested specialties, as stable slugs. Stored as submitted; localised on
     * the way out only when the stored value matches one of these, so a
     * patient's own words are never rewritten into a list they did not choose.
     *
     * This is a SUGGESTION list, not an allow-list: a patient may type a need
     * this platform has not enumerated, and refusing that would be refusing the
     * need.
     */
    private const SUGGESTED_SPECIALTIES = [
        'cardiology', 'dermatology', 'ent', 'gastroenterology', 'general_surgery',
        'gynaecology', 'internal_medicine', 'neurology', 'oncology', 'ophthalmology',
        'orthopaedics', 'paediatrics', 'psychiatry', 'pulmonology', 'urology',
        'dentistry', 'nutrition', 'physiotherapy', 'other',
    ];

    private const SYMPTOM_DURATIONS = [
        'less_than_week', 'one_to_four_weeks', 'one_to_six_months', 'more_than_six_months', 'ongoing',
    ];

    /**
     * How the PATIENT rates it. Deliberately not `referral_cases.urgency`'s
     * routine/urgent/emergency — a patient's sense of urgency and a clinician's
     * triage are different things and must never be read as the same field.
     */
    private const REPORTED_URGENCIES = ['routine', 'soon', 'urgent'];

    /** A patient may have this many requests awaiting a clinician at once. */
    private const MAX_OPEN_REQUESTS = 3;

    private const REASON_MIN = 10;

    private const REASON_MAX = 2000;

    // ── Options ──────────────────────────────────────────────────────────────

    /**
     * GET /api/mobile/referral-requests/options
     *
     * The suggestion lists and the limits, localised. The client renders these
     * rather than hardcoding a second copy that can drift from the server's.
     */
    public function options(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);

        return response()->json([
            'data' => [
                'specialties' => array_map(fn (string $slug) => [
                    'value' => $slug,
                    'label' => $this->say("patient_writes.specialties.{$slug}", [], $locale),
                ], self::SUGGESTED_SPECIALTIES),
                'durations' => array_map(fn (string $slug) => [
                    'value' => $slug,
                    'label' => $this->say("patient_writes.durations.{$slug}", [], $locale),
                ], self::SYMPTOM_DURATIONS),
                'reported_urgencies' => array_map(fn (string $slug) => [
                    'value'       => $slug,
                    'label'       => $this->say("patient_writes.referral_request.reported_urgency.{$slug}.label", [], $locale),
                    'description' => $this->say("patient_writes.referral_request.reported_urgency.{$slug}.description", [], $locale),
                ], self::REPORTED_URGENCIES),
                'limits' => [
                    'reason_min'        => self::REASON_MIN,
                    'reason_max'        => self::REASON_MAX,
                    'max_open_requests' => self::MAX_OPEN_REQUESTS,
                ],
                // The sentence the form must show before the patient starts.
                'disclaimer' => $this->say('patient_writes.referral_request.disclaimer', [], $locale),
            ],
        ]);
    }

    // ── Read ─────────────────────────────────────────────────────────────────

    /**
     * GET /api/mobile/referral-requests
     *
     * The patient's own requests, newest first. These are requests — they are
     * NOT in the list served by GET /mobile/referrals, which is issued
     * referrals only, and the client shows them in a separate section that says
     * so.
     */
    public function index(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $locale = $this->resolveLocale($request);

        $requests = PatientReferralRequest::query()
            ->forPatient($patientId)
            ->with(['preferredCareFacility:id,facility_name,city,region'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $requests->map(fn (PatientReferralRequest $r) => $this->payload($r, $locale))->all(),
            'meta' => [
                'count'             => $requests->count(),
                'open_count'        => $requests->filter(fn ($r) => $r->status->isOpen())->count(),
                'max_open_requests' => self::MAX_OPEN_REQUESTS,
                'disclaimer'        => $this->say('patient_writes.referral_request.disclaimer', [], $locale),
            ],
        ]);
    }

    // ── Write ────────────────────────────────────────────────────────────────

    /**
     * POST /api/mobile/referral-requests
     *
     * File a request. This creates NOTHING in `referral_cases`; a clinician
     * must act on it before a referral exists, and the response says so in the
     * `next_step` field rather than leaving the patient to infer it.
     */
    public function store(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $locale = $this->resolveLocale($request);

        $validator = Validator::make($request->all(), [
            'requested_specialty'        => ['nullable', 'string', 'max:120'],
            'preferred_care_facility_id' => ['nullable', 'uuid'],
            'reason'                     => ['required', 'string', 'min:' . self::REASON_MIN, 'max:' . self::REASON_MAX],
            'symptom_duration'           => ['nullable', 'string', 'in:' . implode(',', self::SYMPTOM_DURATIONS)],
            'reported_urgency'           => ['nullable', 'string', 'in:' . implode(',', self::REPORTED_URGENCIES)],
            'patient_note'               => ['nullable', 'string', 'max:1000'],
            'contact_phone'              => ['nullable', 'string', 'max:32'],
        ]);

        if ($validator->fails()) {
            return $this->refuse(
                $this->say('patient_writes.errors.request_rejected', [], $locale),
                'INVALID_REFERRAL_REQUEST',
                $this->rejections($validator->errors()->toArray()),
            );
        }

        $validated = $validator->validated();

        // Somebody has to be able to read this. A patient with three unanswered
        // requests does not need a fourth; they need an answer to the first.
        $open = PatientReferralRequest::query()->forPatient($patientId)->open()->get();

        if ($open->count() >= self::MAX_OPEN_REQUESTS) {
            return response()->json([
                'message'    => $this->say('patient_writes.errors.too_many_open_requests', [
                    'max' => self::MAX_OPEN_REQUESTS,
                ], $locale),
                'error_code' => 'TOO_MANY_OPEN_REQUESTS',
                'data'       => $open->map(fn ($r) => $this->payload($r, $locale))->all(),
            ], 409);
        }

        $specialty = $this->normaliseSpecialty($validated['requested_specialty'] ?? null);

        // A second request for the same thing is not more information — it is
        // the same request twice in a clinician's queue.
        $duplicate = $specialty === null
            ? null
            : $open->first(fn ($r) => $r->requested_specialty === $specialty);

        if ($duplicate !== null) {
            return response()->json([
                'message'    => $this->say('patient_writes.errors.duplicate_open_request', [
                    'specialty' => $this->specialtyLabel($specialty, $locale),
                    'reference' => $duplicate->reference,
                ], $locale),
                'error_code' => 'REQUEST_ALREADY_OPEN',
                'data'       => $this->payload($duplicate, $locale),
            ], 409);
        }

        // A preference, if given, must point at a facility that is actually
        // listed. A dangling preference would send a clinician looking for a
        // facility the directory does not carry.
        $facilityId = $validated['preferred_care_facility_id'] ?? null;

        if ($facilityId !== null) {
            $exists = CareFacility::query()
                ->where('listing_status', 'active')
                ->whereKey($facilityId)
                ->exists();

            if (! $exists) {
                return $this->refuse(
                    $this->say('patient_writes.errors.request_rejected', [], $locale),
                    'FACILITY_NOT_FOUND',
                    [[
                        'field'       => 'preferred_care_facility_id',
                        'submitted'   => (string) $facilityId,
                        'reason_code' => 'facility_not_found',
                        'reason'      => $this->say('patient_writes.errors.facility_not_found', [], $locale),
                    ]],
                    404,
                );
            }
        }

        // `status` is not fillable and is not set here: the row is born
        // `submitted` by the column default, and no patient-side path can move
        // it to `accepted`.
        $referralRequest = PatientReferralRequest::create([
            'reference'                  => PatientReferralRequest::newReference(),
            'patient_id'                 => $patientId,
            'requested_specialty'        => $specialty,
            'preferred_care_facility_id' => $facilityId,
            'reason'                     => trim((string) $validated['reason']),
            'symptom_duration'           => $validated['symptom_duration'] ?? null,
            'reported_urgency'           => $validated['reported_urgency'] ?? 'routine',
            'patient_note'               => $validated['patient_note'] ?? null,
            'contact_phone'              => $validated['contact_phone'] ?? null,
        ]);

        $this->audit(
            $request,
            'patient.referral_request.submitted',
            'patient_referral_request',
            $referralRequest->id,
            [
                'reference'        => $referralRequest->reference,
                'specialty'        => $specialty,
                'reported_urgency' => $referralRequest->reported_urgency,
                'status'           => PatientReferralRequestStatus::Submitted->value,
            ],
        );

        return response()->json([
            'data' => $this->payload($referralRequest->refresh(), $locale),
        ], 201);
    }

    /**
     * POST /api/mobile/referral-requests/{id}/withdraw
     *
     * The patient takes their own request back. Never a delete: a facility may
     * already have read it, and erasing that is not the patient's to do.
     */
    public function withdraw(Request $request, string $id): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $locale = $this->resolveLocale($request);

        $validator = Validator::make($request->all(), [
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->refuse(
                $this->say('patient_writes.errors.request_rejected', [], $locale),
                'INVALID_REASON',
                $this->rejections($validator->errors()->toArray()),
            );
        }

        // Scoped in the WHERE clause: another patient's id does not exist as
        // far as this query is concerned.
        $referralRequest = PatientReferralRequest::query()
            ->forPatient($patientId)
            ->find($id);

        if ($referralRequest === null) {
            return response()->json([
                'message'    => $this->say('patient_writes.errors.request_not_found', [], $locale),
                'error_code' => 'REQUEST_NOT_FOUND',
            ], 404);
        }

        if (! $referralRequest->status->isWithdrawable()) {
            return response()->json([
                'message'    => $this->say('patient_writes.errors.not_withdrawable', [
                    'status' => $this->say($referralRequest->status->labelKey(), [], $locale),
                ], $locale),
                'error_code' => 'NOT_WITHDRAWABLE',
                'data'       => $this->payload($referralRequest, $locale),
            ], 409);
        }

        $reason = $request->input('reason') ?: null;
        $referralRequest->withdraw($reason);

        $this->audit(
            $request,
            'patient.referral_request.withdrawn',
            'patient_referral_request',
            $referralRequest->id,
            ['status' => PatientReferralRequestStatus::Withdrawn->value],
            $reason,
        );

        return response()->json(['data' => $this->payload($referralRequest->refresh(), $locale)]);
    }

    // ── Shaping ──────────────────────────────────────────────────────────────

    /**
     * One request, as the client sees it.
     *
     * `object`, `is_referral` and `referral_case_id` together make it
     * impossible to render this as an issued referral by accident: the type is
     * stated, the question is answered, and the referral's id is null until
     * there is one.
     *
     * @return array<string, mixed>
     */
    private function payload(PatientReferralRequest $r, string $locale): array
    {
        $status = $r->status;

        return [
            // Not a referral. Said in the payload, not just in the URL.
            'object'      => 'referral_request',
            'id'          => $r->id,
            'reference'   => $r->reference,

            'status'        => $status->value,
            'status_label'  => $this->say($status->labelKey(), [], $locale),
            'status_detail' => $this->say('patient_writes.referral_request.status_detail.' . $status->value, [], $locale),
            'is_open'       => $status->isOpen(),
            'is_withdrawable' => $status->isWithdrawable(),

            // The only two fields that may ever say "there is a referral".
            'is_referral'       => $status->hasIssuedReferral(),
            'referral_case_id'  => $status->hasIssuedReferral() ? $r->referral_case_id : null,

            'requested_specialty'       => $r->requested_specialty,
            'requested_specialty_label' => $this->specialtyLabel($r->requested_specialty, $locale),

            'preferred_facility' => $r->preferredCareFacility === null ? null : [
                'id'     => $r->preferredCareFacility->id,
                'name'   => $r->preferredCareFacility->facility_name,
                'city'   => $r->preferredCareFacility->city,
                'region' => $r->preferredCareFacility->region,
            ],

            'reason'                 => $r->reason,
            'symptom_duration'       => $r->symptom_duration,
            'symptom_duration_label' => $r->symptom_duration
                ? $this->say("patient_writes.durations.{$r->symptom_duration}", [], $locale)
                : null,

            // Labelled at every turn as the patient's own reading of it.
            'reported_urgency'       => $r->reported_urgency,
            'reported_urgency_label' => $this->say(
                "patient_writes.referral_request.reported_urgency.{$r->reported_urgency}.label",
                [],
                $locale,
            ),
            'reported_urgency_caveat' => $this->say('patient_writes.referral_request.urgency_caveat', [], $locale),

            'patient_note'  => $r->patient_note,
            'contact_phone' => $r->contact_phone,

            'submitted_at'   => $r->created_at?->toIso8601String(),
            'reviewed_at'    => $r->reviewed_at?->toIso8601String(),
            'review_note'    => $r->review_note,
            'decline_reason' => $r->decline_reason,
            'withdrawn_at'   => $r->withdrawn_at?->toIso8601String(),

            // What actually happens next, so the screen never has to guess.
            'next_step' => $this->say('patient_writes.referral_request.next_step.' . $status->value, [], $locale),
        ];
    }

    /**
     * Store what the patient submitted. Trim it, cap it, and leave the words
     * alone — a suggestion slug comes back as the slug, anything else comes
     * back verbatim.
     */
    private function normaliseSpecialty(?string $raw): ?string
    {
        $value = trim((string) $raw);

        if ($value === '') {
            return null;
        }

        $slug = strtolower(str_replace([' ', '-'], '_', $value));

        return in_array($slug, self::SUGGESTED_SPECIALTIES, true) ? $slug : $value;
    }

    /**
     * Localise a stored specialty when it is one of ours; otherwise hand back
     * the patient's own words untouched. Never invents a translation for text
     * a patient typed.
     */
    private function specialtyLabel(?string $stored, string $locale): ?string
    {
        if ($stored === null || $stored === '') {
            return null;
        }

        return in_array($stored, self::SUGGESTED_SPECIALTIES, true)
            ? $this->say("patient_writes.specialties.{$stored}", [], $locale)
            : $stored;
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     * @return array<int, array<string, mixed>>
     */
    private function rejections(array $errors): array
    {
        $rejected = [];

        foreach ($errors as $field => $messages) {
            $rejected[] = [
                'field'       => $field,
                'reason_code' => 'invalid',
                'reason'      => (string) ($messages[0] ?? ''),
            ];
        }

        return $rejected;
    }
}

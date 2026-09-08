<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Services\Preconsult\PreconsultQuestionnaireService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Mobile Patient API — the pre-consult questionnaire.
 *
 * A short clinical intake the patient fills in BEFORE an appointment, so the
 * consultation opens with the history already taken.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * ISOLATION
 * ─────────────────────────────────────────────────────────────────────────
 *
 * `patient_id` comes ONLY from `$request->attributes`, stamped by
 * AuthenticateMobilePatient from the bearer token — and swapped to a dependent
 * by ActAsDependent when a guardian presents a valid FamilyLink, which is why
 * this controller needs no family logic of its own. There is no patient
 * identifier in any path, query or body here.
 *
 * `facility_id` is NEVER read from the request at all. It is copied off the
 * appointment row by the service.
 *
 * An appointment that is not the caller's is a **404**, identical to one that
 * does not exist: nothing here tells a caller whether someone else's booking
 * is real. (403 is reserved for a role refusal, and this surface has none.)
 *
 * ─────────────────────────────────────────────────────────────────────────
 * NOTHING HERE CHANGES AN APPOINTMENT
 * ─────────────────────────────────────────────────────────────────────────
 *
 * This controller reads appointments and writes questionnaires. It never
 * writes to `appointments`, and a patient who never opens this surface is seen
 * on exactly the same terms as one who completes it. See
 * tests/Feature/Architecture/CareIsNeverGatedOnPreconsultTest.php.
 *
 * Nothing written here is a clinical finding. Answers land only in
 * `preconsult_answers`, provenance `patient_reported`.
 */
class MobilePreconsultController extends Controller
{
    public function __construct(private readonly PreconsultQuestionnaireService $service)
    {
    }

    /**
     * GET /api/mobile/preconsult/appointments/{appointment}
     *
     * The questionnaire for this appointment, plus whatever the patient has
     * already answered. Returns the definition even when the window is closed,
     * with `window.open = false` — the client shows a read-only form rather
     * than an error the patient cannot act on.
     */
    public function show(Request $request, string $appointment): JsonResponse
    {
        $this->applyRequestLocale($request);

        $patientId   = $this->patientId($request);
        $appointmentModel = $this->service->appointmentForPatient($patientId, $appointment);

        if ($appointmentModel === null) {
            return $this->notFound();
        }

        $existing = $this->service->existing($appointmentModel);

        return response()->json([
            'data' => [
                'appointment' => [
                    'id'           => $appointmentModel->id,
                    'scheduled_at' => optional($appointmentModel->scheduled_at)->toIso8601String(),
                    'status'       => $appointmentModel->status,
                ],
                'window'        => $this->window($appointmentModel),
                'questionnaire' => $this->service->renderedDefinition(),
                // Null, not an empty submission: nothing has been started yet
                // and the payload says so rather than implying a blank form
                // was filled in.
                'submission' => $existing !== null ? $this->service->present($existing) : null,
            ],
        ]);
    }

    /**
     * PUT /api/mobile/preconsult/appointments/{appointment}/draft
     *
     * Save (or start) a draft. Every question is optional, so an empty
     * `answers` object is a valid request; it starts the draft and stores
     * nothing.
     */
    public function saveDraft(Request $request, string $appointment): JsonResponse
    {
        $this->applyRequestLocale($request);

        $patientId        = $this->patientId($request);
        $appointmentModel = $this->service->appointmentForPatient($patientId, $appointment);

        if ($appointmentModel === null) {
            return $this->notFound();
        }

        $window = $this->service->window($appointmentModel);

        if (! $window['open']) {
            return $this->windowClosed($window);
        }

        $validated = $request->validate([
            'answers'   => ['sometimes', 'array'],
            'answers.*' => ['nullable', 'string', 'max:' . $this->service->maxTextLength()],
        ]);

        $submission = $this->service->startOrResume($appointmentModel, $this->actingUserId($request));

        if (! $submission->status->isEditable()) {
            return response()->json([
                'message'    => __('preconsult.errors.already_submitted'),
                'error_code' => 'preconsult_already_submitted',
                'data'       => $this->service->present($submission),
            ], 409);
        }

        $submission = $this->service->saveAnswers($submission, (array) ($validated['answers'] ?? []));

        return response()->json([
            'message' => __('preconsult.saved'),
            'data'    => $this->service->present($submission),
        ]);
    }

    /**
     * POST /api/mobile/preconsult/appointments/{appointment}/submit
     *
     * Hand it to the facility. Accepts a final set of answers in the same
     * request so a patient on a bad connection is not forced to make two.
     *
     * Submitting an empty questionnaire is allowed and is not an error. A form
     * that refuses to be sent half-filled is a form that teaches patients to
     * invent answers.
     */
    public function submit(Request $request, string $appointment): JsonResponse
    {
        $this->applyRequestLocale($request);

        $patientId        = $this->patientId($request);
        $appointmentModel = $this->service->appointmentForPatient($patientId, $appointment);

        if ($appointmentModel === null) {
            return $this->notFound();
        }

        $window = $this->service->window($appointmentModel);

        if (! $window['open']) {
            return $this->windowClosed($window);
        }

        $validated = $request->validate([
            'answers'   => ['sometimes', 'array'],
            'answers.*' => ['nullable', 'string', 'max:' . $this->service->maxTextLength()],
        ]);

        $submission = $this->service->startOrResume($appointmentModel, $this->actingUserId($request));

        if ($submission->status->isEditable()) {
            $submission = $this->service->saveAnswers($submission, (array) ($validated['answers'] ?? []));
            $submission = $this->service->submit($submission, $this->actingUserId($request));
        }

        return response()->json([
            'message' => __('preconsult.submitted'),
            'data'    => $this->service->present($submission),
        ]);
    }

    // ── Internals ────────────────────────────────────────────────────────────

    /** @return array<string,mixed> */
    private function window(\App\Models\Appointment $appointment): array
    {
        $window = $this->service->window($appointment);

        return [
            'open'    => $window['open'],
            'reason'  => $window['reason'],
            'message' => $window['reason'] === null
                ? null
                : __('preconsult.window.' . $window['reason']),
        ];
    }

    /**
     * @param  array{open:bool,reason:?string}  $window
     */
    private function windowClosed(array $window): JsonResponse
    {
        // 409, never 403: the patient is not forbidden anything, and this
        // refusal touches only the form. Their appointment is unaffected.
        return response()->json([
            'message'    => __('preconsult.window.' . ($window['reason'] ?? 'closed')),
            'error_code' => 'preconsult_window_closed',
            'reason'     => $window['reason'],
        ], 409);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['message' => __('preconsult.errors.appointment_not_found')], 404);
    }

    /** The authenticated patient — or the dependent a guardian is acting for. */
    private function patientId(Request $request): string
    {
        $id = $request->attributes->get('patient_id');

        abort_if(! is_string($id) || $id === '', 401, 'Unauthenticated.');

        return $id;
    }

    /**
     * Who is actually filling it in. Under an act-as-dependent request this
     * stays the GUARDIAN's user id, so the record says who spoke.
     */
    private function actingUserId(Request $request): ?string
    {
        $id = $request->attributes->get('patient_user_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    /** Honour `Accept-Language`; scoped to this request. */
    private function applyRequestLocale(Request $request): void
    {
        $header = (string) $request->header('Accept-Language', '');

        if ($header !== '' && str_starts_with(strtolower(ltrim($header)), 'fr')) {
            App::setLocale('fr');
        }
    }
}

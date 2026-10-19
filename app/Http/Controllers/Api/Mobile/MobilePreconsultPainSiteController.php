<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\PainSiteSource;
use App\Http\Controllers\Controller;
use App\Models\PatientPainSite;
use App\Services\Chart\PainSiteService;
use App\Services\Preconsult\PreconsultQuestionnaireService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Mobile Patient API: "where does it hurt", at pre-consult check-in.
 *
 *   GET  /api/mobile/preconsult/appointments/{appointment}/pain-sites
 *   POST /api/mobile/preconsult/appointments/{appointment}/pain-sites
 *
 * Same isolation as MobilePreconsultController, on the same group:
 * `patient_id` comes ONLY from the request attributes (AuthenticateMobilePatient,
 * swapped to a dependent by ActAsDependent for a guardian), the appointment is
 * resolved scoped to that patient (someone else's is a 404), and the facility
 * is copied off the appointment row, never read from the request.
 *
 * POST is accepted while the pre-consult window is open (409 otherwise, as the
 * questionnaire answers); each site lands in `patient_pain_sites` with source
 * `patient_checkin`, linked to the appointment, recorded by the acting user
 * (the guardian's own user id when a guardian speaks). Nothing here touches
 * the appointment, and nothing is a clinical finding: the chart labels these
 * sites "Patient at check-in".
 *
 * GET lists the patient's unresolved check-in sites for this appointment, so
 * the app can show what it already sent.
 */
class MobilePreconsultPainSiteController extends Controller
{
    public function __construct(
        private readonly PreconsultQuestionnaireService $preconsult,
        private readonly PainSiteService $pain,
    ) {
    }

    public function index(Request $request, string $appointment): JsonResponse
    {
        $this->applyRequestLocale($request);

        $model = $this->preconsult->appointmentForPatient($this->patientId($request), $appointment);

        if ($model === null) {
            return $this->notFound();
        }

        $sites = PatientPainSite::query()
            ->where('patient_id', $model->patient_id)
            ->where('appointment_id', $model->id)
            ->where('source', PainSiteSource::PatientCheckin->value)
            ->whereNull('resolved_at')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => $sites->map(fn (PatientPainSite $s) => $this->pain->present($s))->values(),
        ]);
    }

    public function store(Request $request, string $appointment): JsonResponse
    {
        $this->applyRequestLocale($request);

        $model = $this->preconsult->appointmentForPatient($this->patientId($request), $appointment);

        if ($model === null) {
            return $this->notFound();
        }

        $window = $this->preconsult->window($model);

        if (! $window['open']) {
            return response()->json([
                'message'    => __('preconsult.window.' . ($window['reason'] ?? 'closed')),
                'error_code' => 'preconsult_window_closed',
                'reason'     => $window['reason'],
            ], 409);
        }

        $validated = $request->validate([
            'sites' => ['required', 'array', 'min:1', 'max:' . PainSiteService::MAX_PER_SUBMISSION],
        ] + PainSiteService::rules('sites.*.'));

        $created = [];

        foreach ($validated['sites'] as $site) {
            $created[] = $this->pain->record(
                (string) $model->patient_id,
                (string) $model->facility_id,
                PainSiteSource::PatientCheckin,
                $this->actingUserId($request),
                $site,
                ['appointment_id' => $model->id],
            );
        }

        return response()->json([
            'message' => __('pain.recorded'),
            'data'    => array_map(fn (PatientPainSite $s) => $this->pain->present($s), $created),
        ], 201);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['message' => __('preconsult.errors.appointment_not_found')], 404);
    }

    private function patientId(Request $request): string
    {
        $id = $request->attributes->get('patient_id');

        abort_if(! is_string($id) || $id === '', 401, 'Unauthenticated.');

        return $id;
    }

    private function actingUserId(Request $request): ?string
    {
        $id = $request->attributes->get('patient_user_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    private function applyRequestLocale(Request $request): void
    {
        $header = (string) $request->header('Accept-Language', '');

        if ($header !== '' && str_starts_with(strtolower(ltrim($header)), 'fr')) {
            App::setLocale('fr');
        }
    }
}

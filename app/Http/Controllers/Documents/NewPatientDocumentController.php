<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\Appointment;
use App\Models\ConsentGrant;
use App\Models\ConsentRequest;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Services\Documents\NewDocumentRenderer;
use App\Services\Patient\HealthPassportBuilder;
use App\Services\Portal\PortalContextService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Patient-side documents built in the "missing documents" pass
 * (docs/specs/documents/24-26, 31, 32). The holder is the signed-in patient,
 * or the dependent a guardian has switched to (`guardian.context` puts it in
 * the `viewing_patient` request attribute) — never an id from the request.
 */
class NewPatientDocumentController extends Controller
{
    /** The access log is long-lived; the sheet carries the newest rows only. */
    private const ACCESS_LOG_LIMIT = 500;

    public function __construct(
        private readonly NewDocumentRenderer $renderer,
        private readonly PortalContextService $ctx,
    ) {
    }

    public function passport(HealthPassportBuilder $builder): Response
    {
        $patient = $this->holder('health_passport');
        $skip    = ['cover', 'back-cover'];

        $pages = collect(HealthPassportBuilder::PAGES)
            ->reject(fn (array $p) => in_array($p['id'], $skip, true))
            ->map(fn (array $p) => $builder->page($patient, $p['id']))
            ->filter()
            ->values();

        return $this->renderer->secure('documents.new.health-passport', $patient, 'health_passport', [
            'summary' => $builder->summary($patient),
            'pages'   => $pages,
        ], 'health-passport', (string) Auth::id());
    }

    public function emergency(HealthPassportBuilder $builder): Response
    {
        $patient = $this->holder('emergency_summary');

        return $this->renderer->secure('documents.new.emergency-summary', $patient, 'emergency_summary', [
            'page'    => $builder->page($patient, 'emergency-profile'),
            'contact' => is_array($patient->emergency_contact) ? $patient->emergency_contact : [],
        ], 'emergency-summary', (string) Auth::id());
    }

    public function appointment(string $id): Response
    {
        abort_unless(Str::isUuid($id), 404);
        $patient     = $this->holder('appointment_slip', $id);
        $appointment = Appointment::query()->where('patient_id', $patient->id)->find($id) ?? abort(404);

        return $this->renderer->secure('documents.new.appointment-slip', $patient, 'appointment_slip', [
            'appointment' => $appointment,
            'facility'    => $appointment->facility_id ? Facility::withoutGlobalScopes()->find($appointment->facility_id) : null,
            'provider'    => $appointment->provider_id ? User::find($appointment->provider_id) : null,
        ], 'appointment', (string) Auth::id());
    }

    public function consents(): Response
    {
        $patient = $this->holder('consent_record');

        $grants   = ConsentGrant::withoutGlobalScopes()->where('patient_id', $patient->id)->orderByDesc('created_at')->get();
        $requests = ConsentRequest::withoutGlobalScopes()->where('patient_id', $patient->id)->orderByDesc('created_at')->get();
        $facilityIds = $grants->pluck('facility_id')->merge($requests->pluck('requesting_facility_id'))->filter()->unique();

        return $this->renderer->secure('documents.new.consent-record', $patient, 'consent_record', [
            'grants'     => $grants,
            'requests'   => $requests,
            'facilities' => Facility::withoutGlobalScopes()->whereIn('id', $facilityIds)->pluck('name', 'id'),
        ], 'consent-record', (string) Auth::id());
    }

    public function accessLog(): Response
    {
        $patient = $this->holder('access_log');

        $logs = AccessLog::withoutGlobalScopes()->where('patient_id', $patient->id)
            ->orderByDesc('created_at')->limit(self::ACCESS_LOG_LIMIT)->get();

        $actorIds = $logs->pluck('actor_id')->filter(fn ($v) => Str::isUuid((string) $v) || is_numeric($v))->unique();

        return $this->renderer->secure('documents.new.access-log', $patient, 'access_log', [
            'logs'       => $logs,
            'actors'     => User::query()->whereIn('id', $actorIds)->pluck('name', 'id'),
            'facilities' => Facility::withoutGlobalScopes()->whereIn('id', $logs->pluck('facility_id')->filter()->unique())->pluck('name', 'id'),
            'limit'      => self::ACCESS_LOG_LIMIT,
        ], 'access-log', (string) Auth::id());
    }

    /** The patient this request is about; audited before anything renders. */
    private function holder(string $type, ?string $resourceId = null): Patient
    {
        $patient = request()->attributes->get('viewing_patient') ?? Auth::user()?->patient;
        abort_unless($patient instanceof Patient, 403);

        $this->ctx->auditPatientAccess(
            actionType:   'patient_document_generated',
            resourceType: 'PatientDocument:' . $type,
            resourceId:   $resourceId,
            patientId:    $patient->id,
        );

        return $patient;
    }
}

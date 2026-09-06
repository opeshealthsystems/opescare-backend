<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\Patient\PatientDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a patient document to the app, generated at the moment it is asked
 * for and never stored (see PatientDocumentService for why).
 *
 * The listing lives in MobileDocumentController::index() so the app has one
 * documents screen rather than two; this controller only hands over the file.
 *
 * It sits outside the bearer-token group on purpose: the app opens the URL in
 * the system browser, which carries no token, so the link has to authenticate
 * itself. It is signed, short-lived, and names one patient and one record.
 */
class MobilePatientDocumentController extends Controller
{
    public function __construct(private readonly PatientDocumentService $documents)
    {
    }

    /** GET /api/mobile/documents/{type}/download?patient=…&record=…&signature=… */
    public function download(Request $request, string $type): StreamedResponse
    {
        abort_unless($request->hasValidSignature(), 403, 'This download link has expired.');

        $patientId = (string) $request->query('patient');
        abort_if($patientId === '' || ! Str::isUuid($patientId), 404);

        $patient = Patient::withoutGlobalScopes()->find($patientId);
        abort_if($patient === null, 404);

        // A record id that is not a uuid is not a record; the whole-record
        // documents carry none at all.
        $record = $request->query('record');
        $record = is_string($record) && Str::isUuid($record) ? $record : null;

        return $this->documents->download($patient, $type, $record);
    }
}

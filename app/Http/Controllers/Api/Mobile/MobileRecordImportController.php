<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientImportedRecord;
use App\Services\PatientImport\ImportSummary;
use App\Services\PatientImport\PatientFhirImportService;
use App\Services\PatientImport\PatientImportRefusal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * A patient importing records from another provider into their own record.
 *
 *   POST   /mobile/records/import/fhir       multipart `file` — a FHIR R4 Bundle (.json)
 *   POST   /mobile/records/import/document   multipart `file` — PDF / JPEG / PNG / WebP, optional `title`
 *   GET    /mobile/records/imported          what the patient has imported
 *   DELETE /mobile/records/imported/{id}     remove one imported item
 *   GET    /mobile/records/imported/{id}/file  SIGNED, no bearer — the stored document
 *
 * The owner is ALWAYS $request->attributes['patient_id'] (auth.mobile). Every
 * item is provenance 'patient_import', verification 'unverified', and is
 * returned with the "patient-provided, unverified" label. Files sit on the
 * private disk and are reached only through a short-lived signed URL, as the
 * patient photo is (MobilePatientPhotoController).
 */
class MobileRecordImportController extends Controller
{
    public const MAX_KB = 10240;

    private const URL_TTL_MINUTES = 30;

    private const DOCUMENT_MIMES = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];

    private const EXT = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
    ];

    public function __construct(private readonly PatientFhirImportService $fhir) {}

    public function importFhir(Request $request): JsonResponse
    {
        $patient = $this->patient($request);
        if (! $patient) {
            return response()->json(['message' => __('api.patient_not_found')], 404);
        }

        $request->validate([
            'file' => ['required', 'file', 'max:' . self::MAX_KB, 'mimetypes:application/json,text/plain,application/fhir+json'],
        ]);

        $bundle = json_decode((string) file_get_contents($request->file('file')->getRealPath()), true, 64);
        if (! is_array($bundle)) {
            return $this->refuse('INVALID_JSON', __('patient_import.invalid_json'));
        }

        try {
            $summary = $this->fhir->import($patient, $bundle);
        } catch (PatientImportRefusal $e) {
            return $this->refuse($e->errorCode, $e->getMessage());
        }

        return response()->json(['summary' => $summary], 201);
    }

    public function importDocument(Request $request): JsonResponse
    {
        $patient = $this->patient($request);
        if (! $patient) {
            return response()->json(['message' => __('api.patient_not_found')], 404);
        }

        // `mimetypes:` reads the type out of the bytes, not the multipart header.
        $request->validate([
            'file'  => ['required', 'file', 'max:' . self::MAX_KB, 'mimetypes:' . implode(',', self::DOCUMENT_MIMES)],
            'title' => ['nullable', 'string', 'max:200'],
        ]);

        /** @var UploadedFile $file */
        $file  = $request->file('file');
        $bytes = (string) file_get_contents($file->getRealPath());
        $mime  = (string) $file->getMimeType();

        // Second look at the bytes themselves.
        $ok = $mime === 'application/pdf'
            ? str_starts_with($bytes, '%PDF-')
            : (($info = @getimagesizefromstring($bytes)) !== false && ($info['mime'] ?? null) === $mime);
        if (! $ok || ! isset(self::EXT[$mime])) {
            return $this->refuse('DOCUMENT_TYPE_REJECTED', __('patient_import.document_type_rejected'));
        }

        $hash    = hash('sha256', 'doc|' . hash('sha256', $bytes));
        $summary = new ImportSummary();
        $batchId = (string) Str::uuid();

        $existing = PatientImportedRecord::query()
            ->where('patient_id', $patient->id)->where('dedupe_hash', $hash)->first();
        if ($existing) {
            $summary->skip(PatientImportedRecord::DOCUMENT, 'duplicate');

            return response()->json(['summary' => $summary->toArray($batchId), 'record' => $this->present($existing)], 200);
        }

        $path = 'patient-imports/' . $patient->id . '/' . Str::uuid() . '.' . self::EXT[$mime];
        Storage::disk($this->disk())->put($path, $bytes);

        $original = Str::limit(basename((string) $file->getClientOriginalName()), 250, '');
        $record   = PatientImportedRecord::create([
            'patient_id'        => $patient->id,
            'import_batch_id'   => $batchId,
            'resource_type'     => PatientImportedRecord::DOCUMENT,
            'dedupe_hash'       => $hash,
            'title'             => $request->input('title') ?: ($original ?: null),
            'file_path'         => $path,
            'file_mime'         => $mime,
            'file_size'         => strlen($bytes),
            'original_filename' => $original ?: null,
        ]);
        $summary->import(PatientImportedRecord::DOCUMENT);

        return response()->json(['summary' => $summary->toArray($batchId), 'record' => $this->present($record)], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $patient = $this->patient($request);
        if (! $patient) {
            return response()->json(['message' => __('api.patient_not_found')], 404);
        }

        $rows = PatientImportedRecord::query()
            ->where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();

        return response()->json([
            'label'   => __('patient_import.unverified_label'),
            'records' => $rows->map(fn ($r) => $this->present($r))->values(),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $patient = $this->patient($request);
        $record  = $patient && Str::isUuid($id)
            ? PatientImportedRecord::query()->where('patient_id', $patient->id)->find($id)
            : null;
        if (! $record) {
            return response()->json(['message' => __('api.not_found')], 404);
        }

        if ($record->file_path && Storage::disk($this->disk())->exists($record->file_path)) {
            Storage::disk($this->disk())->delete($record->file_path);
        }
        $record->delete();

        return response()->json(['deleted' => true]);
    }

    /** Behind `signed` — the signature is the authorisation, and it expires. */
    public function file(string $id): HttpResponse
    {
        $record = Str::isUuid($id) ? PatientImportedRecord::query()->find($id) : null;
        if (! $record || ! $record->file_path || ! Storage::disk($this->disk())->exists($record->file_path)) {
            return response()->json(['message' => __('api.not_found')], 404);
        }

        $response = Storage::disk($this->disk())->response($record->file_path, null, [
            'Content-Type'           => $record->file_mime ?: 'application/octet-stream',
            'Content-Disposition'    => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPrivate();
        $response->setMaxAge(300);

        return $response;
    }

    // ── Internals ────────────────────────────────────────────────────────────

    /** @return array<string,mixed> */
    private function present(PatientImportedRecord $r): array
    {
        return [
            'id'                     => $r->id,
            'resource_type'          => $r->resource_type,
            'title'                  => $r->title,
            'code_system'            => $r->code_system,
            'code'                   => $r->code,
            'value'                  => $r->value_text,
            'status'                 => $r->status,
            'effective_at'           => $r->effective_at?->toIso8601String(),
            'imported_at'            => $r->created_at?->toIso8601String(),
            'provenance'             => $r->provenance,
            'verification_status'    => $r->verification_status,
            'verified'               => false,
            'label'                  => __('patient_import.unverified_label'),
            'original_source_system' => $r->original_source_system,
            'original_source_name'   => $r->original_source_name,
            'file_mime'              => $r->file_mime,
            'file_size'              => $r->file_size,
            'file_url'               => $r->file_path
                ? URL::temporarySignedRoute('mobile.records.imported.file', now()->addMinutes(self::URL_TTL_MINUTES), ['id' => $r->id])
                : null,
        ];
    }

    private function refuse(string $code, string $message): JsonResponse
    {
        return response()->json(['error_code' => $code, 'message' => $message], 422);
    }

    private function disk(): string
    {
        return (string) config('filesystems.private_disk', 'local');
    }

    private function patient(Request $request): ?Patient
    {
        $patientId = $request->attributes->get('patient_id');

        return $patientId ? Patient::find($patientId) : null;
    }
}

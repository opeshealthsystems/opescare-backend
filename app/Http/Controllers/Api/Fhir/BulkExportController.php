<?php

namespace App\Http\Controllers\Api\Fhir;

use App\Http\Controllers\Controller;
use App\Models\BulkExportJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * BulkExportController
 *
 * FHIR Bulk Data Access IG STU1 — polling and download endpoints.
 *
 * Migration Sprint — Item 4.
 *
 * Implements the two required polling endpoints:
 *
 *   GET /api/fhir/R4/bulkdata/{jobId}/status
 *     - 202 + X-Progress header: job is still queued/processing
 *     - 200 + JSON body: job is complete — links to NDJSON download files
 *     - 500 + OperationOutcome: job has failed
 *
 *   GET /api/fhir/R4/bulkdata/{jobId}/download/{file}
 *     - Streams the NDJSON output file.
 *     - Returns 404 if the file does not exist or the job has expired.
 *
 * Access control: both endpoints require the same auth.bearer:system:export scope
 * as the original $export trigger, and they validate that the requesting facility
 * owns the job — preventing cross-facility data leakage.
 *
 * @see https://hl7.org/fhir/uv/bulkdata/
 */
class BulkExportController extends Controller
{
    /**
     * GET /api/fhir/R4/bulkdata/{jobId}/status
     *
     * Poll the status of an async bulk export job.
     *
     * Responses per FHIR Bulk Data IG §5.3:
     *   - 202: still running (X-Progress header with percentage)
     *   - 200: complete (body lists output file URLs)
     *   - 500: failed (OperationOutcome)
     *   - 404: job not found or not owned by requesting facility
     */
    public function status(Request $request, string $jobId): JsonResponse|\Illuminate\Http\Response
    {
        $facilityId = $this->resolveFacilityId($request);

        $job = BulkExportJob::where('id', $jobId)
            ->where('facility_id', $facilityId)
            ->first();

        if (! $job) {
            return $this->notFound();
        }

        // Expired — files are gone; treat as 404.
        if ($job->isExpired() && ! $job->isStillProcessing()) {
            if ($job->status !== 'expired') {
                $job->update(['status' => 'expired']);
            }
            return $this->notFound('Export job has expired. Re-submit the $export request.');
        }

        // Still in progress → 202
        if ($job->isStillProcessing()) {
            return response('', 202)->withHeaders([
                'X-Progress'   => $job->progress . '%',
                'Content-Type' => 'application/json',
            ]);
        }

        // Failed → 500 OperationOutcome
        if ($job->isFailed()) {
            return response()->json([
                'resourceType' => 'OperationOutcome',
                'issue'        => [[
                    'severity'    => 'error',
                    'code'        => 'processing',
                    'diagnostics' => $job->error ?? 'Export job failed.',
                ]],
            ], 500)->withHeaders(['Content-Type' => 'application/fhir+json']);
        }

        // Complete → 200 with output file manifest
        return response()->json([
            'transactionTime' => $job->completed_at?->toAtomString(),
            'request'         => url("/api/fhir/R4/\$export"),
            'requiresAccessToken' => true,
            'output'          => collect($job->output_files ?? [])->map(fn ($f) => [
                'type'  => $f['type'],
                'url'   => $f['url'],
                'count' => $f['count'] ?? null,
            ])->values()->all(),
            'error'           => [],
        ], 200)->withHeaders(['Content-Type' => 'application/fhir+json']);
    }

    /**
     * GET /api/fhir/R4/bulkdata/{jobId}/download/{file}
     *
     * Stream a completed NDJSON export file.
     *
     * The {file} segment is the basename only (e.g. "Patient.ndjson").
     * Path traversal sequences are rejected.
     */
    public function download(Request $request, string $jobId, string $file): Response
    {
        $facilityId = $this->resolveFacilityId($request);

        // Reject path traversal attempts.
        if (str_contains($file, '..') || str_contains($file, '/') || str_contains($file, '\\')) {
            abort(400, 'Invalid file name.');
        }

        $job = BulkExportJob::where('id', $jobId)
            ->where('facility_id', $facilityId)
            ->first();

        if (! $job || ! $job->isComplete() || $job->isExpired()) {
            abort(404, 'File not found or job has expired.');
        }

        $path = "fhir-exports/{$jobId}/{$file}";

        if (! Storage::exists($path)) {
            abort(404, 'Export file not found.');
        }

        return response(Storage::get($path), 200, [
            'Content-Type'        => 'application/fhir+ndjson',
            'Content-Disposition' => "attachment; filename=\"{$file}\"",
            'Cache-Control'       => 'no-store',
            'Expires'             => $job->expires_at?->toRfc7231String(),
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function resolveFacilityId(Request $request): string
    {
        $facilityId = $request->attributes->get('facility_id');

        if (empty($facilityId)) {
            abort(403, json_encode([
                'resourceType' => 'OperationOutcome',
                'issue'        => [[
                    'severity'    => 'fatal',
                    'code'        => 'forbidden',
                    'diagnostics' => 'facility_id could not be resolved from bearer token.',
                ]],
            ]));
        }

        return $facilityId;
    }

    private function notFound(string $message = 'Export job not found.'): JsonResponse
    {
        return response()->json([
            'resourceType' => 'OperationOutcome',
            'issue'        => [[
                'severity'    => 'error',
                'code'        => 'not-found',
                'diagnostics' => $message,
            ]],
        ], 404)->withHeaders(['Content-Type' => 'application/fhir+json']);
    }
}

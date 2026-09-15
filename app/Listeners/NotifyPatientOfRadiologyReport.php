<?php

namespace App\Listeners;

use App\Events\RadiologyReportDistributed;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * A distributed radiology report tells its patient, on the same bell the lab
 * desk uses for a released result (a `notifications` row on the patient),
 * pointing at their documents. Never fatal: a failed bell is logged and the
 * release stands.
 */
class NotifyPatientOfRadiologyReport
{
    public function handle(RadiologyReportDistributed $event): void
    {
        $report  = $event->report;
        $patient = Patient::query()->withoutGlobalScopes()->find($report->patient_id);

        if ($patient === null) {
            return;
        }

        $locale = $this->patientLocale((string) $patient->id);

        try {
            DB::table('notifications')->insert([
                'id'              => (string) Str::uuid(),
                'type'            => 'radiology_report_available',
                'notifiable_type' => $patient->getMorphClass(),
                'notifiable_id'   => $patient->id,
                'data'            => json_encode([
                    'type'                => 'radiology_report_available',
                    'title'               => __('files.notify.title', [], $locale),
                    'message'             => __('files.notify.body', ['facility' => (string) ($report->facility?->name ?? '')], $locale),
                    'severity'            => 'normal',
                    'action_url'          => '/patient/documents',
                    'action_label'        => __('files.notify.action', [], $locale),
                    'radiology_report_id' => $report->id,
                ], JSON_UNESCAPED_UNICODE),
                'read_at'         => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('radiology report: patient bell failed', ['report_id' => $report->id, 'error' => $e->getMessage()]);
        }
    }

    private function patientLocale(string $patientId): string
    {
        try {
            $preferred = DB::table('mobile_app_settings')->where('patient_id', $patientId)->value('preferred_language');
        } catch (\Throwable) {
            $preferred = null;
        }

        $preferred = strtolower(substr((string) $preferred, 0, 2));

        return in_array($preferred, ['en', 'fr'], true) ? $preferred : app()->getLocale();
    }
}

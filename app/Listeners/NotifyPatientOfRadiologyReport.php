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
    /**
     * The bell type, the imaging twin of the lab desk's `lab_result_available`
     * (`lab_api.notify.result_ready_*`). The mobile app routes on this string.
     */
    public const EVENT = 'radiology_report_available';

    public function handle(RadiologyReportDistributed $event): void
    {
        $report  = $event->report;
        $patient = Patient::query()->withoutGlobalScopes()->find($report->patient_id);

        if ($patient === null) {
            return;
        }

        $locale = $this->patientLocale((string) $patient->id);
        $title  = __('files.notify.title', [], $locale);
        $body   = __('files.notify.body', ['facility' => (string) ($report->facility?->name ?? '')], $locale);

        try {
            DB::table('notifications')->insert([
                'id'              => (string) Str::uuid(),
                'type'            => self::EVENT,
                'notifiable_type' => $patient->getMorphClass(),
                'notifiable_id'   => $patient->id,
                'data'            => json_encode([
                    'type'                => self::EVENT,
                    'title'               => $title,
                    'message'             => $body,
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

        // The same push channel a released laboratory result uses; a no-op on
        // an estate without the patient push service.
        $push = '\App\Support\PatientPush';
        if (class_exists($push) && method_exists($push, 'send')) {
            try {
                $push::send($patient, self::EVENT, $title, $body, [
                    'radiology_report_id' => $report->id,
                    'action_url'          => '/patient/documents',
                ]);
            } catch (\Throwable $e) {
                Log::warning('radiology report: patient push failed', ['report_id' => $report->id, 'error' => $e->getMessage()]);
            }
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

<?php

namespace App\Listeners;

use App\Events\RadiologyReportDistributed;
use App\Models\Patient;
use App\Support\Notify;
use Illuminate\Support\Facades\Log;

/**
 * DX-3 (2026-10-20): a distributed radiology report also tells the clinician
 * who ORDERED the study, on the staff bell (`notification_events` via
 * App\Support\Notify, which uses the Modules NotificationEvent model).
 * Before this only the patient was told (NotifyPatientOfRadiologyReport) and
 * `distributed_to` named only the radiologist.
 *
 * The link is the clinician results inbox path the app routes on
 * (`/clinician/results/rad-<report>`, MobileResultsInboxController); the web
 * renders it only if a web route answers (App\Support\WebNotificationLink).
 *
 * Never fatal: a failed bell is logged and the distribution stands. Nobody is
 * notified when there is no ordering clinician, or when the ordering
 * clinician is the person who distributed it.
 */
class NotifyOrderingClinicianOfRadiologyReport
{
    public const EVENT = 'radiology_report_ready';

    public function handle(RadiologyReportDistributed $event): void
    {
        $report    = $event->report;
        $orderedBy = (string) ($report->ordered_by ?? $report->imagingOrder?->ordered_by ?? '');

        if ($orderedBy === '') {
            return;
        }

        $actorId = isset($event->userIds[0]) ? (string) $event->userIds[0] : null;
        if ($actorId !== null && $actorId === $orderedBy) {
            return;
        }

        $patient = Patient::query()->withoutGlobalScopes()->find($report->patient_id);
        $order   = $report->imagingOrder;
        $study   = $order ? trim($order->modalityLabel() . ' ' . (string) $order->body_part) : '';

        try {
            Notify::toUser(
                $orderedBy,
                self::EVENT,
                'lab',
                [
                    'title'               => __('imaging_report.notify_clinician.title', ['study' => $study]),
                    'message'             => __('imaging_report.notify_clinician.body', [
                        'patient'  => $patient ? trim($patient->first_name . ' ' . $patient->last_name) : '',
                        'study'    => $study,
                        'facility' => (string) ($report->facility?->name ?? ''),
                    ]),
                    'url'                 => '/clinician/results/rad-' . $report->id,
                    'radiology_report_id' => $report->id,
                    'imaging_order_id'    => $report->imaging_order_id,
                    'patient_id'          => $report->patient_id,
                    'facility_id'         => $report->facility_id,
                ],
                'normal',
                $actorId,
                'RadiologyReport',
                (string) $report->id,
            );
        } catch (\Throwable $e) {
            Log::warning('radiology report: ordering clinician bell failed', [
                'report_id' => $report->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}

<?php

namespace App\Services\Patient\Passport\Pages;

use App\Models\Appointment;
use App\Models\CarePlan;
use App\Models\Facility;
use App\Models\Patient;
use App\Services\Patient\Passport\PageSource;

/**
 * Page 29 — Care plans, and what is booked next.
 *
 * The design's care-plan block is a bulleted list of recommendations. Those
 * bullets are only written here when a care plan actually records them: advice
 * a clinician did not give must never appear over their signature, and a
 * patient reading "carry a rescue inhaler" that nobody prescribed is being
 * given a plan by a piece of software.
 */
class CarePlansPage extends PageSource
{
    public function build(Patient $patient): array
    {
        $sections = [];

        $plans = CarePlan::where('patient_id', $patient->id)
            ->orderByDesc('start_date')
            ->limit(6)
            ->get();

        if ($plans->isNotEmpty()) {
            $sections[] = $this->table(
                __('passport.sections.care_plans'),
                [__('passport.columns.plan'), __('passport.columns.started'), __('passport.columns.status')],
                $plans->map(fn (CarePlan $p) => [
                    $this->cell((string) ($p->title ?: __('passport.recorded'))),
                    $this->cell($this->date($p->start_date)),
                    $this->cell($this->statusLabel((string) $p->status), $this->statusTone((string) $p->status)),
                ])->all(),
                'purple',
                'clipboard'
            );

            // The sheet's recommendation bullets — only where a plan recorded them.
            $notes = $plans
                ->pluck('description')
                ->filter(fn ($d) => is_string($d) && trim($d) !== '')
                ->take(6)
                ->values()
                ->all();

            if ($notes !== []) {
                $sections[] = $this->bullets(
                    __('passport.sections.care_recommendations'),
                    $notes,
                    'purple',
                    'check-circle'
                );
            }
        }

        if ($next = $this->upcoming($patient)) {
            $sections[] = $next;
        }

        return $sections;
    }

    /** What is already booked — the one forward-looking thing the record holds. */
    private function upcoming(Patient $patient): ?array
    {
        $rows = Appointment::where('patient_id', $patient->id)
            ->whereIn('status', ['scheduled', 'booked', 'confirmed'])
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $facilities = Facility::withoutGlobalScope('isolate_demo')
            ->whereIn('id', $rows->pluck('facility_id')->filter()->all())
            ->pluck('name', 'id');

        return $this->table(
            __('passport.next_appointment'),
            [__('passport.columns.date'), __('passport.columns.facility'), __('passport.columns.status')],
            $rows->map(fn (Appointment $a) => [
                $this->cell($this->date($a->scheduled_at, 'd M Y, H:i')),
                $this->cell((string) ($facilities[$a->facility_id] ?? __('passport.a_facility'))),
                $this->cell($this->statusLabel((string) $a->status), $this->statusTone((string) $a->status)),
            ])->all(),
            'blue',
            'calendar-check'
        );
    }
}

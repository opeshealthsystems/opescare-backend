<?php

namespace App\Services\Patient\Passport\Pages;

use App\Models\Facility;
use App\Models\HealthIdVerificationEvent;
use App\Models\Patient;
use App\Services\Patient\Passport\PageSource;

/**
 * Page 24 — Verification Centre.
 *
 * WHAT THIS PAGE MAY AND MAY NOT CLAIM. The design sheet for this page lists a
 * "Security features check" with every line ticked — security paper, watermark,
 * microtext, holographic seal, document hash, digital signature, blockchain
 * record — and a set of verification methods including a blockchain check and a
 * digital-signature check.
 *
 * Most of that is not built here, and printing a green tick beside a check this
 * deployment does not perform would be a false security claim on a health
 * credential. Someone would rely on it. So this page carries only what is real:
 * what the register says about the credential right now, and the verifications
 * that have actually been recorded against this Health ID.
 *
 * The genuine article — a short-lived, opaque code a clinician can scan — is
 * minted by the app on this page and dies on a countdown. Nothing clinical is
 * ever encoded in it, and it is the only thing on these thirty pages that
 * establishes anything.
 */
class VerificationPage extends PageSource
{
    public function build(Patient $patient): array
    {
        $status = $this->credentialStatus($patient);

        $sections = [
            $this->callout(
                __('passport.sections.verification'),
                __('passport.checked_against_value'),
                'teal',
                'shield-check'
            ),

            $this->section(__('passport.sections.identity'), [
                $this->item(__('passport.status'), $this->statusLabel($status), $status === 'ACTIVE' ? 'success' : 'warning'),
                $this->item(__('passport.health_id'), (string) $patient->health_id),
                $this->item(__('passport.common_name'), $this->displayName($patient)),
                $this->item(__('passport.checked_against'), __('passport.checked_against_value')),
            ], 'blue', 'id-card'),
        ];

        if ($history = $this->history($patient)) {
            $sections[] = $history;
        }

        return $sections;
    }

    /**
     * Who has checked this Health ID, and what the register told them.
     *
     * This is the one part of the sheet's verification page that is real: the
     * platform records a row every time a Health ID is verified, so the table
     * shows checks that happened rather than checks we would like to advertise.
     */
    private function history(Patient $patient): ?array
    {
        $rows = HealthIdVerificationEvent::where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->limit(self::RECENT_LIMIT)
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $facilities = Facility::withoutGlobalScope('isolate_demo')
            ->whereIn('id', $rows->pluck('verified_by_facility_id')->filter()->all())
            ->pluck('name', 'id');

        return $this->table(
            __('passport.sections.verification_history'),
            [__('passport.columns.date'), __('passport.columns.who'), __('passport.columns.type'), __('passport.columns.result')],
            $rows->map(fn (HealthIdVerificationEvent $e) => [
                $this->cell($this->date($e->created_at, 'd M Y, H:i')),
                $this->cell((string) ($facilities[$e->verified_by_facility_id] ?? __('passport.a_facility'))),
                $this->cell($this->statusLabel((string) $e->verification_type)),
                $this->cell(
                    $this->statusLabel((string) $e->verification_status),
                    $this->statusTone((string) $e->verification_status)
                ),
            ])->all(),
            'green',
            'check-circle'
        );
    }
}

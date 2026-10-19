<?php

namespace App\Enums;

/** Which door a pain site came through. Structural: each door writes its own. */
enum PainSiteSource: string
{
    /** The patient (or a guardian acting for them) at pre-consult check-in. */
    case PatientCheckin = 'patient_checkin';

    /** The nurse at the triage desk. */
    case Triage = 'triage';

    /** A clinician working from the triage record. */
    case Clinician = 'clinician';

    public function label(): string
    {
        return __('pain.source.' . $this->value);
    }

    /** Patient-reported, never a clinical observation. */
    public function isPatientReported(): bool
    {
        return $this === self::PatientCheckin;
    }
}

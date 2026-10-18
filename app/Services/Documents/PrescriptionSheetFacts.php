<?php

namespace App\Services\Documents;

use App\Models\AllergyRecord;
use App\Models\Facility;
use App\Models\FacilityRegistry;
use App\Models\Prescription;
use App\Models\TriageVitalSign;
use App\Models\User;
use App\Support\Enums;
use Illuminate\Support\Carbon;

/**
 * The facts a printed prescription carries — ONE source for both sheets.
 *
 * The patient's PDF (documents/prescription) and the clinic's printed sheet
 * (portals/prescriber/print) used to read the record separately and disagreed:
 * one had the prescriber's licence, sex and a signature block, the other did
 * not. Both now read this, so they cannot drift apart again.
 *
 * Nothing here is invented. A value the record does not hold comes back null
 * and the sheet prints "not recorded"; the patient's weight is the last one
 * measured and is printed WITH its date, never presented as today's.
 * Spec: docs/specs/documents/01-prescription.md.
 */
class PrescriptionSheetFacts
{
    /**
     * @return array{
     *   prescriberName: ?string, credential: ?string, licenseNumber: ?string,
     *   facility: ?Facility, registry: ?FacilityRegistry, facilityName: ?string,
     *   facilityLicence: ?string, ministryCode: ?string, contactLines: list<string>,
     *   patientAge: ?int, weightKg: ?string, weightAt: ?Carbon, allergies: list<string>,
     *   lines: list<array<string, mixed>>
     * }
     */
    public function for(Prescription $prescription): array
    {
        $prescription->loadMissing(['items.medicine', 'patient']);

        [$credential, $licence] = $this->prescriberCredential($prescription);

        $prescriberName = optional($prescription->prescriber)->name
            ?? ($prescription->prescribed_by ? User::find($prescription->prescribed_by)?->name : null);

        $facility = $prescription->facility_id
            ? Facility::withoutGlobalScope('isolate_demo')->find($prescription->facility_id)
            : null;

        $registry = $facility
            ? FacilityRegistry::where('claimed_facility_id', $facility->id)->first()
            : null;

        $contactLines = array_values(array_filter([
            trim(implode(', ', array_filter([
                (string) ($registry->address ?? ''),
                (string) ($registry->city ?? ''),
                (string) ($registry->region ?? ''),
            ]))),
            (string) ($registry->phone ?? ''),
            (string) ($registry->email ?? ''),
        ], fn ($line) => trim((string) $line) !== ''));

        $patient = $prescription->patient;
        $at      = $prescription->prescribed_at ?? now();

        $age = null;
        if ($patient && $patient->date_of_birth) {
            try {
                $age = (int) Carbon::parse($patient->date_of_birth)->diffInYears($at);
            } catch (\Throwable) {
                $age = null;
            }
        }

        $weight = $patient
            ? TriageVitalSign::where('patient_id', $patient->id)
                ->whereNotNull('weight_kg')
                ->orderByDesc('recorded_at')
                ->orderByDesc('created_at')
                ->first()
            : null;

        $allergies = $patient
            ? AllergyRecord::where('patient_id', $patient->id)
                ->where(fn ($q) => $q->whereNull('status')->orWhereNotIn('status', ['inactive', 'resolved', 'entered_in_error', 'refuted']))
                ->pluck('substance')
                ->filter()
                ->map(fn ($s) => (string) $s)
                ->unique()
                ->values()
                ->all()
            : [];

        $lines = [];
        foreach ($prescription->items ?? [] as $item) {
            $medicine = $item->medicine;
            $inn      = $medicine?->generic_name ?: null;
            $name     = $item->drug_name ?: ($medicine?->name ?: null);

            $lines[] = [
                // The INN leads; the brand/written name follows when it differs.
                'inn'       => $inn ?: $name,
                'brand'     => ($inn && $name && mb_strtolower($inn) !== mb_strtolower($name)) ? $name : null,
                'strength'  => $item->strength ?: ($medicine?->strength ?: null),
                'form'      => $item->dosage_form ?: ($medicine?->form ? (string) ($medicine->form instanceof \BackedEnum ? $medicine->form->value : $medicine->form) : null),
                'dose'      => $item->dose ?: null,
                'route'     => $item->route ? Enums::label($item->route, 'route') : null,
                'frequency' => $item->frequency ?: null,
                'duration'  => $item->duration_days,
                'quantity'  => $item->quantity,
                'code'      => $item->drug_code ?: null,
            ];
        }

        return [
            'prescriberName'  => $prescriberName ?: null,
            'credential'      => $credential,
            'licenseNumber'   => $licence,
            'facility'        => $facility,
            'registry'        => $registry,
            'facilityName'    => $facility?->name ?: ($registry->name ?? null),
            'facilityLicence' => trim((string) ($facility->license_number ?? '')) ?: null,
            'ministryCode'    => trim((string) ($registry->ministry_code ?? '')) ?: null,
            'contactLines'    => $contactLines,
            'patientAge'      => $age,
            'weightKg'        => $weight?->weight_kg !== null ? rtrim(rtrim((string) $weight->weight_kg, '0'), '.') : null,
            'weightAt'        => $weight ? ($weight->recorded_at ?? $weight->created_at) : null,
            'allergies'       => $allergies,
            'lines'           => $lines,
        ];
    }

    /**
     * What the prescriber is licensed as, if the record knows: profession and
     * licence number from the staff profile, falling back to the job title.
     *
     * @return array{0: ?string, 1: ?string}
     */
    public function prescriberCredential(Prescription $prescription): array
    {
        $c = ClinicianCredential::for($prescription->prescribed_by);

        return [$c['credential'], $c['licence']];
    }
}

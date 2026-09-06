<?php

namespace App\Services\Patient\Passport\Pages;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\PatientIdentifier;
use App\Services\Patient\HealthPassportBuilder;
use App\Services\Patient\Passport\PageSource;
use Illuminate\Support\Carbon;

/**
 * Page 02 — Patient Identity.
 *
 * Who this record belongs to, and what makes the credential checkable. Every
 * other page in the passport is only as trustworthy as this one, so it is held
 * to a stricter reading of the never-invent rule than the clinical pages.
 *
 * ── WHAT THIS PAGE MUST NOT DO ───────────────────────────────────────────
 *
 * It must never imply an identity has been checked when it has not. A record
 * whose identity_status is merely `active` has not been verified by anyone —
 * it has just been allowed into clinical use — so the credential block reads
 * its state through credentialStatus()/statusLabel() (ACTIVE, PROVISIONAL,
 * SUSPENDED, REVOKED) and never prints the word "verified". The verifying
 * facility appears only when the record actually carries BOTH the facility
 * that did the checking and the moment it was done; either alone is not a
 * verification anyone could stand behind.
 *
 * The same care applies to the date of birth. `is_dob_estimated` means the
 * date was reconstructed — commonly from a stated age — so it is shown with a
 * caution tone rather than presented as a registered fact. Dosing and
 * eligibility decisions get made off this line.
 *
 * ── WHAT THE APPROVED SHEET SHOWS THAT THIS DEPLOYMENT CANNOT ────────────
 *
 * The sheet's identity card carries a portrait, nationality, preferred
 * language, marital status, a separate "Passport ID", a printed document ID
 * with a barcode, and a scan-to-verify QR. The `patients` table records a
 * photo path but the page API has no image block; nationality, language and
 * marital status are not recorded at all; and the passport is not itself a
 * minted document, so there is no document number to print and the QR is
 * short-lived and minted elsewhere (see the verification page). Those blocks
 * are therefore absent rather than filled with something plausible.
 *
 * Primary care provider and insurance also appear on the sheet; they are pages
 * 21 and 22 and are read from their own tables there. `patients.facility_id`
 * is an undocumented column with no writer in this codebase, so it is not
 * presented here as anybody's care provider.
 */
class PatientIdentityPage extends PageSource
{
    /** Enough for every identity document a person realistically carries. */
    private const MAX_IDENTIFIERS = 12;

    public function build(Patient $patient): array
    {
        return array_values(array_filter([
            $this->identity($patient),
            $this->contact($patient),
            $this->nextOfKin($patient),
            $this->documents($patient),
            $this->credential($patient),
        ]));
    }

    // ── The identity card ────────────────────────────────────────────────

    /**
     * Name, Health ID and the demographics the record actually holds.
     *
     * Name and Health ID are always present — a patient row cannot exist
     * without them — so this block, and therefore the page, is never empty.
     */
    private function identity(Patient $patient): array
    {
        $items = [
            $this->item(__('passport.common_name'), $this->fullName($patient)),
            $this->item(__('passport.health_id'), (string) $patient->health_id),
        ];

        if ($patient->date_of_birth) {
            // An estimated date is still the recorded date, so it is shown in
            // full — but toned, because it was not read off a birth record.
            $items[] = $this->item(
                __('passport.dob'),
                $this->date($patient->date_of_birth),
                $patient->is_dob_estimated ? 'caution' : null
            );
        }

        if (filled($patient->sex)) {
            $items[] = $this->item(__('passport.sex'), $this->sexLabel((string) $patient->sex));
        }

        if (filled($patient->blood_group)) {
            $items[] = $this->item(__('passport.blood_group'), (string) $patient->blood_group);
        }

        return $this->section(__('passport.sections.identity'), $items, 'blue', 'id-card');
    }

    /**
     * The full recorded name, middle name included.
     *
     * displayName() is the passport-wide short form and is what the running
     * head and every other page use. This is the one page whose subject IS the
     * name, so it shows all of it — and falls back to exactly displayName()
     * when there is no middle name, so the two never disagree.
     */
    private function fullName(Patient $patient): string
    {
        $parts = array_filter([
            trim((string) $patient->first_name),
            trim((string) $patient->middle_name),
            trim((string) $patient->last_name),
        ], fn (string $p) => $p !== '');

        return $parts === [] ? $this->displayName($patient) : implode(' ', $parts);
    }

    // ── How to reach them ────────────────────────────────────────────────

    /**
     * Phone, email and address, as the sheet's contact card sets them: the
     * values on their own. The sheet labels each line with an icon rather than
     * a word, and this deployment has no per-field caption in the passport
     * vocabulary, so a bulleted card is both the closer match and the honest
     * one — a mislabelled address is worse than an unlabelled one.
     */
    private function contact(Patient $patient): ?array
    {
        $lines = array_values(array_filter([
            $this->clean($patient->phone_number),
            $this->clean($patient->email),
            $this->clean($patient->address),
        ]));

        if ($lines === []) {
            return null;
        }

        return $this->bullets(__('passport.columns.contact'), $lines, 'blue', 'users');
    }

    /**
     * The emergency contact.
     *
     * `patients.emergency_contact` is free-form json across the estate, so the
     * three fields are read under the spellings that are actually in use and
     * left out when the record does not hold them. Red, because on the sheet
     * this block is the one somebody reads in a hurry.
     */
    private function nextOfKin(Patient $patient): ?array
    {
        $raw = $patient->emergency_contact;

        if (! is_array($raw) || $raw === []) {
            return null;
        }

        // Some writers store a single contact, others a list of them.
        $contacts = array_is_list($raw) ? $raw : [$raw];
        $items    = [];

        foreach ($contacts as $contact) {
            if (! is_array($contact)) {
                continue;
            }

            $name     = $this->pick($contact, ['name', 'full_name', 'contact_name']);
            $phone    = $this->pick($contact, ['phone', 'phone_number', 'contact_phone', 'telephone']);
            $relation = $this->pick($contact, ['relation', 'relationship', 'relation_to_patient']);

            if ($name === null && $phone === null && $relation === null) {
                continue;
            }

            if ($name !== null) {
                $items[] = $this->item(__('passport.emergency_contact'), $name);
            }

            if ($phone !== null) {
                $items[] = $this->item(__('passport.columns.contact'), $phone);
            }

            if ($relation !== null) {
                $items[] = $this->item(__('passport.columns.role'), $relation);
            }
        }

        if ($items === []) {
            return null;
        }

        return $this->section(__('passport.sections.emergency_contacts'), $items, 'red', 'first-aid');
    }

    // ── The documents behind the identity ────────────────────────────────

    /**
     * Identity documents on file — the national ID, the insurance number, and
     * anything a facility has registered against this patient.
     *
     * The sheet prints ID numbers on the identity card, and this is the table
     * that holds them. A patient whose identity rests on no document at all
     * gets no block, which is itself worth seeing.
     */
    private function documents(Patient $patient): ?array
    {
        $rows = [];

        if (filled($patient->national_id_number)) {
            $rows[] = [
                $this->statusLabel($patient->national_id_type ? (string) $patient->national_id_type : 'national_id'),
                (string) $patient->national_id_number,
            ];
        }

        if (filled($patient->cnamgs_id)) {
            $rows[] = ['CNAMGS', (string) $patient->cnamgs_id];
        }

        foreach ($this->registeredIdentifiers($patient) as $identifier) {
            if (! filled($identifier->identifier_value)) {
                continue;
            }

            $rows[] = [
                $this->statusLabel((string) $identifier->identifier_type),
                (string) $identifier->identifier_value,
            ];
        }

        if ($rows === []) {
            return null;
        }

        return $this->table(
            __('passport.sections.document_details'),
            [__('passport.columns.type'), __('passport.columns.number')],
            $rows,
            'blue',
            'document-text'
        );
    }

    /** @return iterable<PatientIdentifier> */
    private function registeredIdentifiers(Patient $patient): iterable
    {
        try {
            return PatientIdentifier::where('patient_id', $patient->id)
                ->orderBy('created_at')
                ->limit(self::MAX_IDENTIFIERS)
                ->get();
        } catch (\Throwable) {
            // A deployment without the table must still get its identity card.
            return [];
        }
    }

    // ── The credential itself ────────────────────────────────────────────

    /**
     * What state this Health ID is in, and how long it stands.
     *
     * The status is the credential's own state, never a claim about the
     * person: ACTIVE means the ID is usable, not that anyone has confirmed
     * who is holding it.
     */
    private function credential(Patient $patient): array
    {
        $status = $this->credentialStatus($patient);

        $items = [
            $this->item(__('passport.status'), $this->statusLabel($status), match ($status) {
                'ACTIVE'      => 'success',
                'PROVISIONAL' => 'warning',
                default       => 'danger',
            }),
        ];

        if ($patient->created_at) {
            $items[] = $this->item(__('passport.blocks.issued_on'), $this->date($patient->created_at));
        }

        if ($patient->expires_at) {
            // Past its date, the "valid until" line is the whole story — say so
            // in colour rather than leaving it to be read as a future date.
            $expired = $this->hasPassed($patient->expires_at);

            $items[] = $this->item(
                __('passport.blocks.valid_until_label'),
                $this->date($patient->expires_at),
                $expired ? 'danger' : null
            );
        }

        // Only a facility AND a moment together amount to a verification.
        if ($patient->verified_at && filled($patient->verified_by_facility_id)) {
            $facility = $this->verifyingFacility($patient);

            if ($facility !== null) {
                $items[] = $this->item(
                    __('passport.columns.facility'),
                    $facility . ' · ' . $this->date($patient->verified_at)
                );
            }
        }

        $items[] = $this->item(__('passport.version'), HealthPassportBuilder::VERSION);

        if ($patient->updated_at) {
            $items[] = $this->item(__('passport.last_updated'), $this->date($patient->updated_at));
        }

        return $this->section(
            __('passport.sections.credential'),
            $items,
            $status === 'ACTIVE' ? 'green' : 'amber',
            'shield-check'
        );
    }

    /** The name of the facility that checked this identity, or nothing. */
    private function verifyingFacility(Patient $patient): ?string
    {
        try {
            $name = Facility::whereKey($patient->verified_by_facility_id)->value('name');
        } catch (\Throwable) {
            return null;
        }

        $name = trim((string) $name);

        return $name !== '' ? $name : null;
    }

    // ── Small readings ───────────────────────────────────────────────────

    private function hasPassed(mixed $value): bool
    {
        try {
            return Carbon::parse($value)->isPast();
        } catch (\Throwable) {
            return false;
        }
    }

    /** The first of these keys the record actually carries, trimmed. */
    private function pick(array $source, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($source[$key]) && is_scalar($source[$key]) && trim((string) $source[$key]) !== '') {
                return trim((string) $source[$key]);
            }
        }

        return null;
    }

    private function clean(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }
}

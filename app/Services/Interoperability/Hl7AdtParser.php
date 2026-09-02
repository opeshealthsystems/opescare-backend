<?php
namespace App\Services\Interoperability;

class Hl7AdtParser
{
    /**
     * Parse an HL7 v2 ADT message into a structured array.
     * Supports A01 (Admit), A02 (Transfer), A03 (Discharge), A08 (Update).
     *
     * @throws \InvalidArgumentException for non-HL7 input
     */
    public function parse(string $message): array
    {
        $segments = preg_split('/[\r\n]+/', trim($message));

        if (empty($segments) || !str_starts_with($segments[0], 'MSH')) {
            throw new \InvalidArgumentException('Not a valid HL7 v2 message — MSH segment missing');
        }

        $msh = $this->parseSegment($segments[0]);
        $pid = $this->findSegment($segments, 'PID');
        $pv1 = $this->findSegment($segments, 'PV1');

        // MSH-9 = message type: "ADT^A01"
        $eventType = isset($msh[8]) ? (explode('^', $msh[8])[1] ?? '') : '';

        // PID-3: patient ID list (first component), PID-5: family^given, PID-7: DOB, PID-8: gender
        $patientId  = $pid ? (explode('^', $pid[3] ?? '')[0] ?? null) : null;
        $name       = $pid ? explode('^', $pid[5] ?? '') : [];
        $familyName = $name[0] ?? null;
        $givenName  = $name[1] ?? null;
        $dob        = $pid[7] ?? null;
        $gender     = $pid[8] ?? null;

        // PV1-3: ward^bed^facility
        $location = $pv1 ? explode('^', $pv1[3] ?? '') : [];
        $ward     = $location[0] ?? null;
        $bed      = $location[1] ?? null;

        return [
            'event_type'  => $eventType,
            'patient_id'  => $patientId,
            'family_name' => $familyName,
            'given_name'  => $givenName,
            'dob'         => $dob,
            'gender'      => $gender,
            'ward'        => $ward,
            'bed'         => $bed,
            'raw_msh'     => $msh,
        ];
    }

    private function parseSegment(string $line): array
    {
        return explode('|', $line);
    }

    private function findSegment(array $segments, string $type): ?array
    {
        foreach ($segments as $seg) {
            if (str_starts_with($seg, $type . '|')) {
                return $this->parseSegment($seg);
            }
        }
        return null;
    }
}

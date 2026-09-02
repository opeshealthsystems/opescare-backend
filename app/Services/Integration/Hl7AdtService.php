<?php

namespace App\Services\Integration;

use App\Models\Facility;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Stateless HL7 v2.5 ADT outbound sender.
 *
 * Supported events: A01 (admit), A08 (update patient info), A28 (add person info).
 * Transport: MLLP (Minimum Lower Layer Protocol) over TCP sockets.
 * NOT a full HL7 parser — send-only.
 */
class Hl7AdtService
{
    // MLLP framing bytes
    public const VT = "\x0b"; // Vertical Tab — start of block
    public const FS = "\x1c"; // File Separator — end of block
    public const CR = "\r";   // Carriage Return — final terminator

    // HL7 field delimiters
    private const FIELD     = '|';
    private const COMPONENT = '^';
    private const REPEAT    = '~';
    private const ESCAPE    = '\\';
    private const SUBCOMP   = '&';

    // -------------------------------------------------------------------------
    // Message builders
    // -------------------------------------------------------------------------

    /**
     * Build an ADT^A01 (Admit/Visit Notification) HL7 v2.5 message string.
     */
    public function buildA01Message(Patient $patient, object $visit, Facility $facility): string
    {
        $segments = [
            $this->buildMsh('ADT', 'A01', $facility),
            $this->buildEvn('A01'),
            $this->buildPid($patient),
            $this->buildPv1($visit, 'I', $facility),
        ];

        return implode(self::CR, $segments) . self::CR;
    }

    /**
     * Build an ADT^A08 (Update Patient Information) HL7 v2.5 message string.
     */
    public function buildA08Message(Patient $patient): string
    {
        $segments = [
            $this->buildMsh('ADT', 'A08'),
            $this->buildEvn('A08'),
            $this->buildPid($patient),
        ];

        return implode(self::CR, $segments) . self::CR;
    }

    /**
     * Build an ADT^A28 (Add Person Information) HL7 v2.5 message string.
     */
    public function buildA28Message(Patient $patient): string
    {
        $segments = [
            $this->buildMsh('ADT', 'A28'),
            $this->buildEvn('A28'),
            $this->buildPid($patient),
        ];

        return implode(self::CR, $segments) . self::CR;
    }

    // -------------------------------------------------------------------------
    // Segment builders
    // -------------------------------------------------------------------------

    /**
     * MSH — Message Header Segment
     */
    private function buildMsh(string $messageType, string $triggerEvent, ?Facility $facility = null): string
    {
        $sendingApp        = config('hl7.sending_app', 'OPESCARE_EMR');
        $sendingFacility   = config('hl7.facility_id', 'OPESCARE');
        $receivingApp      = $facility?->hl7_receiving_app ?? 'RECEIVING_APP';
        $receivingFacility = $facility?->hl7_receiving_facility ?? 'RECEIVING_FACILITY';
        $dateTime          = Carbon::now()->format('YmdHis');
        $msgCtrlId         = strtoupper(substr(md5(uniqid('', true)), 0, 20));
        $msgType           = "{$messageType}^{$triggerEvent}";

        $fields = [
            'MSH',
            '^~\\&',            // MSH.2 encoding characters
            $sendingApp,        // MSH.3
            $sendingFacility,   // MSH.4
            $receivingApp,      // MSH.5
            $receivingFacility, // MSH.6
            $dateTime,          // MSH.7
            '',                 // MSH.8 (security)
            $msgType,           // MSH.9
            $msgCtrlId,         // MSH.10
            'P',                // MSH.11 (processing ID — P=Production)
            '2.5',              // MSH.12 (version ID)
        ];

        return implode(self::FIELD, $fields);
    }

    /**
     * EVN — Event Type Segment
     */
    private function buildEvn(string $eventTypeCode): string
    {
        $recorded = Carbon::now()->format('YmdHis');

        return implode(self::FIELD, [
            'EVN',
            $eventTypeCode, // EVN.1
            $recorded,      // EVN.2
        ]);
    }

    /**
     * PID — Patient Identification Segment
     */
    private function buildPid(Patient $patient): string
    {
        $patientId = $patient->id;
        $lastName  = $this->escape($patient->last_name ?? '');
        $firstName = $this->escape($patient->first_name ?? '');
        $dob       = $patient->date_of_birth
            ? Carbon::parse($patient->date_of_birth)->format('Ymd')
            : '';
        // Patient model uses 'sex' field (male/female/other) — map to HL7 M/F/U
        $sex       = strtoupper(substr($patient->sex ?? 'U', 0, 1));
        // phone_number is the correct field name on Patient
        $phone     = $this->escape($patient->phone_number ?? '');
        $mrn       = $patient->mrn ?? $patientId;

        $pidList = "{$mrn}^^^" . config('hl7.facility_id', 'OPESCARE');
        $name    = "{$lastName}" . self::COMPONENT . "{$firstName}";

        $fields = [
            'PID',
            '1',        // PID.1 set ID
            $patientId, // PID.2 patient ID (external)
            $pidList,   // PID.3 patient identifier list
            '',         // PID.4 alternate patient ID
            $name,      // PID.5 patient name
            '',         // PID.6 mother's maiden name
            $dob,       // PID.7 date of birth
            $sex,       // PID.8 administrative sex
            '',         // PID.9 patient alias
            '',         // PID.10 race
            '',         // PID.11 patient address
            '',         // PID.12 county code
            $phone,     // PID.13 phone number (home)
        ];

        return implode(self::FIELD, $fields);
    }

    /**
     * PV1 — Patient Visit Segment
     */
    private function buildPv1(object $visit, string $patientClass = 'O', ?Facility $facility = null): string
    {
        $visitId   = $visit->id ?? '';
        $location  = $facility?->code ?? config('hl7.facility_id', 'OPESCARE');
        $admitTime = '';
        if (isset($visit->admitted_at)) {
            $admitTime = Carbon::parse($visit->admitted_at)->format('YmdHis');
        }

        $fields = [
            'PV1',
            '1',           // PV1.1 set ID
            $patientClass, // PV1.2 patient class
            $location,     // PV1.3 assigned patient location
            '',            // PV1.4 admission type
            '',            // PV1.5 preadmit number
            '',            // PV1.6 prior patient location
            '',            // PV1.7 attending doctor
            '',            // PV1.8 referring doctor
            '',            // PV1.9 consulting doctor
            '',            // PV1.10 hospital service
            '', '', '', '', '', '', // PV1.11-17
            '',            // PV1.18 patient type
            $visitId,      // PV1.19 visit number
            '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', // PV1.20-43
            $admitTime,    // PV1.44 admit date/time
        ];

        return implode(self::FIELD, $fields);
    }

    // -------------------------------------------------------------------------
    // Transport
    // -------------------------------------------------------------------------

    /**
     * Send an HL7 message over MLLP/TCP (or MLLP-S/TLS when configured).
     *
     * [FIX M-1] Raw TCP was used unconditionally — PHI transmitted in plaintext.
     * Fix: when `hl7.tls` is true (default in production), the connection is
     * upgraded to TLS via stream_socket_client() with an SSL context.
     * The TLS peer is verified against the system CA bundle by default; set
     * `hl7.tls_verify_peer = false` only for self-signed certs in staging.
     *
     * Configuration keys (config/hl7.php):
     *   hl7.tls               — bool   (default: true)
     *   hl7.tls_verify_peer   — bool   (default: true)
     *   hl7.tls_cafile        — string (optional path to CA bundle)
     *   hl7.timeout           — int    (default: 5)
     *
     * Returns true on successful write (ACK not parsed — fire-and-forget).
     */
    public function send(string $hl7Message, string $host, int $port): bool
    {
        $timeout   = (int) config('hl7.timeout', 5);
        $useTls    = (bool) config('hl7.tls', true);
        $caFile    = config('hl7.tls_cafile');
        $verifyPeer = (bool) config('hl7.tls_verify_peer', true);

        // PHI must never leave in plaintext. In production this fails CLOSED —
        // the message is refused rather than transmitted unencrypted. In
        // non-production we warn but allow, so staging/dev without a TLS-capable
        // HL7 endpoint can still be exercised. (Supersedes the prior warn-only
        // [FIX M-1] behaviour, which still sent PHI in the clear.)
        if (! $useTls) {
            if (app()->isProduction()) {
                Log::error('Hl7AdtService: REFUSING to send — TLS is disabled in production. '
                    . 'PHI must not be transmitted in plaintext. Set HL7_TLS=true to enable '
                    . 'transport encryption. ISO 27799 §8.2 / ISO 27001 A.13.2.3.', [
                    'host' => $host,
                    'port' => $port,
                ]);
                return false;
            }

            Log::warning('Hl7AdtService: TLS is DISABLED — PHI will be sent in plaintext. '
                . 'Set HL7_TLS=true to enable transport encryption. '
                . 'ISO 27799 §8.2 / ISO 27001 A.13.2.3 require encryption of PHI in transit.', [
                'host' => $host,
                'port' => $port,
            ]);
        }

        try {
            $socket = $this->openSocket($host, $port, $timeout, $useTls, $verifyPeer, $caFile);

            if ($socket === false) {
                return false;
            }

            stream_set_timeout($socket, $timeout);

            $framed  = self::VT . $hl7Message . self::FS . self::CR;
            $written = fwrite($socket, $framed);

            if ($written === false || $written === 0) {
                Log::warning('Hl7AdtService: Failed to write to socket', [
                    'host' => $host,
                    'port' => $port,
                    'tls'  => $useTls,
                ]);
                fclose($socket);
                return false;
            }

            // Drain the ACK to avoid RST (not parsed)
            fread($socket, 4096);
            fclose($socket);

            Log::info('Hl7AdtService: Message sent', [
                'host'  => $host,
                'port'  => $port,
                'bytes' => $written,
                'tls'   => $useTls,
            ]);

            return true;

        } catch (\Throwable $e) {
            Log::error('Hl7AdtService: Exception during send', [
                'host'      => $host,
                'port'      => $port,
                'tls'       => $useTls,
                'exception' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Open a socket connection — TLS (stream_socket_client + SSL context) or plain TCP.
     *
     * @return resource|false
     */
    private function openSocket(
        string  $host,
        int     $port,
        int     $timeout,
        bool    $useTls,
        bool    $verifyPeer,
        ?string $caFile
    ): mixed {
        if ($useTls) {
            // Build SSL context — peer verification is mandatory in production.
            $contextOptions = [
                'ssl' => [
                    'verify_peer'       => $verifyPeer,
                    'verify_peer_name'  => $verifyPeer,
                    'allow_self_signed' => ! $verifyPeer,
                    'SNI_enabled'       => true,
                    'peer_name'         => $host,
                ],
            ];

            if (! empty($caFile) && file_exists($caFile)) {
                $contextOptions['ssl']['cafile'] = $caFile;
            }

            $context = stream_context_create($contextOptions);

            // 'tls://' triggers TLS negotiation during connect (MLLP-S).
            $socket = @stream_socket_client(
                "tls://{$host}:{$port}",
                $errno,
                $errstr,
                $timeout,
                STREAM_CLIENT_CONNECT,
                $context
            );
        } else {
            // Plain TCP fallback — only when operator explicitly sets HL7_TLS=false.
            $socket = @stream_socket_client(
                "tcp://{$host}:{$port}",
                $errno,
                $errstr,
                $timeout,
                STREAM_CLIENT_CONNECT
            );
        }

        if ($socket === false) {
            Log::warning('Hl7AdtService: Could not connect to HL7 host', [
                'host'   => $host,
                'port'   => $port,
                'tls'    => $useTls,
                'errno'  => $errno,
                'errstr' => $errstr,
            ]);
        }

        return $socket;
    }

    /**
     * Build and send an ADT^A01 message for a patient admission.
     */
    public function sendAdmission(string $patientId, string $visitId): bool
    {
        $patient  = Patient::findOrFail($patientId);
        $facility = Facility::first();
        $visit    = (object) ['id' => $visitId, 'admitted_at' => now(), 'visit_type' => 'inpatient'];

        $message = $this->buildA01Message($patient, $visit, $facility);

        return $this->send($message, config('hl7.host'), config('hl7.port'));
    }

    /**
     * Build and send an ADT^A08 message for a patient info update.
     */
    public function sendPatientUpdate(string $patientId): bool
    {
        $patient = Patient::findOrFail($patientId);
        $message = $this->buildA08Message($patient);

        return $this->send($message, config('hl7.host'), config('hl7.port'));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Escape HL7 special characters in a field value.
     */
    private function escape(string $value): string
    {
        return str_replace(
            ['\\', '|', '^', '~', '&'],
            ['\\E\\', '\\F\\', '\\S\\', '\\R\\', '\\T\\'],
            $value
        );
    }
}

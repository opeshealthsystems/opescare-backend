<?php

namespace Tests\Feature\Billing;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Care is never gated on payment — the structural half.
 *
 * Sibling of CareIsNeverGatedOnPaymentTest (which watches subscription gates
 * on care routes) and of CareIsNeverGatedOnQueueTest / …OnPreconsultTest
 * (which keep the queue and the pre-consult out of clinical code). This one
 * keeps the billing desk out of care, and care out of the billing desk, in
 * both directions, at the level of what a file can SPELL:
 *
 *   1. No clinical or queue file may name an invoice, a payment, a refund or
 *      a cashier session. A `->whereHas('invoice', paid)` on the triage desk,
 *      a "balance outstanding" check before dispensing, a queue sort that
 *      knows who has paid — each is a routine-looking line, and each turns a
 *      bill into a condition of care. A file that cannot spell the table
 *      cannot write that line.
 *
 *   2. No billing file may name a visit, a triage record, a queue ticket, a
 *      consultation, a prescription or a lab order. The desk raises invoices
 *      against a PATIENT; it does not reach into the record to find out what
 *      was done, and it does not write back into it.
 *
 *   3. No clinical or queue table carries an invoice/payment column, because
 *      that is the column a NOT NULL constraint turns into a gate.
 *
 *   4. No care route is gated on the billing capability.
 *
 * If this test ever fails, the right response is almost certainly to remove
 * the reference, not to amend the test.
 */
class BillingIsolationGuardTest extends TestCase
{
    /** Files that decide who is seen, when, and what they get. */
    private const CLINICAL_AND_QUEUE = [
        'app/Services/Triage',
        'app/Services/Queue',
        'app/Services/Clinical',
        'app/Services/Lab',
        'app/Services/Pharmacy',
        'app/Services/Preconsult',
        'app/Services/Chart',
        'app/Http/Controllers/MedicalId/TriageDeskController.php',
        'app/Http/Controllers/MedicalId/QueueDeskController.php',
        'app/Http/Controllers/MedicalId/PrescriberController.php',
        'app/Http/Controllers/MedicalId/LabDeskWebController.php',
        'app/Http/Controllers/MedicalId/PharmacyPortalController.php',
        'app/Http/Controllers/MedicalId/PatientChartController.php',
        'app/Support/ClinicalRelationshipGate.php',
        'app/Modules/Governance/Services/EmergencyAccessService.php',
        'routes/triage.php',
        'routes/queue.php',
        'routes/lab.php',
        'routes/chart.php',
    ];

    /** The billing desk. */
    private const BILLING = [
        'app/Services/Billing/BillingDeskService.php',
        'app/Services/Billing/MobileMoneyCounterService.php',
        'app/Services/Billing/BillingRefusal.php',
        'app/Services/Billing/Xaf.php',
        'app/Http/Controllers/MedicalId/BillingDeskController.php',
        'routes/billing.php',
    ];

    /** Words that mean "money owed or paid". */
    private const BILLING_NEEDLES = [
        '/\bInvoice\b/', "/'invoices'/", '/\bPayment\b/', "/'payments'/",
        '/\bRefund\b/', "/'refunds'/", '/cashier_sessions/', '/CashierSession/',
        '/BillingDeskService/', '/MobileMoneyCounterService/', '/balance_amount/',
    ];

    /** Words that mean "the record, the queue, the act of care". */
    private const CLINICAL_NEEDLES = [
        "/'visits'/", '/\bVisit\b/', '/triage_records/', '/TriageRecord/',
        '/queue_tickets/', '/QueueTicket/', "/'consultations'/", '/\bConsultation\b/',
        "/'prescriptions'/", '/\bPrescription\b/', '/lab_orders/', '/LabOrder/',
        '/lab_results/', '/LabResult/', '/TriageDeskService/', '/PatientQueueService/',
        '/PrescriptionService/', '/LabDeskService/', '/DispensingService/',
    ];

    /** Tables whose rows are care. */
    private const CLINICAL_TABLES = [
        'visits', 'triage_records', 'consultations', 'prescriptions', 'lab_orders',
        'lab_results', 'diagnoses', 'vital_signs', 'queue_tickets', 'dispensing_records',
    ];

    public function test_no_clinical_or_queue_file_can_spell_a_bill(): void
    {
        $this->assertNoNeedles(self::CLINICAL_AND_QUEUE, self::BILLING_NEEDLES,
            'A file that decides care can name an invoice or a payment — the first step to gating care on one.');
    }

    public function test_no_billing_file_can_spell_the_record_or_the_queue(): void
    {
        $this->assertNoNeedles(self::BILLING, self::CLINICAL_NEEDLES,
            'The billing desk can reach a clinical or queue table. It bills a patient; it does not touch the record.');
    }

    public function test_no_clinical_table_carries_an_invoice_or_payment_column(): void
    {
        $offences = [];

        foreach (self::CLINICAL_TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (Schema::getColumnListing($table) as $column) {
                foreach (['invoice', 'payment', 'paid', 'billing', 'balance'] as $word) {
                    if (str_contains($column, $word)) {
                        $offences[] = "{$table}.{$column}";
                    }
                }
            }
        }

        $this->assertSame([], $offences, 'A clinical table gained a money column: '.implode(', ', $offences));
    }

    public function test_no_care_route_is_behind_the_billing_capability(): void
    {
        $offences = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (str_starts_with($uri, 'facility/billing')) {
                continue;
            }

            $isCare = str_starts_with($uri, 'api/mobile')
                || str_starts_with($uri, 'patient/')
                || str_starts_with($uri, 'facility/triage')
                || str_starts_with($uri, 'facility/queue')
                || str_starts_with($uri, 'facility/lab')
                || str_starts_with($uri, 'facility/chart')
                || str_starts_with($uri, 'facility/prescriber')
                || str_starts_with($uri, 'facility/pharmacy');

            if (! $isCare) {
                continue;
            }

            foreach ($route->gatherMiddleware() as $middleware) {
                if (is_string($middleware) && preg_match('/^capability:.*\b(billing|refund_approve)\b/', $middleware)) {
                    $offences[] = sprintf('%s  [%s]', $uri, $middleware);
                }
            }
        }

        $this->assertSame([], $offences, "A care route is gated on the billing capability:\n  ".implode("\n  ", $offences));
    }

    /**
     * @param  list<string>  $paths     files or directories, relative to base_path()
     * @param  list<string>  $needles   regexes
     */
    private function assertNoNeedles(array $paths, array $needles, string $why): void
    {
        $offences = [];
        $seen     = 0;

        foreach ($paths as $relative) {
            $path = base_path($relative);

            if (! file_exists($path)) {
                // Moved or renamed: repoint rather than silently pass.
                $offences[] = $relative.' (missing — repoint this test)';
                continue;
            }

            foreach (is_dir($path) ? $this->phpFilesUnder($path) : [$path] as $file) {
                $seen++;
                $code = $this->stripComments((string) file_get_contents($file));
                $rel  = str_replace('\\', '/', substr($file, strlen(base_path()) + 1));

                foreach ($needles as $needle) {
                    if (preg_match($needle, $code)) {
                        $offences[] = sprintf('%s matches %s', $rel, $needle);
                    }
                }
            }
        }

        $this->assertGreaterThan(0, $seen, 'The guard scanned nothing.');
        $this->assertSame([], $offences, $why."\nOffenders:\n  ".implode("\n  ", $offences));
    }

    /** @return list<string> */
    private function phpFilesUnder(string $dir): array
    {
        $files = [];
        $it    = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));

        foreach ($it as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /** Strip PHP comments so a docblock explaining the rule is not read as breaking it. */
    private function stripComments(string $source): string
    {
        $out = '';

        foreach (token_get_all($source) as $token) {
            if (is_array($token)) {
                if (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }
                $out .= $token[1];
            } else {
                $out .= $token;
            }
        }

        return $out;
    }
}

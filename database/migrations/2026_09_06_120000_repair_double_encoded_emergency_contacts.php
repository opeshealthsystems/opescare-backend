<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Repair emergency contacts that were stored as a JSON string of a JSON string.
 *
 * `patients.emergency_contact` is an `array` cast, but two registration paths
 * called json_encode() before the write. The column therefore held an encoded
 * string, the cast decoded it once, and every reader — which checks for an
 * array — saw text and showed nothing. A patient who gave an emergency contact
 * at registration had no emergency contact on the page where it matters most.
 *
 * The writers are fixed; this repairs the rows they already wrote. It only
 * touches rows that decode to a string and then decode again to an array, so a
 * correctly stored contact is left exactly as it is, and running it twice
 * changes nothing the second time.
 */
return new class extends Migration
{
    public function up(): void
    {
        $repaired = 0;

        DB::table('patients')
            ->whereNotNull('emergency_contact')
            ->select('id', 'emergency_contact')
            ->orderBy('id')
            ->chunk(500, function ($rows) use (&$repaired) {
                foreach ($rows as $row) {
                    $once = json_decode((string) $row->emergency_contact, true);

                    if (! is_string($once)) {
                        continue; // already a well-formed contact
                    }

                    $twice = json_decode($once, true);

                    if (! is_array($twice)) {
                        continue; // not the double-encoding case; leave it alone
                    }

                    DB::table('patients')
                        ->where('id', $row->id)
                        ->update(['emergency_contact' => json_encode($twice)]);

                    $repaired++;
                }
            });

        if ($repaired > 0) {
            echo "repaired {$repaired} double-encoded emergency contact(s)\n";
        }
    }

    public function down(): void
    {
        // Re-breaking correctly stored data would be the only thing to undo
        // here, and there is no reason to want it.
    }
};

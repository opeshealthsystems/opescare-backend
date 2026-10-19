<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Synthetic NATIONAL-VOLUME data for EXPLAIN / timing work. TEST DATABASES ONLY.
 *
 *   DB_DATABASE=opescare_finder_test_a5 php artisan db:seed --class=ScaleSeeder --force
 *
 * Refuses to run unless the connected database name contains "_test" or "_scale" - it
 * must never touch dev (opescare_finder) or prod. Rows are generated in SQL
 * with generate_series rather than one Eloquent factory call per row: 500k
 * factory saves take hours, set-based inserts take seconds, and the only
 * thing being measured is the planner's behaviour at volume. Every row is
 * tagged source_system='scale_synthetic' (NOT on the availability
 * allow-list), so even if it leaked it could never surface as live stock.
 *
 * Volumes are env-tunable: SCALE_FACILITIES (default 20000), SCALE_PATIENTS
 * (default 100000).
 */
class ScaleSeeder extends Seeder
{
    public function run(): void
    {
        $db = (string) DB::connection()->getDatabaseName();
        if (! str_contains($db, '_test') && ! str_contains($db, '_scale')) {
            throw new RuntimeException("ScaleSeeder refuses to run on '{$db}': test or scale databases only.");
        }
        if (DB::getDriverName() !== 'pgsql') {
            throw new RuntimeException('ScaleSeeder targets PostgreSQL.');
        }

        $facilities = (int) (env('SCALE_FACILITIES') ?: 20000);
        $patients   = (int) (env('SCALE_PATIENTS') ?: 100000);
        $tenants    = 2000;

        // Cameroon bounding box: lat 1.6..13.1, lng 8.4..16.2.
        DB::statement("
            INSERT INTO care_facilities (id, facility_name, facility_type, city, address, phone_primary,
                listing_status, latitude, longitude, source_system, description, created_at, updated_at)
            SELECT gen_random_uuid(),
                   (ARRAY['Pharmacie','Clinique','Hopital','Centre de Sante','Laboratoire'])[1 + g % 5] || ' Synth ' || g,
                   (ARRAY['pharmacy','clinic','hospital','health_center','laboratory'])[1 + g % 5],
                   (ARRAY['Yaounde','Douala','Bamenda','Garoua','Bafoussam','Maroua','Ngaoundere','Bertoua','Buea','Ebolowa'])[1 + g % 10],
                   'Synthetic address ' || g, '+2376' || lpad((g % 100000000)::text, 8, '0'),
                   CASE WHEN g % 20 = 0 THEN 'inactive' ELSE 'active' END,
                   1.6 + random() * 11.5, 8.4 + random() * 7.8, 'scale_synthetic',
                   'Synthetic facility for scale testing', now(), now()
            FROM generate_series(1, {$facilities}) g
        ");

        DB::statement("
            INSERT INTO facilities (id, name, type, created_at, updated_at)
            SELECT gen_random_uuid(), 'Synth Tenant ' || g, 'hospital', now(), now()
            FROM generate_series(1, {$tenants}) g
        ");

        DB::statement("
            INSERT INTO patients (id, health_id, first_name, last_name, created_at, updated_at)
            SELECT gen_random_uuid(), 'SYN-' || lpad(g::text, 9, '0'), 'Synth', 'Patient ' || g, now(), now()
            FROM generate_series(1, {$patients}) g
        ");

        DB::statement("
            INSERT INTO health_id_aliases (patient_id, alias_type, alias_value, status, created_at, updated_at)
            SELECT id, 'merged_health_id', 'ALIAS-' || health_id, 'active', now(), now()
            FROM patients WHERE health_id LIKE 'SYN-%' AND random() < 0.2
        ");

        DB::statement("DROP TABLE IF EXISTS scale_tenants");
        DB::statement("
            CREATE TEMP TABLE scale_tenants AS
            SELECT id, row_number() OVER () AS n FROM facilities WHERE name LIKE 'Synth Tenant %'
        ");
        // Without a key + stats the planner nested-loops 500k x 2000.
        DB::statement('CREATE UNIQUE INDEX ON scale_tenants (n)');
        DB::statement('ANALYZE scale_tenants');
        $n = (int) DB::scalar('SELECT count(*) FROM scale_tenants');

        DB::statement("
            INSERT INTO consent_grants (id, patient_id, facility_id, authorizing_actor, scope, status, expires_at, created_at, updated_at)
            SELECT gen_random_uuid(), p.id, t.id, 'patient', '[\"records\"]'::jsonb,
                   CASE WHEN random() < 0.7 THEN 'active' ELSE 'revoked' END, now() + interval '30 days', now(), now()
            FROM patients p JOIN scale_tenants t ON t.n = 1 + (abs(hashtext(p.id::text)) % {$n})
            WHERE p.health_id LIKE 'SYN-%'
        ");

        // Access-log actors must be real users (FK); 500 synthetic staff.
        DB::statement("
            INSERT INTO users (id, name, email, password, status, created_at, updated_at)
            SELECT gen_random_uuid(), 'Synth Staff ' || g, 'synth.staff.' || g || '@scale.test', 'x', 'active', now(), now()
            FROM generate_series(1, 500) g
        ");
        DB::statement("DROP TABLE IF EXISTS scale_users");
        DB::statement("CREATE TEMP TABLE scale_users AS SELECT id, row_number() OVER () AS n FROM users WHERE email LIKE 'synth.staff.%@scale.test'");
        DB::statement('CREATE UNIQUE INDEX ON scale_users (n)');
        DB::statement('ANALYZE scale_users');

        DB::statement("
            INSERT INTO access_logs (id, patient_id, actor_id, actor_type, facility_id, purpose, data_category, resource_type, access_type, created_at)
            SELECT gen_random_uuid(), p.id, u.id, 'user', t.id, 'treatment', 'clinical', 'patient', 'read',
                   now() - (random() * interval '365 days')
            FROM patients p CROSS JOIN generate_series(1, 2) k
            JOIN scale_tenants t ON t.n = 1 + (abs(hashtext(p.id::text || k::text)) % {$n})
            JOIN scale_users u ON u.n = 1 + (abs(hashtext(p.id::text || 'u' || k::text)) % 500)
            WHERE p.health_id LIKE 'SYN-%'
        ");

        // One visit + two encounters per patient (chart load), one queue
        // ticket per 4 patients (queue desk), three notifications per staff user.
        DB::statement("
            INSERT INTO visits (id, patient_id, facility_id, visit_type, status, started_at, created_at, updated_at)
            SELECT gen_random_uuid(), p.id, t.id, 'outpatient', 'closed', now() - random() * interval '365 days', now(), now()
            FROM patients p JOIN scale_tenants t ON t.n = 1 + (abs(hashtext(p.id::text)) % {$n})
            WHERE p.health_id LIKE 'SYN-%'
        ");
        DB::statement("
            INSERT INTO encounters (id, visit_id, patient_id, facility_id, encounter_type, status, started_at, created_at, updated_at)
            SELECT gen_random_uuid(), v.id, v.patient_id, v.facility_id, 'consultation', 'completed',
                   v.started_at + k * interval '1 hour', now(), now()
            FROM visits v CROSS JOIN generate_series(0, 1) k
            JOIN patients p ON p.id = v.patient_id WHERE p.health_id LIKE 'SYN-%'
        ");
        DB::statement("
            INSERT INTO queue_tickets (id, patient_id, facility_id, queue_number, current_queue, status, priority_level, checked_in_at, created_at, updated_at)
            SELECT gen_random_uuid(), p.id, t.id, 'S' || row_number() OVER (), 'triage',
                   (ARRAY['waiting','called','completed','completed'])[1 + abs(hashtext(p.id::text || 'q')) % 4],
                   5, now() - random() * interval '30 days', now() - random() * interval '30 days', now()
            FROM patients p JOIN scale_tenants t ON t.n = 1 + (abs(hashtext(p.id::text)) % {$n})
            WHERE p.health_id LIKE 'SYN-%' AND abs(hashtext(p.id::text || 'qq')) % 4 = 0
        ");
        DB::statement("
            INSERT INTO notifications (id, type, notifiable_type, notifiable_id, data, read_at, created_at, updated_at)
            SELECT gen_random_uuid(), 'App\Notifications\Synthetic', 'App\Models\User', u.id::text, '{}',
                   CASE WHEN k % 3 = 0 THEN NULL ELSE now() END, now() - k * interval '1 hour', now()
            FROM scale_users u CROSS JOIN generate_series(1, 60) k
        ");

        DB::statement("
            INSERT INTO appointments (id, patient_id, facility_id, appointment_type, status, scheduled_at, created_at, updated_at)
            SELECT gen_random_uuid(), p.id, t.id, 'consultation', 'scheduled',
                   now() + ((random() * 120 - 60) * interval '1 day'), now(), now()
            FROM patients p JOIN scale_tenants t ON t.n = 1 + (abs(hashtext(p.id::text)) % {$n})
            WHERE p.health_id LIKE 'SYN-%'
        ");

        DB::statement("
            INSERT INTO medicines (id, name, generic_name, category, is_active, created_at, updated_at)
            SELECT gen_random_uuid(), 'SynthMed ' || g || ' ' || (ARRAY['Paracetamol','Ibuprofen','Amoxicillin','Artemether','Metformin'])[1 + g % 5],
                   (ARRAY['paracetamol','ibuprofen','amoxicillin','artemether','metformin'])[1 + g % 5], 'synthetic', true, now(), now()
            FROM generate_series(1, 2000) g
        ");

        // ~10 stock lines per pharmacy: 10 consecutive medicines from a per-facility offset.
        DB::statement("
            WITH meds AS (SELECT id, row_number() OVER (ORDER BY id) - 1 AS n FROM medicines WHERE category = 'synthetic'),
                 cnt AS (SELECT count(*) AS c FROM meds),
                 ph AS (SELECT id, abs(hashtext(id::text)) AS h FROM care_facilities
                        WHERE facility_type = 'pharmacy' AND source_system = 'scale_synthetic')
            INSERT INTO medicine_pharmacy_stocks (id, medicine_id, care_facility_id, stock_status, source_system, last_reported_at, created_at, updated_at)
            SELECT gen_random_uuid(), m.id, ph.id, 'in_stock', 'scale_synthetic', now() - random() * interval '10 days', now(), now()
            FROM ph CROSS JOIN generate_series(0, 9) k CROSS JOIN cnt
            JOIN meds m ON m.n = (ph.h + k) % cnt.c
        ");

        DB::statement("
            INSERT INTO blood_availability (id, facility_id, blood_group, component_type, availability_status, source_system, last_updated_at, created_at, updated_at)
            SELECT gen_random_uuid(), f.id, bg, 'whole_blood', 'available', 'scale_synthetic', now(), now(), now()
            FROM care_facilities f CROSS JOIN unnest(ARRAY['O+','O-','A+','B+']) bg
            WHERE f.facility_type = 'hospital' AND f.source_system = 'scale_synthetic'
        ");

        DB::statement('ANALYZE');
    }
}

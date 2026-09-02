<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\User;

class DemoUsersSeeder extends Seeder {
    public function run(): void {
        $users = [
            // ── Clinical Staff ────────────────────────────────────────────────
            ['id' => '00000000-0000-0000-0000-200000000001', 'name' => 'Dr. Amara Diallo',        'email' => 'demo.doctor@opescare.test',          'password' => bcrypt('DemoPass!2026'), 'is_demo' => true],
            ['id' => '00000000-0000-0000-0000-200000000002', 'name' => 'Dr. Kofi Mensah',          'email' => 'demo.multi.doctor@opescare.test',    'password' => bcrypt('DemoPass!2026'), 'is_demo' => true],
            ['id' => '00000000-0000-0000-0000-200000000010', 'name' => 'Nurse Fatou Traoré',       'email' => 'demo.nurse@opescare.test',           'password' => bcrypt('DemoPass!2026'), 'is_demo' => true],
            ['id' => '00000000-0000-0000-0000-200000000011', 'name' => 'Dr. Ibrahim Sow',          'email' => 'demo.specialist@opescare.test',      'password' => bcrypt('DemoPass!2026'), 'is_demo' => true],
            ['id' => '00000000-0000-0000-0000-200000000012', 'name' => 'Pharmacist Aïcha Coulibaly','email' => 'demo.pharmacist@opescare.test',      'password' => bcrypt('DemoPass!2026'), 'is_demo' => true],
            ['id' => '00000000-0000-0000-0000-200000000013', 'name' => 'Lab Tech Boubacar Keïta',  'email' => 'demo.labtech@opescare.test',         'password' => bcrypt('DemoPass!2026'), 'is_demo' => true],

            // ── Facility Admin / Management ───────────────────────────────────
            ['id' => '00000000-0000-0000-0000-200000000020', 'name' => 'Admin Mariam Touré',       'email' => 'demo.facility.admin@opescare.test', 'password' => bcrypt('DemoPass!2026'), 'is_demo' => true],
            ['id' => '00000000-0000-0000-0000-200000000021', 'name' => 'CEO Seydou Ouédraogo',     'email' => 'demo.facility.ceo@opescare.test',   'password' => bcrypt('DemoPass!2026'), 'is_demo' => true],
            ['id' => '00000000-0000-0000-0000-200000000022', 'name' => 'Finance Officer Kadiatou', 'email' => 'demo.finance@opescare.test',         'password' => bcrypt('DemoPass!2026'), 'is_demo' => true],

            // ── Insurance ─────────────────────────────────────────────────────
            ['id' => '00000000-0000-0000-0000-200000000030', 'name' => 'Claims Officer Oumar Ba', 'email' => 'demo.insurance@opescare.test',       'password' => bcrypt('DemoPass!2026'), 'is_demo' => true],
            ['id' => '00000000-0000-0000-0000-200000000031', 'name' => 'Preauth Reviewer Awa',    'email' => 'demo.preauth@opescare.test',         'password' => bcrypt('DemoPass!2026'), 'is_demo' => true],

            // ── Patient & Guardian ────────────────────────────────────────────
            ['id' => '00000000-0000-0000-0000-200000000003', 'name' => 'Patient Jean Dupont',      'email' => 'demo.patient@opescare.test',         'password' => bcrypt('DemoPass!2026'), 'is_demo' => true],
            ['id' => '00000000-0000-0000-0000-200000000004', 'name' => 'Guardian Marie Dupont',    'email' => 'demo.guardian@opescare.test',        'password' => bcrypt('DemoPass!2026'), 'is_demo' => true],

            // ── Platform Admin ────────────────────────────────────────────────
            ['id' => '00000000-0000-0000-0000-200000000040', 'name' => 'Platform Admin',           'email' => 'demo.admin@opescare.test',           'password' => bcrypt('DemoPass!2026'), 'is_demo' => true],
        ];

        foreach ($users as $u) {
            User::firstOrCreate(['email' => $u['email']], $u);
        }
    }
}

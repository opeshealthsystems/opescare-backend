<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds demo staff profiles and department assignments for the HR module.
 * Idempotent – safe to run multiple times.
 */
class DemoDepartmentsSeeder extends Seeder
{
    private const FAC = '00000000-0000-0000-0000-100000000001';

    public function run(): void
    {
        $staff = [
            [
                'id'              => '00000000-0000-0000-0017-100000000001',
                'user_id'         => '00000000-0000-0000-0000-200000000001',
                'employee_number' => 'EMP-DOC-001',
                'first_name'      => 'Amara',
                'last_name'       => 'Diallo',
                'email'           => 'demo.doctor@opescare.test',
                'job_title'       => 'General Practitioner',
                'department'      => 'Outpatient Clinic',
                'staff_category'  => 'clinical',
                'employment_type' => 'full_time',
                'hire_date'       => '2023-01-15',
            ],
            [
                'id'              => '00000000-0000-0000-0017-100000000002',
                'user_id'         => '00000000-0000-0000-0000-200000000010',
                'employee_number' => 'EMP-NUR-001',
                'first_name'      => 'Fatou',
                'last_name'       => 'Traoré',
                'email'           => 'demo.nurse@opescare.test',
                'job_title'       => 'Registered Nurse',
                'department'      => 'Emergency & Triage',
                'staff_category'  => 'clinical',
                'employment_type' => 'full_time',
                'hire_date'       => '2022-06-01',
            ],
            [
                'id'              => '00000000-0000-0000-0017-100000000003',
                'user_id'         => '00000000-0000-0000-0000-200000000011',
                'employee_number' => 'EMP-SPE-001',
                'first_name'      => 'Ibrahim',
                'last_name'       => 'Sow',
                'email'           => 'demo.specialist@opescare.test',
                'job_title'       => 'Cardiologist',
                'department'      => 'Cardiology',
                'staff_category'  => 'clinical',
                'employment_type' => 'full_time',
                'hire_date'       => '2021-03-10',
            ],
            [
                'id'              => '00000000-0000-0000-0017-100000000004',
                'user_id'         => '00000000-0000-0000-0000-200000000012',
                'employee_number' => 'EMP-PHA-001',
                'first_name'      => 'Aïcha',
                'last_name'       => 'Coulibaly',
                'email'           => 'demo.pharmacist@opescare.test',
                'job_title'       => 'Clinical Pharmacist',
                'department'      => 'Pharmacy',
                'staff_category'  => 'clinical',
                'employment_type' => 'full_time',
                'hire_date'       => '2022-09-01',
            ],
            [
                'id'              => '00000000-0000-0000-0017-100000000005',
                'user_id'         => '00000000-0000-0000-0000-200000000013',
                'employee_number' => 'EMP-LAB-001',
                'first_name'      => 'Boubacar',
                'last_name'       => 'Keïta',
                'email'           => 'demo.labtech@opescare.test',
                'job_title'       => 'Senior Lab Technician',
                'department'      => 'Laboratory',
                'staff_category'  => 'clinical',
                'employment_type' => 'full_time',
                'hire_date'       => '2023-04-01',
            ],
            [
                'id'              => '00000000-0000-0000-0017-100000000006',
                'user_id'         => '00000000-0000-0000-0000-200000000020',
                'employee_number' => 'EMP-ADM-001',
                'first_name'      => 'Mariam',
                'last_name'       => 'Touré',
                'email'           => 'demo.facility.admin@opescare.test',
                'job_title'       => 'Facility Administrator',
                'department'      => 'Administration',
                'staff_category'  => 'administrative',
                'employment_type' => 'full_time',
                'hire_date'       => '2020-11-15',
            ],
            [
                'id'              => '00000000-0000-0000-0017-100000000007',
                'user_id'         => '00000000-0000-0000-0000-200000000022',
                'employee_number' => 'EMP-FIN-001',
                'first_name'      => 'Kadiatou',
                'last_name'       => 'Bah',
                'email'           => 'demo.finance@opescare.test',
                'job_title'       => 'Finance Officer',
                'department'      => 'Finance',
                'staff_category'  => 'administrative',
                'employment_type' => 'full_time',
                'hire_date'       => '2021-07-20',
            ],
        ];

        foreach ($staff as $s) {
            if (DB::table('staff_profiles')->where('id', $s['id'])->doesntExist()) {
                DB::table('staff_profiles')->insert([
                    'id'              => $s['id'],
                    'user_id'         => $s['user_id'],
                    'facility_id'     => self::FAC,
                    'employee_number' => $s['employee_number'],
                    'first_name'      => $s['first_name'],
                    'last_name'       => $s['last_name'],
                    'email'           => $s['email'],
                    'job_title'       => $s['job_title'],
                    'department'      => $s['department'],
                    'staff_category'  => $s['staff_category'],
                    'employment_type' => $s['employment_type'],
                    'hire_date'       => $s['hire_date'],
                    'status'          => 'active',
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        }
    }
}

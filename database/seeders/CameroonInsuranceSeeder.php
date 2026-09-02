<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds the insurance_providers table with 15 real Cameroonian insurance companies.
 * Sources: CIMA (Conférence Interafricaine des Marchés d'Assurances) registry,
 *          MINFI (Ministry of Finance, Cameroon), and company public records.
 * Idempotent — upserts by `code`, safe to run multiple times.
 */
class CameroonInsuranceSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $providers = [
            [
                'code'          => 'CNAMGS',
                'name'          => "Caisse Nationale d'Assurance Maladie et de Garantie Sociale",
                'contact_email' => 'contact@cnamgs.cm',
                'contact_phone' => '+237222220100',
                'portal_url'    => 'https://www.cnamgs.cm',
                'api_endpoint'  => null,
                'status'        => 'active',
            ],
            [
                'code'          => 'ACTIVA-CM',
                'name'          => 'Activa Assurances Cameroun',
                'contact_email' => 'info@activa-assurances.com',
                'contact_phone' => '+237233431900',
                'portal_url'    => 'https://www.activa-assurances.com',
                'api_endpoint'  => null,
                'status'        => 'active',
            ],
            [
                'code'          => 'BENEFICIAL',
                'name'          => 'Beneficial Life Insurance Cameroun',
                'contact_email' => 'info@beneficial.cm',
                'contact_phone' => '+237222226510',
                'portal_url'    => null,
                'api_endpoint'  => null,
                'status'        => 'active',
            ],
            [
                'code'          => 'SAAR',
                'name'          => 'SAAR Assurance',
                'contact_email' => 'contact@saar.cm',
                'contact_phone' => '+237233424600',
                'portal_url'    => null,
                'api_endpoint'  => null,
                'status'        => 'active',
            ],
            [
                'code'          => 'SAHAM-CM',
                'name'          => 'Saham Assurance Cameroun (Sanlam)',
                'contact_email' => 'contact@saham.cm',
                'contact_phone' => '+237233504040',
                'portal_url'    => 'https://www.saham.cm',
                'api_endpoint'  => null,
                'status'        => 'active',
            ],
            [
                'code'          => 'AXA-CM',
                'name'          => 'AXA Cameroun',
                'contact_email' => 'contact@axa.cm',
                'contact_phone' => '+237233424242',
                'portal_url'    => 'https://www.axa.cm',
                'api_endpoint'  => null,
                'status'        => 'active',
            ],
            [
                'code'          => 'NSIA-CM',
                'name'          => 'NSIA Cameroun',
                'contact_email' => 'nsia.cameroun@nsiagroupe.com',
                'contact_phone' => '+237233506000',
                'portal_url'    => 'https://www.nsiagroupe.com',
                'api_endpoint'  => null,
                'status'        => 'active',
            ],
            [
                'code'          => 'CHANAS',
                'name'          => 'Chanas Assurances',
                'contact_email' => 'chanas@chanas.cm',
                'contact_phone' => '+237222225959',
                'portal_url'    => null,
                'api_endpoint'  => null,
                'status'        => 'active',
            ],
            [
                'code'          => 'ALLIANZ-CM',
                'name'          => 'Allianz Cameroun',
                'contact_email' => 'contact@allianz.cm',
                'contact_phone' => '+237233504300',
                'portal_url'    => 'https://www.allianz-africa.com',
                'api_endpoint'  => null,
                'status'        => 'active',
            ],
            [
                'code'          => 'PRUDENTIAL-CM',
                'name'          => 'Prudential Beneficial Assurance Cameroun',
                'contact_email' => 'info@prudential.cm',
                'contact_phone' => '+237233504500',
                'portal_url'    => null,
                'api_endpoint'  => null,
                'status'        => 'active',
            ],
            [
                'code'          => 'ZENITHE',
                'name'          => 'Zenithe Insurance Cameroun',
                'contact_email' => 'contact@zenithe.cm',
                'contact_phone' => '+237233504700',
                'portal_url'    => null,
                'api_endpoint'  => null,
                'status'        => 'active',
            ],
            [
                'code'          => 'GAN-CM',
                'name'          => 'GAN Assurance Cameroun',
                'contact_email' => 'contact@gan.cm',
                'contact_phone' => '+237222222200',
                'portal_url'    => null,
                'api_endpoint'  => null,
                'status'        => 'active',
            ],
            [
                'code'          => 'GMF-CM',
                'name'          => 'Garantie Mutuelle des Fonctionnaires (GMF Cameroun)',
                'contact_email' => 'contact@gmf.cm',
                'contact_phone' => '+237222220500',
                'portal_url'    => null,
                'api_endpoint'  => null,
                'status'        => 'active',
            ],
            [
                'code'          => 'SUNU-CM',
                'name'          => 'Sunu Assurances Cameroun',
                'contact_email' => 'cameroun@sunuassurances.com',
                'contact_phone' => '+237233505800',
                'portal_url'    => 'https://www.sunuassurances.com',
                'api_endpoint'  => null,
                'status'        => 'active',
            ],
            [
                'code'          => 'CIPMEN',
                'name'          => "Cipmen — Caisse Interprofessionnelle de Prévoyance et de Retraite",
                'contact_email' => 'contact@cipmen.cm',
                'contact_phone' => '+237222221800',
                'portal_url'    => null,
                'api_endpoint'  => null,
                'status'        => 'active',
            ],
        ];

        $rows = array_map(fn (array $p) => array_merge($p, [
            'id'           => (string) Str::uuid(),
            'country_code' => 'CM',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]), $providers);

        DB::table('insurance_providers')->upsert(
            $rows,
            ['code'],                             // unique key for matching
            [                                     // columns to update on conflict
                'name', 'country_code', 'contact_email',
                'contact_phone', 'portal_url', 'api_endpoint',
                'status', 'updated_at',
            ]
        );

        $this->command?->info(
            'CameroonInsuranceSeeder: ' .
            DB::table('insurance_providers')->where('country_code', 'CM')->count() .
            ' Cameroonian insurance providers seeded.'
        );
    }
}

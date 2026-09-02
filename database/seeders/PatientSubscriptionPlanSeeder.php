<?php

namespace Database\Seeders;

use App\Models\PlanFeature;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

/**
 * Seeds the B2C patient subscription catalog: Free (Essentiel) and Premium (Santé+).
 *
 * Prices are PLACEHOLDER XAF figures (see the design spec) — adjust before launch.
 * price_kobo / annual_price_kobo follow the existing minor-unit convention
 * (XAF amount × 100, so priceFormatted()/100 renders the major-unit figure).
 */
class PatientSubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        // ── Free: Essentiel ──────────────────────────────────────────────────
        $free = SubscriptionPlan::updateOrCreate(
            ['slug' => 'patient-free'],
            [
                'name'              => 'Essentiel',
                'audience'          => 'patient',
                'billing_cycle'     => 'monthly',
                'price_kobo'        => 0,
                'annual_price_kobo' => 0,
                'currency'          => 'XAF',
                'description'       => 'Compte patient gratuit : vos dossiers, identifiant santé QR, recherche d’établissement et prise de rendez-vous de base.',
                'is_active'         => true,
                'is_public'         => true,
                'trial_days'        => 0,
                'sort_order'        => 0,
            ]
        );
        $this->syncFeatures($free, [
            ['own_records',     'Accès à vos propres dossiers',        'boolean', null],
            ['qr_health_id',    'Identifiant santé QR',                'boolean', null],
            ['facility_search', 'Recherche d’établissements',          'boolean', null],
            ['basic_booking',   'Prise de rendez-vous de base',        'boolean', null],
        ]);

        // ── Premium: Santé+ ──────────────────────────────────────────────────
        $premium = SubscriptionPlan::updateOrCreate(
            ['slug' => 'patient-premium'],
            [
                'name'              => 'Santé+',
                'audience'          => 'patient',
                'billing_cycle'     => 'monthly',
                'price_kobo'        => 150000,    // 1,500 XAF / month (placeholder)
                'annual_price_kobo' => 1500000,   // 15,000 XAF / year  (placeholder, ~2 months free)
                'currency'          => 'XAF',
                'description'       => 'Téléconsultations, historique complet, coffre de documents, file prioritaire, partage familial et rappels.',
                'is_active'         => true,
                'is_public'         => true,
                'trial_days'        => 0,
                'sort_order'        => 1,
            ]
        );
        $this->syncFeatures($premium, [
            ['teleconsult',    'Téléconsultations',                    'boolean', null],
            ['full_history',   'Historique médical complet',           'boolean', null],
            ['document_vault', 'Coffre-fort de documents',             'boolean', null],
            ['priority_queue', 'File d’attente prioritaire',           'boolean', null],
            ['family_sharing', 'Partage familial (jusqu’à 5)',         'count',   5],
            ['med_reminders',  'Rappels de médicaments',               'boolean', null],
            ['offline_card',   'Carte santé hors ligne',               'boolean', null],
        ]);
    }

    /**
     * @param array<int, array{0:string,1:string,2:string,3:?int}> $features
     */
    private function syncFeatures(SubscriptionPlan $plan, array $features): void
    {
        foreach ($features as [$key, $label, $limitType, $limitValue]) {
            PlanFeature::updateOrCreate(
                ['plan_id' => $plan->id, 'feature_key' => $key],
                ['feature_label' => $label, 'limit_type' => $limitType, 'limit_value' => $limitValue],
            );
        }
    }
}

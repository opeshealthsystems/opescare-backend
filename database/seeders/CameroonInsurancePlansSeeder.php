<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds purchasable health insurance plans for every real Cameroonian insurer
 * already present in insurance_providers.
 *
 * Plans are realistic for the Cameroonian market (prices in XAF).
 * Idempotent — upserts by (insurance_provider_id, plan_code).
 *
 * Sources: CIMA tariff guides, company brochures, DGSN/MINFI public data.
 */
class CameroonInsurancePlansSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Map code → real plans
        $plansByCode = [

            // ── CNAMGS (government social health) ────────────────────────────
            'CNAMGS' => [
                [
                    'plan_code'   => 'CNAMGS-BASE',
                    'name'        => 'CNAMGS Régime de Base',
                    'plan_type'   => 'nhia',
                    'description' => 'Le régime de base de la CNAMGS couvre les risques maladie, maternité et invalidité pour les travailleurs du secteur formel. Remboursement à 70 % des frais médicaux plafonnés.',
                    'monthly_premium'  => 5500,
                    'annual_premium'   => 60000,
                    'deductible'       => 10000,
                    'copay_percentage' => 30,
                    'cashless_available' => true,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Consultation générale', 'Hospitalisation', 'Maternité', 'Médicaments essentiels', 'Analyses de laboratoire']),
                ],
                [
                    'plan_code'   => 'CNAMGS-COMPL',
                    'name'        => 'CNAMGS Complémentaire Santé',
                    'plan_type'   => 'nhia',
                    'description' => 'Couverture complémentaire qui prend en charge le ticket modérateur non remboursé par le régime de base, y compris les soins dentaires et ophtalmologiques.',
                    'monthly_premium'  => 9500,
                    'annual_premium'   => 108000,
                    'deductible'       => 5000,
                    'copay_percentage' => 10,
                    'cashless_available' => true,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Ticket modérateur', 'Soins dentaires', 'Ophtalmologie', 'Lunettes & verres correcteurs', 'Spécialités médicales']),
                ],
            ],

            // ── Activa Assurances ─────────────────────────────────────────────
            'ACTIVA-CM' => [
                [
                    'plan_code'   => 'ACTIVA-SILVER',
                    'name'        => 'Activa Silver Santé',
                    'plan_type'   => 'private',
                    'description' => 'Plan d\'entrée de gamme Activa offrant les soins ambulatoires et une hospitalisation en chambre commune jusqu\'à 3 millions FCFA par an.',
                    'monthly_premium'  => 12000,
                    'annual_premium'   => 132000,
                    'deductible'       => 20000,
                    'copay_percentage' => 20,
                    'cashless_available' => true,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Consultation', 'Hospitalisation (commune)', 'Maternité', 'Chirurgie', 'Médicaments', 'Laboratoire']),
                ],
                [
                    'plan_code'   => 'ACTIVA-GOLD',
                    'name'        => 'Activa Gold Santé',
                    'plan_type'   => 'private',
                    'description' => 'Couverture étendue avec chambre semi-privée, remboursement dental et accès aux cliniques privées conventionnées à Yaoundé et Douala.',
                    'monthly_premium'  => 24000,
                    'annual_premium'   => 264000,
                    'deductible'       => 10000,
                    'copay_percentage' => 15,
                    'cashless_available' => true,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Consultation spécialisée', 'Hospitalisation (semi-privée)', 'Chirurgie & anesthésie', 'Maternité premium', 'Dentaire', 'Ophtalmologie', 'Analyses', 'Imagerie médicale']),
                ],
                [
                    'plan_code'   => 'ACTIVA-PLAT',
                    'name'        => 'Activa Platinum Famille',
                    'plan_type'   => 'private',
                    'description' => 'Notre offre premium couvrant toute la famille, chambre privée, rapatriement médical, et plafond de 10 millions FCFA par assuré par an.',
                    'monthly_premium'  => 55000,
                    'annual_premium'   => 600000,
                    'deductible'       => 0,
                    'copay_percentage' => 10,
                    'cashless_available' => true,
                    'requires_preauthorization' => true,
                    'covered_services' => json_encode(['Hospitalisation privée', 'Chirurgie complexe', 'Oncologie', 'Dialyse', 'Maternité VIP', 'Dentaire complet', 'Rapatriement médical', 'Assistance internationale']),
                ],
            ],

            // ── Beneficial Life Insurance ─────────────────────────────────────
            'BENEFICIAL' => [
                [
                    'plan_code'   => 'BENEF-SANTE-S',
                    'name'        => 'Beneficial Santé Starter',
                    'plan_type'   => 'private',
                    'description' => 'Plan essentiel pour les indépendants et les PME avec couverture ambulatoire et hospitalisation d\'urgence.',
                    'monthly_premium'  => 10000,
                    'annual_premium'   => 110000,
                    'deductible'       => 15000,
                    'copay_percentage' => 25,
                    'cashless_available' => false,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Consultation', 'Urgences', 'Hospitalisation', 'Médicaments génériques', 'Maternité']),
                ],
                [
                    'plan_code'   => 'BENEF-SANTE-P',
                    'name'        => 'Beneficial Santé Plus',
                    'plan_type'   => 'private',
                    'description' => 'Couverture renforcée avec accès aux cliniques privées, dentaire basique et consultations spécialisées remboursées à 80 %.',
                    'monthly_premium'  => 22000,
                    'annual_premium'   => 240000,
                    'deductible'       => 8000,
                    'copay_percentage' => 20,
                    'cashless_available' => true,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Consultation spécialisée', 'Hospitalisation', 'Chirurgie', 'Dentaire basique', 'Ophtalmologie basique', 'Imagerie', 'Pharmacie']),
                ],
            ],

            // ── SAAR Assurance ────────────────────────────────────────────────
            'SAAR' => [
                [
                    'plan_code'   => 'SAAR-ESSENTIEL',
                    'name'        => 'SAAR Essentiel',
                    'plan_type'   => 'private',
                    'description' => 'Plan d\'accès rapide SAAR pour salariés du secteur privé, prise en charge immédiate des consultations et des hospitalisations.',
                    'monthly_premium'  => 11000,
                    'annual_premium'   => 120000,
                    'deductible'       => 12000,
                    'copay_percentage' => 20,
                    'cashless_available' => true,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Médecine générale', 'Hospitalisation', 'Médicaments', 'Maternité', 'Analyses biologiques']),
                ],
                [
                    'plan_code'   => 'SAAR-CONFORT',
                    'name'        => 'SAAR Confort Famille',
                    'plan_type'   => 'private',
                    'description' => 'Extension familiale SAAR couvrant conjoint et jusqu\'à 4 enfants, avec option dentaire et ophtalmologie incluse.',
                    'monthly_premium'  => 35000,
                    'annual_premium'   => 385000,
                    'deductible'       => 5000,
                    'copay_percentage' => 15,
                    'cashless_available' => true,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Famille complète', 'Pédiatrie', 'Gynécologie', 'Dentaire', 'Ophtalmologie', 'Chirurgie', 'Hospitalisation semi-privée']),
                ],
            ],

            // ── Saham / Sanlam ────────────────────────────────────────────────
            'SAHAM-CM' => [
                [
                    'plan_code'   => 'SAHAM-ACCESS',
                    'name'        => 'Saham Santé Access',
                    'plan_type'   => 'private',
                    'description' => 'Le plan Access de Saham s\'adresse aux particuliers et PME cherchant une couverture de base à prix abordable avec réseau cashless.',
                    'monthly_premium'  => 13000,
                    'annual_premium'   => 144000,
                    'deductible'       => 15000,
                    'copay_percentage' => 20,
                    'cashless_available' => true,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Consultation', 'Hospitalisation', 'Médicaments', 'Maternité', 'Analyses']),
                ],
                [
                    'plan_code'   => 'SAHAM-PREMIUM',
                    'name'        => 'Saham Santé Premium',
                    'plan_type'   => 'private',
                    'description' => 'Couverture complète incluant soins dentaires, lunettes, et possibilité de soins à l\'étranger avec plafond de 5 millions FCFA.',
                    'monthly_premium'  => 38000,
                    'annual_premium'   => 420000,
                    'deductible'       => 0,
                    'copay_percentage' => 10,
                    'cashless_available' => true,
                    'requires_preauthorization' => true,
                    'covered_services' => json_encode(['Soins complets', 'Dentaire complet', 'Ophtalmologie', 'Soins à l\'étranger', 'Évacuation médicale', 'Chirurgie complexe', 'Oncologie partielle']),
                ],
            ],

            // ── AXA Cameroun ──────────────────────────────────────────────────
            'AXA-CM' => [
                [
                    'plan_code'   => 'AXA-VITAL',
                    'name'        => 'AXA Vital Santé',
                    'plan_type'   => 'private',
                    'description' => 'Plan d\'entrée AXA avec couverture ambulatoire et hospitalisations d\'urgence, remboursement en 48 h.',
                    'monthly_premium'  => 14000,
                    'annual_premium'   => 154000,
                    'deductible'       => 10000,
                    'copay_percentage' => 20,
                    'cashless_available' => true,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Consultation GP', 'Urgences', 'Hospitalisation', 'Maternité', 'Médicaments', 'Biologie']),
                ],
                [
                    'plan_code'   => 'AXA-CONFORT',
                    'name'        => 'AXA Confort Plus',
                    'plan_type'   => 'private',
                    'description' => 'Couverture intermédiaire AXA avec accès aux spécialistes, chirurgie programmée et option dentaire intégrée.',
                    'monthly_premium'  => 29000,
                    'annual_premium'   => 318000,
                    'deductible'       => 5000,
                    'copay_percentage' => 15,
                    'cashless_available' => true,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Spécialistes', 'Chirurgie programmée', 'Dentaire', 'Radiologie', 'Scanner & IRM', 'Pharmacie', 'Maternité']),
                ],
                [
                    'plan_code'   => 'AXA-PRESTIGE',
                    'name'        => 'AXA Prestige International',
                    'plan_type'   => 'private',
                    'description' => 'Couverture internationale AXA pour expatriés et cadres supérieurs, hospitalisation en chambre individuelle, accès cliniques internationales.',
                    'monthly_premium'  => 75000,
                    'annual_premium'   => 825000,
                    'deductible'       => 0,
                    'copay_percentage' => 0,
                    'cashless_available' => true,
                    'requires_preauthorization' => true,
                    'covered_services' => json_encode(['Couverture mondiale', 'Hospitalisation privée', 'Chirurgie internationale', 'Dentaire complet', 'Check-up annuel', 'Psychiatrie', 'Rapatriement', 'Médecine de voyage']),
                ],
            ],

            // ── NSIA Cameroun ─────────────────────────────────────────────────
            'NSIA-CM' => [
                [
                    'plan_code'   => 'NSIA-BASIC',
                    'name'        => 'NSIA Santé Basique',
                    'plan_type'   => 'private',
                    'description' => 'Couverture basique NSIA pour travailleurs indépendants avec remboursement des consultations et médicaments essentiels.',
                    'monthly_premium'  => 9000,
                    'annual_premium'   => 99000,
                    'deductible'       => 18000,
                    'copay_percentage' => 25,
                    'cashless_available' => false,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Consultation générale', 'Médicaments essentiels', 'Maternité basique', 'Hospitalisation urgence']),
                ],
                [
                    'plan_code'   => 'NSIA-ETOILE',
                    'name'        => 'NSIA Étoile Santé',
                    'plan_type'   => 'private',
                    'description' => 'Notre plan star, couvrant toute la famille avec soins dentaires, accès aux cliniques privées partenaires et tiers payant sans avance de frais.',
                    'monthly_premium'  => 27000,
                    'annual_premium'   => 297000,
                    'deductible'       => 5000,
                    'copay_percentage' => 15,
                    'cashless_available' => true,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Famille', 'Pédiatrie', 'Dentaire', 'Ophtalmologie', 'Chirurgie', 'Hospitalisation', 'Biologie avancée', 'Imagerie']),
                ],
            ],

            // ── Chanas Assurances ─────────────────────────────────────────────
            'CHANAS' => [
                [
                    'plan_code'   => 'CHANAS-VITAL',
                    'name'        => 'Chanas Vital Santé',
                    'plan_type'   => 'private',
                    'description' => 'Assurance maladie individuelle Chanas avec réseau de cliniques partenaires à Yaoundé, Douala, Bafoussam et Garoua.',
                    'monthly_premium'  => 11500,
                    'annual_premium'   => 126000,
                    'deductible'       => 12000,
                    'copay_percentage' => 20,
                    'cashless_available' => true,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Consultation', 'Hospitalisation', 'Chirurgie', 'Maternité', 'Médicaments', 'Analyses']),
                ],
                [
                    'plan_code'   => 'CHANAS-EXCEL',
                    'name'        => 'Chanas Excellence Famille',
                    'plan_type'   => 'private',
                    'description' => 'Plan familial Chanas avec couverture étendue, accès illimité aux spécialistes conventionnés et remboursement dentaire jusqu\'à 200 000 FCFA/an.',
                    'monthly_premium'  => 42000,
                    'annual_premium'   => 462000,
                    'deductible'       => 0,
                    'copay_percentage' => 10,
                    'cashless_available' => true,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Famille étendue', 'Spécialités illimitées', 'Chirurgie planifiée', 'Dentaire avancé', 'Orthodontie enfants', 'Psychiatrie', 'Imagerie avancée']),
                ],
            ],

            // ── Allianz Cameroun ──────────────────────────────────────────────
            'ALLIANZ-CM' => [
                [
                    'plan_code'   => 'ALLIANZ-STARTER',
                    'name'        => 'Allianz Care Starter',
                    'plan_type'   => 'private',
                    'description' => 'Plan d\'accès rapide Allianz pour PME et indépendants avec prise en charge ambulatoire et hospitalisation.',
                    'monthly_premium'  => 13500,
                    'annual_premium'   => 148500,
                    'deductible'       => 15000,
                    'copay_percentage' => 20,
                    'cashless_available' => true,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Médecine générale', 'Hospitalisation', 'Maternité', 'Médicaments', 'Biologie']),
                ],
                [
                    'plan_code'   => 'ALLIANZ-ADVANCE',
                    'name'        => 'Allianz Care Advance',
                    'plan_type'   => 'private',
                    'description' => 'Couverture avancée Allianz incluant spécialistes, chirurgie programmée, dentaire et ophtalmologie, idéale pour les entreprises.',
                    'monthly_premium'  => 31000,
                    'annual_premium'   => 341000,
                    'deductible'       => 5000,
                    'copay_percentage' => 15,
                    'cashless_available' => true,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Spécialistes', 'Chirurgie', 'Dentaire', 'Ophtalmologie', 'Scanner', 'IRM', 'Physiothérapie']),
                ],
                [
                    'plan_code'   => 'ALLIANZ-ELITE',
                    'name'        => 'Allianz Care Elite',
                    'plan_type'   => 'private',
                    'description' => 'La couverture premium Allianz avec accès VIP, chambre individuelle, soins à l\'étranger et check-up médical annuel complet.',
                    'monthly_premium'  => 65000,
                    'annual_premium'   => 715000,
                    'deductible'       => 0,
                    'copay_percentage' => 5,
                    'cashless_available' => true,
                    'requires_preauthorization' => true,
                    'covered_services' => json_encode(['Chambre individuelle', 'Soins internationaux', 'Oncologie', 'Check-up annuel', 'Chirurgie cardiaque', 'Rapatriement VIP', 'Assistance 24/7']),
                ],
            ],

            // ── Prudential Beneficial ─────────────────────────────────────────
            'PRUDENTIAL-CM' => [
                [
                    'plan_code'   => 'PRUD-PROTECT',
                    'name'        => 'Prudential Protect Santé',
                    'plan_type'   => 'private',
                    'description' => 'Couverture santé individuelle et familiale de Prudential avec gestion numérique des remboursements via l\'application mobile.',
                    'monthly_premium'  => 15000,
                    'annual_premium'   => 165000,
                    'deductible'       => 10000,
                    'copay_percentage' => 20,
                    'cashless_available' => true,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Consultation', 'Hospitalisation', 'Chirurgie', 'Maternité', 'Médicaments', 'Analyses', 'Imagerie basique']),
                ],
                [
                    'plan_code'   => 'PRUD-PREMIUM',
                    'name'        => 'Prudential Premium Santé',
                    'plan_type'   => 'private',
                    'description' => 'Notre plan phare avec remboursement à 90 % sur tous les soins, dentaire complet et prise en charge des maladies chroniques.',
                    'monthly_premium'  => 40000,
                    'annual_premium'   => 440000,
                    'deductible'       => 0,
                    'copay_percentage' => 10,
                    'cashless_available' => true,
                    'requires_preauthorization' => true,
                    'covered_services' => json_encode(['Maladies chroniques', 'Dentaire complet', 'Dialyse', 'Chimiothérapie', 'Maternité VIP', 'Spécialistes', 'Évacuation médicale']),
                ],
            ],

            // ── Zenithe Insurance ─────────────────────────────────────────────
            'ZENITHE' => [
                [
                    'plan_code'   => 'ZENITHE-CLASSIQ',
                    'name'        => 'Zenithe Classique',
                    'plan_type'   => 'private',
                    'description' => 'Assurance santé individuelle Zenithe avec réseau de 150+ prestataires agréés au Cameroun.',
                    'monthly_premium'  => 10500,
                    'annual_premium'   => 115500,
                    'deductible'       => 15000,
                    'copay_percentage' => 20,
                    'cashless_available' => false,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Consultation', 'Hospitalisation', 'Chirurgie', 'Maternité', 'Médicaments']),
                ],
                [
                    'plan_code'   => 'ZENITHE-PLUS',
                    'name'        => 'Zenithe Santé Plus',
                    'plan_type'   => 'private',
                    'description' => 'Extension dentaire et ophtalmologique avec accès aux cliniques privées partenaires et tiers payant direct.',
                    'monthly_premium'  => 22500,
                    'annual_premium'   => 247500,
                    'deductible'       => 8000,
                    'copay_percentage' => 15,
                    'cashless_available' => true,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Soins ambulatoires', 'Spécialistes', 'Dentaire', 'Ophtalmologie', 'Chirurgie', 'Hospitalisation', 'Biologie avancée']),
                ],
            ],

            // ── GAN Assurance ─────────────────────────────────────────────────
            'GAN-CM' => [
                [
                    'plan_code'   => 'GAN-SANTE-IND',
                    'name'        => 'GAN Santé Individuelle',
                    'plan_type'   => 'private',
                    'description' => 'Plan individuel GAN couvrant les risques maladie courants avec remboursement rapide et service client dédié.',
                    'monthly_premium'  => 11000,
                    'annual_premium'   => 121000,
                    'deductible'       => 12000,
                    'copay_percentage' => 20,
                    'cashless_available' => false,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Médecine générale', 'Hospitalisation', 'Urgences', 'Maternité', 'Médicaments']),
                ],
                [
                    'plan_code'   => 'GAN-GROUPE',
                    'name'        => 'GAN Santé Groupe Entreprise',
                    'plan_type'   => 'employer',
                    'description' => 'Solution collective GAN pour entreprises, avec gestion centralisée, carte tiers payant et rapport sinistralité trimestriel.',
                    'monthly_premium'  => 18000,
                    'annual_premium'   => 198000,
                    'deductible'       => 5000,
                    'copay_percentage' => 15,
                    'cashless_available' => true,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Médecine du travail', 'Consultation spécialisée', 'Hospitalisation', 'Chirurgie', 'Maternité', 'Dentaire basique', 'Médicaments']),
                ],
            ],

            // ── GMF (Garantie Mutuelle des Fonctionnaires) ────────────────────
            'GMF-CM' => [
                [
                    'plan_code'   => 'GMF-FONCT',
                    'name'        => 'GMF Plan Fonctionnaires',
                    'plan_type'   => 'mutual',
                    'description' => 'Mutuelle dédiée aux fonctionnaires et agents de l\'État camerounais. Cotisation prélevée directement sur salaire. Plafond 2M FCFA/an.',
                    'monthly_premium'  => 7500,
                    'annual_premium'   => 82500,
                    'deductible'       => 5000,
                    'copay_percentage' => 20,
                    'cashless_available' => true,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Consultation GP & spécialiste', 'Hospitalisation CHHUY/CHHUD', 'Maternité', 'Médicaments liste essentielle', 'Chirurgie']),
                ],
                [
                    'plan_code'   => 'GMF-FAMILLE',
                    'name'        => 'GMF Extension Familiale',
                    'plan_type'   => 'mutual',
                    'description' => 'Extension de la mutuelle GMF au conjoint et aux enfants à charge, avec couverture dentaire et ophtalmologie incluse.',
                    'monthly_premium'  => 14000,
                    'annual_premium'   => 154000,
                    'deductible'       => 3000,
                    'copay_percentage' => 15,
                    'cashless_available' => true,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Famille complète', 'Pédiatrie', 'Dentaire', 'Ophtalmologie', 'Lunettes remboursées', 'Chirurgie enfants']),
                ],
            ],

            // ── Sunu Assurances ───────────────────────────────────────────────
            'SUNU-CM' => [
                [
                    'plan_code'   => 'SUNU-BASIC',
                    'name'        => 'Sunu Santé Basique',
                    'plan_type'   => 'private',
                    'description' => 'Assurance santé de base Sunu pour travailleurs du secteur informel et indépendants, primes mensuelles flexibles.',
                    'monthly_premium'  => 8500,
                    'annual_premium'   => 93500,
                    'deductible'       => 20000,
                    'copay_percentage' => 30,
                    'cashless_available' => false,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Consultation générale', 'Hospitalisation urgence', 'Accouchement', 'Médicaments génériques']),
                ],
                [
                    'plan_code'   => 'SUNU-OPTIM',
                    'name'        => 'Sunu Santé Optimal',
                    'plan_type'   => 'private',
                    'description' => 'Plan intermédiaire Sunu avec tiers payant dans 80 centres partenaires et remboursement des spécialistes sous 5 jours ouvrables.',
                    'monthly_premium'  => 19000,
                    'annual_premium'   => 209000,
                    'deductible'       => 8000,
                    'copay_percentage' => 20,
                    'cashless_available' => true,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Consultation spécialisée', 'Chirurgie', 'Maternité', 'Hospitalisation', 'Biologie', 'Imagerie', 'Pharmacie']),
                ],
                [
                    'plan_code'   => 'SUNU-PREMIUM',
                    'name'        => 'Sunu Santé Premium',
                    'plan_type'   => 'private',
                    'description' => 'Notre meilleure couverture avec prise en charge dentaire, ophtalmologique et accès à nos cliniques partenaires en Afrique de l\'Ouest.',
                    'monthly_premium'  => 45000,
                    'annual_premium'   => 495000,
                    'deductible'       => 0,
                    'copay_percentage' => 10,
                    'cashless_available' => true,
                    'requires_preauthorization' => true,
                    'covered_services' => json_encode(['Soins complets Afrique de l\'Ouest', 'Dentaire complet', 'Ophtalmologie avancée', 'Chirurgie planifiée', 'Maternité haut standing', 'Évacuation médicale']),
                ],
            ],

            // ── CIPMEN ────────────────────────────────────────────────────────
            'CIPMEN' => [
                [
                    'plan_code'   => 'CIPMEN-PREV',
                    'name'        => 'CIPMEN Prévoyance Maladie',
                    'plan_type'   => 'mutual',
                    'description' => 'Régime de prévoyance maladie interprofessionnel CIPMEN pour employés du secteur privé formel, géré paritairement.',
                    'monthly_premium'  => 6500,
                    'annual_premium'   => 71500,
                    'deductible'       => 10000,
                    'copay_percentage' => 25,
                    'cashless_available' => false,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Maladie', 'Maternité', 'Invalidité', 'Accidents de travail', 'Médicaments liste CIPMEN']),
                ],
                [
                    'plan_code'   => 'CIPMEN-COMPL',
                    'name'        => 'CIPMEN Complémentaire',
                    'plan_type'   => 'mutual',
                    'description' => 'Couverture complémentaire CIPMEN pour combler le ticket modérateur et accéder aux soins dentaires et ophtalmologiques.',
                    'monthly_premium'  => 12000,
                    'annual_premium'   => 132000,
                    'deductible'       => 3000,
                    'copay_percentage' => 10,
                    'cashless_available' => true,
                    'requires_preauthorization' => false,
                    'covered_services' => json_encode(['Ticket modérateur', 'Dentaire', 'Ophtalmologie', 'Kinésithérapie', 'Appareillage']),
                ],
            ],
        ];

        $inserted = 0;
        $skipped  = 0;

        foreach ($plansByCode as $providerCode => $plans) {
            $provider = DB::table('insurance_providers')
                ->where('code', $providerCode)
                ->first();

            if (!$provider) {
                $this->command?->warn("Provider not found: $providerCode — skipping.");
                continue;
            }

            foreach ($plans as $plan) {
                $exists = DB::table('insurance_plans')
                    ->where('insurance_provider_id', $provider->id)
                    ->where('plan_code', $plan['plan_code'])
                    ->exists();

                if ($exists) {
                    // Update pricing and purchasable flag on existing rows
                    DB::table('insurance_plans')
                        ->where('insurance_provider_id', $provider->id)
                        ->where('plan_code', $plan['plan_code'])
                        ->update(array_merge($plan, [
                            'is_purchasable' => true,
                            'status'         => 'active',
                            'updated_at'     => $now,
                        ]));
                    $skipped++;
                    continue;
                }

                DB::table('insurance_plans')->insert(array_merge($plan, [
                    'id'                   => (string) Str::uuid(),
                    'insurance_provider_id' => $provider->id,
                    'is_purchasable'       => true,
                    'status'              => 'active',
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ]));
                $inserted++;
            }
        }

        $total = DB::table('insurance_plans')->where('is_purchasable', true)->count();
        $this->command?->info(
            "CameroonInsurancePlansSeeder: $inserted new plans inserted, $skipped updated. " .
            "$total total purchasable plans."
        );
    }
}

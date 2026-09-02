<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Notifications\Models\NotificationTemplate;
use Illuminate\Support\Str;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // 1. Welcome - Patient
            [
                'event_type' => 'welcome_patient',
                'channel' => 'email',
                'language' => 'en',
                'subject' => 'Welcome to OpesCare',
                'title' => 'Welcome to OpesCare',
                'body' => 'Your OpesCare account has been created. You can now access your Health ID, manage consent requests, view health updates, and see who accessed your records.',
                'cta_label' => 'Open My Health ID',
                'template_text' => 'Your account is created. Access your Health ID.'
            ],
            [
                'event_type' => 'welcome_patient',
                'channel' => 'email',
                'language' => 'fr',
                'subject' => 'Bienvenue sur OpesCare',
                'title' => 'Bienvenue sur OpesCare',
                'body' => 'Votre compte OpesCare a été créé. Vous pouvez maintenant accéder à votre identifiant de santé, gérer les demandes de consentement, consulter vos mises à jour de santé et voir qui a consulté vos dossiers.',
                'cta_label' => 'Ouvrir mon identifiant de santé',
                'template_text' => 'Votre compte est créé. Accédez à votre identifiant de santé.'
            ],

            // 2. OTP
            [
                'event_type' => 'otp',
                'channel' => 'email',
                'language' => 'en',
                'subject' => 'Your OpesCare verification code',
                'title' => 'Verification Code',
                'body' => 'Use this code to continue securely: {{ otp_code }}. It expires in {{ expiry_minutes }} minutes.',
                'cta_label' => null,
                'template_text' => 'Verification code: {{ otp_code }}.'
            ],
            [
                'event_type' => 'otp',
                'channel' => 'email',
                'language' => 'fr',
                'subject' => 'Votre code de vérification OpesCare',
                'title' => 'Code de vérification',
                'body' => 'Utilisez ce code pour continuer en toute sécurité : {{ otp_code }}. Il expire dans {{ expiry_minutes }} minutes.',
                'cta_label' => null,
                'template_text' => 'Code de vérification : {{ otp_code }}.'
            ],

            // 3. Lab Result Available
            [
                'event_type' => 'lab_result_patient',
                'channel' => 'email',
                'language' => 'en',
                'subject' => 'A lab result is ready to view',
                'title' => 'Lab Result Ready',
                'body' => 'A lab result has been released to your OpesCare account. Log in securely to view it.',
                'cta_label' => 'View Health Update',
                'template_text' => 'A lab result is ready to view. Log in securely.'
            ],
            [
                'event_type' => 'lab_result_patient',
                'channel' => 'email',
                'language' => 'fr',
                'subject' => 'Un résultat de laboratoire est disponible',
                'title' => 'Résultat de laboratoire disponible',
                'body' => 'Un résultat de laboratoire a été publié dans votre compte OpesCare. Connectez-vous de manière sécurisée pour le consulter.',
                'cta_label' => 'Voir la mise à jour de santé',
                'template_text' => 'Un résultat est disponible. Connectez-vous.'
            ],

            // 4. Critical Lab Result
            [
                'event_type' => 'critical_lab_provider',
                'channel' => 'email',
                'language' => 'en',
                'subject' => 'Urgent action required in OpesCare',
                'title' => 'Urgent Action Required',
                'body' => 'A critical result has been released and requires attention from an authorized clinical user. Log in to OpesCare to review the case securely.',
                'cta_label' => 'Review Critical Result',
                'template_text' => 'A critical result requires your urgent review. Log in.'
            ],
            [
                'event_type' => 'critical_lab_provider',
                'channel' => 'email',
                'language' => 'fr',
                'subject' => 'Action urgente requise dans OpesCare',
                'title' => 'Action urgente requise',
                'body' => 'Un résultat critique a été publié et nécessite l’attention d’un utilisateur clinique autorisé. Connectez-vous à OpesCare pour examiner le cas en toute sécurité.',
                'cta_label' => 'Examiner le résultat critique',
                'template_text' => 'Un résultat critique nécessite un examen immédiat.'
            ],

            // 5. Prescription Update
            [
                'event_type' => 'prescription_patient',
                'channel' => 'email',
                'language' => 'en',
                'subject' => 'A prescription update is available',
                'title' => 'Prescription Update',
                'body' => 'A prescription update has been added to your OpesCare account. Log in securely to view the details and pharmacy options where available.',
                'cta_label' => 'View Prescription Update',
                'template_text' => 'A prescription update is ready.'
            ],
            [
                'event_type' => 'prescription_patient',
                'channel' => 'email',
                'language' => 'fr',
                'subject' => 'Une mise à jour d’ordonnance est disponible',
                'title' => 'Mise à jour d’ordonnance',
                'body' => 'Une mise à jour d’ordonnance a été ajoutée à votre compte OpesCare. Connectez-vous de manière sécurisée pour voir les détails et les options de pharmacie disponibles.',
                'cta_label' => 'Voir la mise à jour d’ordonnance',
                'template_text' => 'Une ordonnance est mise à jour.'
            ],

            // 6. Consent Request
            [
                'event_type' => 'consent_request_patient',
                'channel' => 'email',
                'language' => 'en',
                'subject' => 'A provider is requesting access',
                'title' => 'Access Request',
                'body' => '{{ facility_name }} is requesting access to selected health information for {{ purpose }}. Review the request before approving or denying it.',
                'cta_label' => 'Review Access Request',
                'template_text' => '{{ facility_name }} requests access.'
            ],
            [
                'event_type' => 'consent_request_patient',
                'channel' => 'email',
                'language' => 'fr',
                'subject' => 'Un prestataire demande un accès',
                'title' => 'Demande d’accès',
                'body' => '{{ facility_name }} demande l’accès à certaines informations de santé pour {{ purpose }}. Examinez la demande avant de l’approuver ou de la refuser.',
                'cta_label' => 'Examiner la demande d’accès',
                'template_text' => '{{ facility_name }} demande un accès.'
            ],

            // 7. Emergency Access Used
            [
                'event_type' => 'emergency_access_patient',
                'channel' => 'email',
                'language' => 'en',
                'subject' => 'Emergency access was used',
                'title' => 'Emergency Access Notice',
                'body' => 'An authorized provider used emergency access for your OpesCare profile. Log in to view the access details where available.',
                'cta_label' => 'View Access Log',
                'template_text' => 'Emergency access was used for your profile.'
            ],
            [
                'event_type' => 'emergency_access_patient',
                'channel' => 'email',
                'language' => 'fr',
                'subject' => 'Un accès d’urgence a été utilisé',
                'title' => 'Notice d’accès d’urgence',
                'body' => 'Un prestataire autorisé a utilisé l’accès d’urgence pour votre profil OpesCare. Connectez-vous pour voir les détails d’accès disponibles.',
                'cta_label' => 'Voir le journal d’accès',
                'template_text' => 'Un accès d’urgence a été activé sur votre profil.'
            ],

            // 8. Medicine Reservation Update
            [
                'event_type' => 'medicine_reservation_update',
                'channel' => 'email',
                'language' => 'en',
                'subject' => 'Medicine reservation update',
                'title' => 'Reservation Update',
                'body' => 'A pharmacy has updated your medicine reservation request. Log in to OpesCare to view the status and next steps.',
                'cta_label' => 'View Reservation',
                'template_text' => 'Reservation update is ready.'
            ],
            [
                'event_type' => 'medicine_reservation_update',
                'channel' => 'email',
                'language' => 'fr',
                'subject' => 'Mise à jour de réservation de médicament',
                'title' => 'Mise à jour de réservation',
                'body' => 'Une pharmacie a mis à jour votre demande de réservation de médicament. Connectez-vous à OpesCare pour voir le statut et les prochaines étapes.',
                'cta_label' => 'Voir la réservation',
                'template_text' => 'Votre réservation est mise à jour.'
            ],

            // 9. Blood Availability Update
            [
                'event_type' => 'blood_availability_update',
                'channel' => 'email',
                'language' => 'en',
                'subject' => 'Blood availability update',
                'title' => 'Blood Alert',
                'body' => 'A blood availability update is available in OpesCare. Please follow guidance from authorized healthcare professionals.',
                'cta_label' => 'View Details',
                'template_text' => 'Blood update is ready.'
            ],
            [
                'event_type' => 'blood_availability_update',
                'channel' => 'email',
                'language' => 'fr',
                'subject' => 'Mise à jour de disponibilité du sang',
                'title' => 'Alerte disponibilité du sang',
                'body' => 'Une mise à jour sur la disponibilité du sang est disponible dans OpesCare. Veuillez suivre les conseils des professionnels de santé autorisés.',
                'cta_label' => 'Voir les détails',
                'template_text' => 'Disponibilité du sang mise à jour.'
            ],

            // 10. Insurance Claim Update
            [
                'event_type' => 'insurance_claim_update',
                'channel' => 'email',
                'language' => 'en',
                'subject' => 'Insurance claim update',
                'title' => 'Claim Update',
                'body' => 'There is an update on an insurance workflow linked to your OpesCare account. Log in securely to view the status.',
                'cta_label' => 'View Claim',
                'template_text' => 'Claim update is ready.'
            ],
            [
                'event_type' => 'insurance_claim_update',
                'channel' => 'email',
                'language' => 'fr',
                'subject' => 'Mise à jour de demande d’assurance',
                'title' => 'Mise à jour d’assurance',
                'body' => 'Une mise à jour est disponible sur un processus d’assurance lié à votre compte OpesCare. Connectez-vous de manière sécurisée pour voir le statut.',
                'cta_label' => 'Voir la demande',
                'template_text' => 'Processus d’assurance mis à jour.'
            ],

            // 11. Partner Application Submitted
            [
                'event_type' => 'partner_application_submitted',
                'channel' => 'email',
                'language' => 'en',
                'subject' => 'Your OpesCare partner application was received',
                'title' => 'Application Received',
                'body' => 'Thank you for applying to become an OpesCare partner. Our team will review your information and contact you if more details are needed.',
                'cta_label' => 'View Application Status',
                'template_text' => 'Partner application received.'
            ],
            [
                'event_type' => 'partner_application_submitted',
                'channel' => 'email',
                'language' => 'fr',
                'subject' => 'Votre demande de partenariat OpesCare a été reçue',
                'title' => 'Demande reçue',
                'body' => 'Merci d’avoir demandé à devenir partenaire OpesCare. Notre équipe examinera vos informations et vous contactera si des détails supplémentaires sont nécessaires.',
                'cta_label' => 'Voir le statut de la demande',
                'template_text' => 'Demande de partenariat reçue.'
            ],

            // 12. API Sync Failed
            [
                'event_type' => 'api_sync_failed_developer',
                'channel' => 'email',
                'language' => 'en',
                'subject' => 'OpesCare API sync issue',
                'title' => 'Sync Error Detected',
                'body' => 'A sync event from your integration failed and requires review. Log in to the developer dashboard to inspect the error and retry if appropriate.',
                'cta_label' => 'Go to Developer Dashboard',
                'template_text' => 'API sync failed.'
            ],
            [
                'event_type' => 'api_sync_failed_developer',
                'channel' => 'email',
                'language' => 'fr',
                'subject' => 'Problème de synchronisation API OpesCare',
                'title' => 'Erreur de synchronisation détectée',
                'body' => 'Un événement de synchronisation de votre intégration a échoué et nécessite un examen. Connectez-vous au tableau de bord développeur pour inspecter l’erreur et réessayer si nécessaire.',
                'cta_label' => 'Aller au tableau de bord',
                'template_text' => 'Échec de synchro API.'
            ]
        ];

        foreach ($templates as $t) {
            NotificationTemplate::firstOrCreate(
                [
                    'event_type' => $t['event_type'],
                    'channel'    => $t['channel'],
                    'language'   => $t['language'],
                ],
                array_merge($t, [
                    'uuid'                => (string) Str::uuid(),
                    'version'             => 1,
                    'approval_status'     => $t['approval_status'] ?? 'published',
                    'communication_class' => $t['communication_class'] ?? 'optional',
                    'template_text'       => $t['template_text'] ?? $t['body'],
                ])
            );
        }

        // ── SP-4: real delivery templates (sms + email) for transactional events ──
        $transactionalTemplates = [
            [
                'event_type'          => 'appointment.booked',
                'channel'             => 'sms',
                'language'            => 'en',
                'subject'             => 'Appointment Confirmed',
                'title'               => 'Appointment Confirmed',
                'body'                => 'Your appointment at {{ facility_name }} is confirmed for {{ scheduled_at }}.',
                'template_text'       => 'Your appointment at {{ facility_name }} is confirmed for {{ scheduled_at }}.',
                'priority'            => 'high',
                'communication_class' => 'transactional',
                'approval_status'     => 'approved',
            ],
            [
                'event_type'          => 'appointment.booked',
                'channel'             => 'email',
                'language'            => 'en',
                'subject'             => 'Appointment Confirmed — {{ facility_name }}',
                'title'               => 'Your OpesCare Appointment is Confirmed',
                'body'                => "Hello {{ patient_name }},\n\nYour appointment at {{ facility_name }} has been confirmed.\n\nDate & Time: {{ scheduled_at }}\nType: {{ appointment_type }}\n\nLog in to OpesCare to view or manage your appointment.",
                'template_text'       => "Hello {{ patient_name }},\n\nYour appointment at {{ facility_name }} has been confirmed.\n\nDate & Time: {{ scheduled_at }}\nType: {{ appointment_type }}\n\nLog in to OpesCare to view or manage your appointment.",
                'priority'            => 'high',
                'communication_class' => 'transactional',
                'approval_status'     => 'approved',
            ],
            [
                'event_type'          => 'lab.result.ready',
                'channel'             => 'sms',
                'language'            => 'en',
                'subject'             => 'Lab Result Ready',
                'title'               => 'Your Lab Result is Ready',
                'body'                => 'Your lab result is now available in OpesCare. Log in to view it securely.',
                'template_text'       => 'Your lab result is now available in OpesCare. Log in to view it securely.',
                'priority'            => 'high',
                'communication_class' => 'transactional',
                'approval_status'     => 'approved',
            ],
            [
                'event_type'          => 'lab.result.ready',
                'channel'             => 'email',
                'language'            => 'en',
                'subject'             => 'Your Lab Result is Ready',
                'title'               => 'Lab Result Available',
                'body'                => "Hello {{ patient_name }},\n\nA new lab result has been added to your OpesCare health record.\n\nFor privacy, result details are only visible inside the secure OpesCare app. Log in to view them.",
                'template_text'       => "Hello {{ patient_name }},\n\nA new lab result has been added to your OpesCare health record.\n\nFor privacy, result details are only visible inside the secure OpesCare app. Log in to view them.",
                'priority'            => 'high',
                'communication_class' => 'transactional',
                'approval_status'     => 'approved',
            ],
            [
                'event_type'          => 'consent.request.pending',
                'channel'             => 'sms',
                'language'            => 'en',
                'subject'             => 'Consent Request',
                'title'               => 'A Facility Wants to Access Your Records',
                'body'                => '{{ facility_name }} has requested access to your health records. Open OpesCare to approve or deny.',
                'template_text'       => '{{ facility_name }} has requested access to your health records. Open OpesCare to approve or deny.',
                'priority'            => 'urgent',
                'communication_class' => 'transactional',
                'approval_status'     => 'approved',
            ],
            [
                'event_type'          => 'consent.request.pending',
                'channel'             => 'email',
                'language'            => 'en',
                'subject'             => 'Action Required: Health Record Access Request',
                'title'               => 'Consent Request',
                'body'                => "Hello,\n\n{{ facility_name }} has requested access to your health records for the purpose of: {{ purpose }}.\n\nLog in to OpesCare to approve or deny this request. You can revoke access at any time.",
                'template_text'       => "Hello,\n\n{{ facility_name }} has requested access to your health records for the purpose of: {{ purpose }}.\n\nLog in to OpesCare to approve or deny this request. You can revoke access at any time.",
                'priority'            => 'urgent',
                'communication_class' => 'transactional',
                'approval_status'     => 'approved',
            ],
        ];

        foreach ($transactionalTemplates as $tpl) {
            NotificationTemplate::firstOrCreate(
                [
                    'event_type' => $tpl['event_type'],
                    'channel'    => $tpl['channel'],
                    'language'   => $tpl['language'],
                ],
                array_merge($tpl, ['uuid' => (string) Str::uuid(), 'version' => 1])
            );
        }
    }
}

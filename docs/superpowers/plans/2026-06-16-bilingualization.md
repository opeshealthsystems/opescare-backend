# OpesCare Complete Bilingualization (EN/FR 1:1) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Achieve 100% EN/FR bilingualization across the entire OpesCare platform — every user-visible string, error message, flash message, meta tag, aria-label, placeholder, and JS string must be driven by `__()` with 1:1 matching keys in `lang/en/` and `lang/fr/`.

**Architecture:**
Laravel's built-in `__()` / `trans()` system is already wired and the locale switcher route exists (`lang.switch`). All 18 existing lang files have 1:1 EN/FR parity. The work is three-pronged: (1) wrap hardcoded strings in Blade views inside `__()`, (2) create two new lang files (`api.php`, `flash.php`) for controller messages and replace hardcoded strings in 126 controllers, (3) add `validation.php` and `passwords.php` in both languages. Every new key added to EN must be simultaneously added to FR.

**Tech Stack:** Laravel 13 · PHP 8.3 · Blade templates · `lang/en/*.php` + `lang/fr/*.php` · `__()` / `trans()` · `@json()` for JS string injection

---

## Audit Summary (pre-work baseline)

| Layer | Files | Hardcoded strings | Status |
|---|---|---|---|
| Lang files (18 files × 2) | 36 | 0 | ✓ Complete, 1:1 |
| Public Blade views | 20 files | 300+ | ✗ Critical |
| Portal Blade views (9 portals) | 180+ files | 400+ | ✗ Critical |
| Mobile API controllers | 20 files | 150+ | ✗ Critical |
| Web/Admin controllers (flash msgs) | 40+ files | 200+ | ✗ Critical |
| V1 API controllers | 60+ files | 350+ | ✗ Critical |
| Meta tags (page titles/descriptions) | 30+ views | 60+ | ✗ Medium |
| JavaScript carousel/tab labels | 5+ views | 20+ | ✗ Medium |
| validation.php / passwords.php | missing | N/A | ✗ Missing |

**Total estimated strings to translate: ~1,500+**

---

## File Map

### New files to create:
- `lang/en/api.php` — all API JSON response messages
- `lang/fr/api.php` — French translations of the above (1:1)
- `lang/en/flash.php` — web controller flash/redirect messages
- `lang/fr/flash.php` — French translations
- `lang/en/validation.php` — custom validation messages
- `lang/fr/validation.php` — French validation messages
- `lang/en/passwords.php` — password reset messages
- `lang/fr/passwords.php` — French password reset messages
- `scripts/i18n-audit.php` — artisan command to detect key parity drift

### Files to modify (Blade — public):
- `resources/views/public/landing.blade.php`
- `resources/views/public/landing2.blade.php`
- `resources/views/public/about.blade.php`
- `resources/views/public/how_it_works.blade.php`
- `resources/views/public/contact.blade.php`
- `resources/views/public/privacy.blade.php`
- `resources/views/public/security.blade.php`
- `resources/views/public/terms.blade.php`
- `resources/views/public/consent.blade.php`
- `resources/views/public/developers.blade.php`
- `resources/views/public/faq.blade.php`
- `resources/views/public/help.blade.php`
- `resources/views/public/interoperability.blade.php`
- `resources/views/public/status.blade.php`
- `resources/views/public/legal/index.blade.php`
- `resources/views/public/solutions/*.blade.php` (4 files)

### Files to modify (Blade — portals, grouped by wave):
- Patient portal: `portals/patient/*.blade.php` + subdirs (~15 files)
- Staff portal: `portals/staff/*.blade.php` + subdirs (~40 files)
- Admin portal: `portals/admin/*.blade.php` + subdirs (~60 files)
- Insurance: `portals/insurance/*.blade.php` (6 files)
- Lab: `portals/lab/*.blade.php` (5 files)
- Pharmacy: `portals/pharmacy/*.blade.php` (5 files)
- HealthOrg: `portals/healthorg/*.blade.php` (6 files)
- Developer: `portals/developer/*.blade.php` (10 files)
- Lite: `portals/lite/*.blade.php` (7 files)

### Files to modify (Controllers):
- `app/Http/Controllers/Api/Mobile/*.php` (20 files)
- `app/Http/Controllers/Api/V1/*.php` (60+ files)
- `app/Http/Controllers/MedicalId/*.php` (40+ files)

---

## Wave 1 — Infrastructure & Parity Tooling

### Task 1: Create `api.php` lang files (EN + FR)

Create the translation home for all API JSON response messages. Every key must exist in both files simultaneously.

**Files:**
- Create: `lang/en/api.php`
- Create: `lang/fr/api.php`

- [ ] **Step 1: Create `lang/en/api.php`**

```php
<?php
// API JSON response messages — used in Api\Mobile\* and Api\V1\* controllers
return [
    // Auth
    'patient_not_found'             => 'Patient not found.',
    'identity_verification_required'=> 'Identity verification required. Please provide your date of birth.',
    'invalid_credentials'           => 'Invalid credentials.',
    'otp_sent'                      => 'OTP sent to your registered phone number.',
    'otp_resent'                    => 'OTP resent to your registered phone number.',
    'otp_invalid'                   => 'Invalid or expired OTP.',
    'invalid_email_or_password'     => 'Invalid email or password.',
    'no_token_provided'             => 'No token provided.',
    'token_too_old'                 => 'Token invalid or too old to refresh.',
    'unauthenticated'               => 'Unauthenticated.',
    'unauthorized'                  => 'Unauthorized.',
    'account_suspended'             => 'Your account has been suspended. Contact support.',
    'two_factor_required'           => 'Two-factor authentication is required.',
    'two_factor_invalid'            => 'Invalid two-factor code.',
    'session_expired'               => 'Your session has expired. Please log in again.',
    'logout_success'                => 'Logged out successfully.',

    // Patient / Record
    'no_patient_record'             => 'No patient record linked to this account.',
    'record_not_found'              => 'Record not found.',
    'record_created'                => 'Record created successfully.',
    'record_updated'                => 'Record updated successfully.',
    'record_deleted'                => 'Record deleted successfully.',

    // Consent
    'consent_not_found'             => 'Consent request not found or already processed.',
    'consent_grant_not_found'       => 'Active consent grant not found.',
    'consent_revoked'               => 'Consent grant revoked. Existing tokens invalidated.',
    'consent_approved'              => 'Consent approved.',
    'consent_denied'                => 'Consent denied.',

    // Family
    'no_user_linked'                => 'No user account linked to this patient.',
    'cannot_link_self'              => 'You cannot link yourself as a dependent.',
    'dependent_added'               => 'Dependent linked successfully.',
    'dependent_removed'             => 'Dependent removed.',

    // Appointments
    'appointment_not_found'         => 'Appointment not found.',
    'appointment_created'           => 'Appointment scheduled successfully.',
    'appointment_updated'           => 'Appointment updated.',
    'appointment_cancelled'         => 'Appointment cancelled.',
    'appointment_already_cancelled' => 'Appointment is already cancelled.',
    'waitlist_dispatched'           => 'Waitlist backfill job dispatched.',
    'slot_not_available'            => 'The selected slot is no longer available.',

    // Blood / Inventory
    'units_added'                   => 'Units added.',
    'units_removed'                 => 'Units removed.',
    'insufficient_stock'            => 'Insufficient stock.',

    // Lab
    'order_not_found'               => 'Lab order not found.',
    'sample_not_found'              => 'Sample not found.',
    'result_submitted'              => 'Result submitted successfully.',
    'result_not_found'              => 'Result not found.',

    // Prescriptions
    'prescription_not_found'        => 'Prescription not found.',
    'prescription_dispensed'        => 'Prescription dispensed.',
    'prescription_cancelled'        => 'Prescription cancelled.',

    // Referrals
    'referral_not_found'            => 'Referral not found.',
    'referral_created'              => 'Referral created.',
    'referral_accepted'             => 'Referral accepted.',
    'referral_rejected'             => 'Referral rejected.',

    // Insurance
    'policy_not_found'              => 'Insurance policy not found.',
    'claim_not_found'               => 'Claim not found.',
    'claim_submitted'               => 'Claim submitted successfully.',
    'preauth_not_found'             => 'Pre-authorisation not found.',
    'preauth_approved'              => 'Pre-authorisation approved.',
    'preauth_rejected'              => 'Pre-authorisation rejected.',

    // QR / Health ID
    'qr_generated'                  => 'QR code generated.',
    'qr_invalid'                    => 'Invalid or expired QR code.',
    'health_id_not_found'           => 'Health ID not found.',

    // Developer / API apps
    'app_not_found'                 => 'Application not found.',
    'app_created'                   => 'Application created.',
    'app_deleted'                   => 'Application deleted.',
    'webhook_not_found'             => 'Webhook not found.',
    'webhook_created'               => 'Webhook created.',
    'webhook_deleted'               => 'Webhook deleted.',
    'production_request_submitted'  => 'Production access request submitted.',

    // Generic
    'success'                       => 'Operation completed successfully.',
    'error'                         => 'An error occurred. Please try again.',
    'not_found'                     => 'The requested resource was not found.',
    'validation_failed'             => 'Validation failed.',
    'forbidden'                     => 'You do not have permission to perform this action.',
    'server_error'                  => 'A server error occurred. Please contact support.',
    'too_many_requests'             => 'Too many requests. Please wait before retrying.',
    'service_unavailable'           => 'Service temporarily unavailable.',
    'feature_not_available'         => 'This feature is not available on your plan.',
    'file_too_large'                => 'File size exceeds the maximum allowed limit.',
    'invalid_file_type'             => 'File type not permitted.',
    'export_ready'                  => 'Export ready.',
    'email_sent'                    => 'Email sent.',
    'sync_complete'                 => 'Sync completed.',
    'job_queued'                    => 'Job queued for processing.',
];
```

- [ ] **Step 2: Create `lang/fr/api.php`**

```php
<?php
return [
    // Auth
    'patient_not_found'             => 'Patient introuvable.',
    'identity_verification_required'=> 'Vérification d\'identité requise. Veuillez fournir votre date de naissance.',
    'invalid_credentials'           => 'Identifiants invalides.',
    'otp_sent'                      => 'OTP envoyé à votre numéro de téléphone enregistré.',
    'otp_resent'                    => 'OTP renvoyé à votre numéro de téléphone enregistré.',
    'otp_invalid'                   => 'OTP invalide ou expiré.',
    'invalid_email_or_password'     => 'Adresse e-mail ou mot de passe invalide.',
    'no_token_provided'             => 'Aucun token fourni.',
    'token_too_old'                 => 'Token invalide ou trop ancien pour être rafraîchi.',
    'unauthenticated'               => 'Non authentifié.',
    'unauthorized'                  => 'Non autorisé.',
    'account_suspended'             => 'Votre compte a été suspendu. Contactez le support.',
    'two_factor_required'           => 'L\'authentification à deux facteurs est requise.',
    'two_factor_invalid'            => 'Code d\'authentification à deux facteurs invalide.',
    'session_expired'               => 'Votre session a expiré. Veuillez vous reconnecter.',
    'logout_success'                => 'Déconnexion réussie.',

    // Patient / Record
    'no_patient_record'             => 'Aucun dossier patient lié à ce compte.',
    'record_not_found'              => 'Dossier introuvable.',
    'record_created'                => 'Dossier créé avec succès.',
    'record_updated'                => 'Dossier mis à jour avec succès.',
    'record_deleted'                => 'Dossier supprimé avec succès.',

    // Consent
    'consent_not_found'             => 'Demande de consentement introuvable ou déjà traitée.',
    'consent_grant_not_found'       => 'Autorisation de consentement active introuvable.',
    'consent_revoked'               => 'Consentement révoqué. Les tokens existants ont été invalidés.',
    'consent_approved'              => 'Consentement approuvé.',
    'consent_denied'                => 'Consentement refusé.',

    // Family
    'no_user_linked'                => 'Aucun compte utilisateur lié à ce patient.',
    'cannot_link_self'              => 'Vous ne pouvez pas vous lier vous-même comme dépendant.',
    'dependent_added'               => 'Dépendant lié avec succès.',
    'dependent_removed'             => 'Dépendant supprimé.',

    // Appointments
    'appointment_not_found'         => 'Rendez-vous introuvable.',
    'appointment_created'           => 'Rendez-vous planifié avec succès.',
    'appointment_updated'           => 'Rendez-vous mis à jour.',
    'appointment_cancelled'         => 'Rendez-vous annulé.',
    'appointment_already_cancelled' => 'Le rendez-vous est déjà annulé.',
    'waitlist_dispatched'           => 'Tâche de remplissage de liste d\'attente envoyée.',
    'slot_not_available'            => 'Le créneau sélectionné n\'est plus disponible.',

    // Blood / Inventory
    'units_added'                   => 'Unités ajoutées.',
    'units_removed'                 => 'Unités retirées.',
    'insufficient_stock'            => 'Stock insuffisant.',

    // Lab
    'order_not_found'               => 'Bon de laboratoire introuvable.',
    'sample_not_found'              => 'Échantillon introuvable.',
    'result_submitted'              => 'Résultat soumis avec succès.',
    'result_not_found'              => 'Résultat introuvable.',

    // Prescriptions
    'prescription_not_found'        => 'Ordonnance introuvable.',
    'prescription_dispensed'        => 'Ordonnance délivrée.',
    'prescription_cancelled'        => 'Ordonnance annulée.',

    // Referrals
    'referral_not_found'            => 'Référence introuvable.',
    'referral_created'              => 'Référence créée.',
    'referral_accepted'             => 'Référence acceptée.',
    'referral_rejected'             => 'Référence rejetée.',

    // Insurance
    'policy_not_found'              => 'Police d\'assurance introuvable.',
    'claim_not_found'               => 'Demande de remboursement introuvable.',
    'claim_submitted'               => 'Demande de remboursement soumise avec succès.',
    'preauth_not_found'             => 'Pré-autorisation introuvable.',
    'preauth_approved'              => 'Pré-autorisation approuvée.',
    'preauth_rejected'              => 'Pré-autorisation rejetée.',

    // QR / Health ID
    'qr_generated'                  => 'Code QR généré.',
    'qr_invalid'                    => 'Code QR invalide ou expiré.',
    'health_id_not_found'           => 'Identifiant de santé introuvable.',

    // Developer / API apps
    'app_not_found'                 => 'Application introuvable.',
    'app_created'                   => 'Application créée.',
    'app_deleted'                   => 'Application supprimée.',
    'webhook_not_found'             => 'Webhook introuvable.',
    'webhook_created'               => 'Webhook créé.',
    'webhook_deleted'               => 'Webhook supprimé.',
    'production_request_submitted'  => 'Demande d\'accès en production soumise.',

    // Generic
    'success'                       => 'Opération effectuée avec succès.',
    'error'                         => 'Une erreur est survenue. Veuillez réessayer.',
    'not_found'                     => 'La ressource demandée est introuvable.',
    'validation_failed'             => 'Échec de la validation.',
    'forbidden'                     => 'Vous n\'avez pas la permission d\'effectuer cette action.',
    'server_error'                  => 'Une erreur serveur est survenue. Contactez le support.',
    'too_many_requests'             => 'Trop de requêtes. Veuillez patienter avant de réessayer.',
    'service_unavailable'           => 'Service temporairement indisponible.',
    'feature_not_available'         => 'Cette fonctionnalité n\'est pas disponible sur votre plan.',
    'file_too_large'                => 'La taille du fichier dépasse la limite maximale autorisée.',
    'invalid_file_type'             => 'Type de fichier non autorisé.',
    'export_ready'                  => 'Export prêt.',
    'email_sent'                    => 'E-mail envoyé.',
    'sync_complete'                 => 'Synchronisation terminée.',
    'job_queued'                    => 'Tâche mise en file d\'attente pour traitement.',
];
```

- [ ] **Step 3: Verify key count matches**

```bash
php -r "
  \$en = require 'lang/en/api.php';
  \$fr = require 'lang/fr/api.php';
  \$missing_fr = array_diff_key(\$en, \$fr);
  \$missing_en = array_diff_key(\$fr, \$en);
  echo 'EN keys: ' . count(\$en) . PHP_EOL;
  echo 'FR keys: ' . count(\$fr) . PHP_EOL;
  if (\$missing_fr) echo 'Missing in FR: ' . implode(', ', array_keys(\$missing_fr)) . PHP_EOL;
  if (\$missing_en) echo 'Missing in EN: ' . implode(', ', array_keys(\$missing_en)) . PHP_EOL;
  if (!(\$missing_fr || \$missing_en)) echo 'PERFECT PARITY' . PHP_EOL;
"
```
Expected: `PERFECT PARITY`

- [ ] **Step 4: Commit**

```bash
git add lang/en/api.php lang/fr/api.php
git commit -m "feat(i18n): create api.php EN/FR lang files for all API response messages"
```

---

### Task 2: Create `flash.php` lang files (EN + FR)

Web controller flash/session messages shown in portal alerts and toasts.

**Files:**
- Create: `lang/en/flash.php`
- Create: `lang/fr/flash.php`

- [ ] **Step 1: Create `lang/en/flash.php`**

```php
<?php
return [
    // Appointments (Admin)
    'appointment_cancelled'         => 'Appointment cancelled successfully.',
    'appointment_cancel_failed'     => 'Failed to cancel appointment.',
    'appointment_only_cancelled_deletable' => 'Only cancelled appointments can be deleted.',
    'appointment_deleted'           => 'Appointment deleted.',
    'appointment_booked'            => 'Appointment booked successfully.',
    'appointment_updated'           => 'Appointment updated.',
    'appointment_confirmed'         => 'Appointment confirmed.',
    'appointment_rescheduled'       => 'Appointment rescheduled.',

    // Users
    'user_created'                  => 'User created successfully.',
    'user_updated'                  => 'User updated.',
    'user_deleted'                  => 'User deleted.',
    'user_suspended'                => 'User account suspended.',
    'user_reinstated'               => 'User account reinstated.',
    'user_role_updated'             => 'User role updated.',
    'password_reset_sent'           => 'Password reset link sent.',
    'password_changed'              => 'Password changed successfully.',

    // Facilities
    'facility_created'              => 'Facility created.',
    'facility_updated'              => 'Facility updated.',
    'facility_deleted'              => 'Facility deleted.',
    'facility_activated'            => 'Facility activated.',
    'facility_deactivated'          => 'Facility deactivated.',

    // Staff
    'staff_created'                 => 'Staff member created.',
    'staff_updated'                 => 'Staff record updated.',
    'staff_deleted'                 => 'Staff record deleted.',
    'staff_credentialed'            => 'Credentials verified.',
    'staff_suspended'               => 'Staff account suspended.',

    // Patients
    'patient_created'               => 'Patient record created.',
    'patient_updated'               => 'Patient record updated.',
    'patient_deleted'               => 'Patient record deleted.',
    'patient_merged'                => 'Patient records merged.',

    // Roles / Permissions
    'role_created'                  => 'Role created.',
    'role_updated'                  => 'Role updated.',
    'role_deleted'                  => 'Role deleted.',
    'permission_granted'            => 'Permission granted.',
    'permission_revoked'            => 'Permission revoked.',

    // Organizations
    'org_created'                   => 'Organisation created.',
    'org_updated'                   => 'Organisation updated.',
    'org_deleted'                   => 'Organisation deleted.',

    // Billing / Financial
    'invoice_created'               => 'Invoice created.',
    'invoice_paid'                  => 'Invoice marked as paid.',
    'invoice_cancelled'             => 'Invoice cancelled.',
    'payment_recorded'              => 'Payment recorded.',
    'subscription_updated'          => 'Subscription updated.',

    // Data import
    'import_queued'                 => 'Import job queued. You will be notified when complete.',
    'import_failed'                 => 'Import failed. Please check the file and try again.',
    'import_complete'               => 'Import completed successfully.',

    // CDSS rules
    'rule_created'                  => 'Clinical decision rule created.',
    'rule_updated'                  => 'Clinical decision rule updated.',
    'rule_deleted'                  => 'Clinical decision rule deleted.',
    'rule_activated'                => 'Rule activated.',
    'rule_deactivated'              => 'Rule deactivated.',

    // Settings / Config
    'settings_saved'                => 'Settings saved.',
    'settings_reset'                => 'Settings reset to defaults.',
    'logo_uploaded'                 => 'Logo uploaded.',
    'certificate_uploaded'          => 'Certificate uploaded.',

    // Security
    'audit_log_exported'            => 'Audit log exported.',
    'ip_whitelisted'                => 'IP address whitelisted.',
    'ip_removed'                    => 'IP address removed.',
    'session_terminated'            => 'Session terminated.',
    'two_factor_enabled'            => 'Two-factor authentication enabled.',
    'two_factor_disabled'           => 'Two-factor authentication disabled.',

    // Generic
    'saved'                         => 'Saved.',
    'deleted'                       => 'Deleted.',
    'created'                       => 'Created.',
    'updated'                       => 'Updated.',
    'error_generic'                 => 'An error occurred. Please try again.',
    'access_denied'                 => 'Access denied.',
    'not_found'                     => 'Resource not found.',
    'file_uploaded'                 => 'File uploaded.',
    'file_deleted'                  => 'File deleted.',
    'email_sent'                    => 'Email sent.',
    'exported'                      => 'Data exported.',
    'printed'                       => 'Sent to printer.',
    'action_completed'              => 'Action completed.',
];
```

- [ ] **Step 2: Create `lang/fr/flash.php`**

```php
<?php
return [
    // Appointments (Admin)
    'appointment_cancelled'         => 'Rendez-vous annulé avec succès.',
    'appointment_cancel_failed'     => 'Échec de l\'annulation du rendez-vous.',
    'appointment_only_cancelled_deletable' => 'Seuls les rendez-vous annulés peuvent être supprimés.',
    'appointment_deleted'           => 'Rendez-vous supprimé.',
    'appointment_booked'            => 'Rendez-vous réservé avec succès.',
    'appointment_updated'           => 'Rendez-vous mis à jour.',
    'appointment_confirmed'         => 'Rendez-vous confirmé.',
    'appointment_rescheduled'       => 'Rendez-vous reprogrammé.',

    // Users
    'user_created'                  => 'Utilisateur créé avec succès.',
    'user_updated'                  => 'Utilisateur mis à jour.',
    'user_deleted'                  => 'Utilisateur supprimé.',
    'user_suspended'                => 'Compte utilisateur suspendu.',
    'user_reinstated'               => 'Compte utilisateur réactivé.',
    'user_role_updated'             => 'Rôle utilisateur mis à jour.',
    'password_reset_sent'           => 'Lien de réinitialisation du mot de passe envoyé.',
    'password_changed'              => 'Mot de passe modifié avec succès.',

    // Facilities
    'facility_created'              => 'Établissement créé.',
    'facility_updated'              => 'Établissement mis à jour.',
    'facility_deleted'              => 'Établissement supprimé.',
    'facility_activated'            => 'Établissement activé.',
    'facility_deactivated'          => 'Établissement désactivé.',

    // Staff
    'staff_created'                 => 'Membre du personnel créé.',
    'staff_updated'                 => 'Dossier du personnel mis à jour.',
    'staff_deleted'                 => 'Dossier du personnel supprimé.',
    'staff_credentialed'            => 'Accréditations vérifiées.',
    'staff_suspended'               => 'Compte du personnel suspendu.',

    // Patients
    'patient_created'               => 'Dossier patient créé.',
    'patient_updated'               => 'Dossier patient mis à jour.',
    'patient_deleted'               => 'Dossier patient supprimé.',
    'patient_merged'                => 'Dossiers patients fusionnés.',

    // Roles / Permissions
    'role_created'                  => 'Rôle créé.',
    'role_updated'                  => 'Rôle mis à jour.',
    'role_deleted'                  => 'Rôle supprimé.',
    'permission_granted'            => 'Permission accordée.',
    'permission_revoked'            => 'Permission révoquée.',

    // Organizations
    'org_created'                   => 'Organisation créée.',
    'org_updated'                   => 'Organisation mise à jour.',
    'org_deleted'                   => 'Organisation supprimée.',

    // Billing / Financial
    'invoice_created'               => 'Facture créée.',
    'invoice_paid'                  => 'Facture marquée comme payée.',
    'invoice_cancelled'             => 'Facture annulée.',
    'payment_recorded'              => 'Paiement enregistré.',
    'subscription_updated'          => 'Abonnement mis à jour.',

    // Data import
    'import_queued'                 => 'Tâche d\'importation mise en file d\'attente. Vous serez notifié à la fin.',
    'import_failed'                 => 'Importation échouée. Vérifiez le fichier et réessayez.',
    'import_complete'               => 'Importation terminée avec succès.',

    // CDSS rules
    'rule_created'                  => 'Règle de décision clinique créée.',
    'rule_updated'                  => 'Règle de décision clinique mise à jour.',
    'rule_deleted'                  => 'Règle de décision clinique supprimée.',
    'rule_activated'                => 'Règle activée.',
    'rule_deactivated'              => 'Règle désactivée.',

    // Settings / Config
    'settings_saved'                => 'Paramètres enregistrés.',
    'settings_reset'                => 'Paramètres réinitialisés par défaut.',
    'logo_uploaded'                 => 'Logo téléchargé.',
    'certificate_uploaded'          => 'Certificat téléchargé.',

    // Security
    'audit_log_exported'            => 'Journal d\'audit exporté.',
    'ip_whitelisted'                => 'Adresse IP mise sur liste blanche.',
    'ip_removed'                    => 'Adresse IP retirée.',
    'session_terminated'            => 'Session terminée.',
    'two_factor_enabled'            => 'Authentification à deux facteurs activée.',
    'two_factor_disabled'           => 'Authentification à deux facteurs désactivée.',

    // Generic
    'saved'                         => 'Enregistré.',
    'deleted'                       => 'Supprimé.',
    'created'                       => 'Créé.',
    'updated'                       => 'Mis à jour.',
    'error_generic'                 => 'Une erreur est survenue. Veuillez réessayer.',
    'access_denied'                 => 'Accès refusé.',
    'not_found'                     => 'Ressource introuvable.',
    'file_uploaded'                 => 'Fichier téléchargé.',
    'file_deleted'                  => 'Fichier supprimé.',
    'email_sent'                    => 'E-mail envoyé.',
    'exported'                      => 'Données exportées.',
    'printed'                       => 'Envoyé à l\'imprimante.',
    'action_completed'              => 'Action effectuée.',
];
```

- [ ] **Step 3: Verify parity**

```bash
php -r "
  \$en = require 'lang/en/flash.php';
  \$fr = require 'lang/fr/flash.php';
  \$missing = array_diff_key(\$en, \$fr);
  echo count(\$en) === count(\$fr) && !(\$missing) ? 'PERFECT PARITY' : 'MISMATCH: ' . implode(', ', array_keys(\$missing));
  echo PHP_EOL;
"
```

- [ ] **Step 4: Commit**

```bash
git add lang/en/flash.php lang/fr/flash.php
git commit -m "feat(i18n): create flash.php EN/FR lang files for web controller messages"
```

---

### Task 3: Create `validation.php` and `passwords.php` (EN + FR)

Laravel falls back to hardcoded English if these don't exist.

**Files:**
- Create: `lang/en/validation.php`
- Create: `lang/fr/validation.php`
- Create: `lang/en/passwords.php`
- Create: `lang/fr/passwords.php`

- [ ] **Step 1: Create `lang/en/validation.php`**

```php
<?php
return [
    'accepted'             => 'The :attribute must be accepted.',
    'accepted_if'          => 'The :attribute must be accepted when :other is :value.',
    'active_url'           => 'The :attribute is not a valid URL.',
    'after'                => 'The :attribute must be a date after :date.',
    'after_or_equal'       => 'The :attribute must be a date after or equal to :date.',
    'alpha'                => 'The :attribute must only contain letters.',
    'alpha_dash'           => 'The :attribute must only contain letters, numbers, dashes and underscores.',
    'alpha_num'            => 'The :attribute must only contain letters and numbers.',
    'array'                => 'The :attribute must be an array.',
    'before'               => 'The :attribute must be a date before :date.',
    'before_or_equal'      => 'The :attribute must be a date before or equal to :date.',
    'between'              => [
        'array'   => 'The :attribute must have between :min and :max items.',
        'file'    => 'The :attribute must be between :min and :max kilobytes.',
        'numeric' => 'The :attribute must be between :min and :max.',
        'string'  => 'The :attribute must be between :min and :max characters.',
    ],
    'boolean'              => 'The :attribute field must be true or false.',
    'confirmed'            => 'The :attribute confirmation does not match.',
    'date'                 => 'The :attribute is not a valid date.',
    'date_equals'          => 'The :attribute must be a date equal to :date.',
    'date_format'          => 'The :attribute does not match the format :format.',
    'declined'             => 'The :attribute must be declined.',
    'different'            => 'The :attribute and :other must be different.',
    'digits'               => 'The :attribute must be :digits digits.',
    'digits_between'       => 'The :attribute must be between :min and :max digits.',
    'dimensions'           => 'The :attribute has invalid image dimensions.',
    'distinct'             => 'The :attribute field has a duplicate value.',
    'doesnt_end_with'      => 'The :attribute may not end with one of the following: :values.',
    'doesnt_start_with'    => 'The :attribute may not start with one of the following: :values.',
    'email'                => 'The :attribute must be a valid email address.',
    'ends_with'            => 'The :attribute must end with one of the following: :values.',
    'enum'                 => 'The selected :attribute is invalid.',
    'exists'               => 'The selected :attribute is invalid.',
    'file'                 => 'The :attribute must be a file.',
    'filled'               => 'The :attribute field must have a value.',
    'gt'                   => [
        'array'   => 'The :attribute must have more than :value items.',
        'file'    => 'The :attribute must be greater than :value kilobytes.',
        'numeric' => 'The :attribute must be greater than :value.',
        'string'  => 'The :attribute must be greater than :value characters.',
    ],
    'gte'                  => [
        'array'   => 'The :attribute must have :value items or more.',
        'file'    => 'The :attribute must be greater than or equal to :value kilobytes.',
        'numeric' => 'The :attribute must be greater than or equal to :value.',
        'string'  => 'The :attribute must be greater than or equal to :value characters.',
    ],
    'image'                => 'The :attribute must be an image.',
    'in'                   => 'The selected :attribute is invalid.',
    'in_array'             => 'The :attribute field does not exist in :other.',
    'integer'              => 'The :attribute must be an integer.',
    'ip'                   => 'The :attribute must be a valid IP address.',
    'ipv4'                 => 'The :attribute must be a valid IPv4 address.',
    'ipv6'                 => 'The :attribute must be a valid IPv6 address.',
    'json'                 => 'The :attribute must be a valid JSON string.',
    'lowercase'            => 'The :attribute must be lowercase.',
    'lt'                   => [
        'array'   => 'The :attribute must have less than :value items.',
        'file'    => 'The :attribute must be less than :value kilobytes.',
        'numeric' => 'The :attribute must be less than :value.',
        'string'  => 'The :attribute must be less than :value characters.',
    ],
    'lte'                  => [
        'array'   => 'The :attribute must not have more than :value items.',
        'file'    => 'The :attribute must be less than or equal to :value kilobytes.',
        'numeric' => 'The :attribute must be less than or equal to :value.',
        'string'  => 'The :attribute must be less than or equal to :value characters.',
    ],
    'mac_address'          => 'The :attribute must be a valid MAC address.',
    'max'                  => [
        'array'   => 'The :attribute must not have more than :max items.',
        'file'    => 'The :attribute must not be greater than :max kilobytes.',
        'numeric' => 'The :attribute must not be greater than :max.',
        'string'  => 'The :attribute must not be greater than :max characters.',
    ],
    'max_digits'           => 'The :attribute must not have more than :max digits.',
    'mimes'                => 'The :attribute must be a file of type: :values.',
    'mimetypes'            => 'The :attribute must be a file of type: :values.',
    'min'                  => [
        'array'   => 'The :attribute must have at least :min items.',
        'file'    => 'The :attribute must be at least :min kilobytes.',
        'numeric' => 'The :attribute must be at least :min.',
        'string'  => 'The :attribute must be at least :min characters.',
    ],
    'min_digits'           => 'The :attribute must have at least :min digits.',
    'missing'              => 'The :attribute field must be missing.',
    'missing_if'           => 'The :attribute field must be missing when :other is :value.',
    'missing_unless'       => 'The :attribute field must be missing unless :other is :value.',
    'missing_with'         => 'The :attribute field must be missing when :values is present.',
    'missing_with_all'     => 'The :attribute field must be missing when :values are present.',
    'multiple_of'          => 'The :attribute must be a multiple of :value.',
    'not_in'               => 'The selected :attribute is invalid.',
    'not_regex'            => 'The :attribute format is invalid.',
    'numeric'              => 'The :attribute must be a number.',
    'password'             => [
        'letters'       => 'The :attribute must contain at least one letter.',
        'mixed'         => 'The :attribute must contain at least one uppercase and one lowercase letter.',
        'numbers'       => 'The :attribute must contain at least one number.',
        'symbols'       => 'The :attribute must contain at least one symbol.',
        'uncompromised' => 'The given :attribute has appeared in a data leak. Please choose a different :attribute.',
    ],
    'present'              => 'The :attribute field must be present.',
    'prohibited'           => 'The :attribute field is prohibited.',
    'prohibited_if'        => 'The :attribute field is prohibited when :other is :value.',
    'prohibited_unless'    => 'The :attribute field is prohibited unless :other is in :values.',
    'prohibits'            => 'The :attribute field prohibits :other from being present.',
    'regex'                => 'The :attribute format is invalid.',
    'required'             => 'The :attribute field is required.',
    'required_array_keys'  => 'The :attribute field must contain entries for: :values.',
    'required_if'          => 'The :attribute field is required when :other is :value.',
    'required_if_accepted' => 'The :attribute field is required when :other is accepted.',
    'required_unless'      => 'The :attribute field is required unless :other is in :values.',
    'required_with'        => 'The :attribute field is required when :values is present.',
    'required_with_all'    => 'The :attribute field is required when :values are present.',
    'required_without'     => 'The :attribute field is required when :values is not present.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'same'                 => 'The :attribute and :other must match.',
    'size'                 => [
        'array'   => 'The :attribute must contain :size items.',
        'file'    => 'The :attribute must be :size kilobytes.',
        'numeric' => 'The :attribute must be :size.',
        'string'  => 'The :attribute must be :size characters.',
    ],
    'starts_with'          => 'The :attribute must start with one of the following: :values.',
    'string'               => 'The :attribute must be a string.',
    'timezone'             => 'The :attribute must be a valid timezone.',
    'unique'               => 'The :attribute has already been taken.',
    'uploaded'             => 'The :attribute failed to upload.',
    'uppercase'            => 'The :attribute must be uppercase.',
    'url'                  => 'The :attribute must be a valid URL.',
    'ulid'                 => 'The :attribute must be a valid ULID.',
    'uuid'                 => 'The :attribute must be a valid UUID.',

    'attributes' => [
        'name'                  => 'name',
        'email'                 => 'email address',
        'password'              => 'password',
        'password_confirmation' => 'password confirmation',
        'date'                  => 'date',
        'date_of_birth'         => 'date of birth',
        'phone'                 => 'phone number',
        'address'               => 'address',
        'city'                  => 'city',
        'country'               => 'country',
        'facility_id'           => 'facility',
        'role'                  => 'role',
        'file'                  => 'file',
        'message'               => 'message',
        'type'                  => 'type',
    ],
];
```

- [ ] **Step 2: Create `lang/fr/validation.php`**

```php
<?php
return [
    'accepted'             => 'Le champ :attribute doit être accepté.',
    'accepted_if'          => 'Le champ :attribute doit être accepté quand :other vaut :value.',
    'active_url'           => 'Le champ :attribute n\'est pas une URL valide.',
    'after'                => 'Le champ :attribute doit être une date postérieure au :date.',
    'after_or_equal'       => 'Le champ :attribute doit être une date postérieure ou égale au :date.',
    'alpha'                => 'Le champ :attribute doit contenir uniquement des lettres.',
    'alpha_dash'           => 'Le champ :attribute doit contenir uniquement des lettres, des chiffres, des tirets et des underscores.',
    'alpha_num'            => 'Le champ :attribute doit contenir uniquement des lettres et des chiffres.',
    'array'                => 'Le champ :attribute doit être un tableau.',
    'before'               => 'Le champ :attribute doit être une date antérieure au :date.',
    'before_or_equal'      => 'Le champ :attribute doit être une date antérieure ou égale au :date.',
    'between'              => [
        'array'   => 'Le champ :attribute doit avoir entre :min et :max éléments.',
        'file'    => 'Le fichier :attribute doit avoir une taille comprise entre :min et :max kilo-octets.',
        'numeric' => 'La valeur de :attribute doit être comprise entre :min et :max.',
        'string'  => 'Le texte :attribute doit avoir entre :min et :max caractères.',
    ],
    'boolean'              => 'Le champ :attribute doit être vrai ou faux.',
    'confirmed'            => 'La confirmation du champ :attribute ne correspond pas.',
    'date'                 => 'Le champ :attribute n\'est pas une date valide.',
    'date_equals'          => 'Le champ :attribute doit être une date égale à :date.',
    'date_format'          => 'Le champ :attribute ne correspond pas au format :format.',
    'declined'             => 'Le champ :attribute doit être refusé.',
    'different'            => 'Les champs :attribute et :other doivent être différents.',
    'digits'               => 'Le champ :attribute doit avoir :digits chiffres.',
    'digits_between'       => 'Le champ :attribute doit avoir entre :min et :max chiffres.',
    'dimensions'           => 'Le champ :attribute a des dimensions d\'image invalides.',
    'distinct'             => 'Le champ :attribute a une valeur en double.',
    'doesnt_end_with'      => 'Le champ :attribute ne doit pas terminer par : :values.',
    'doesnt_start_with'    => 'Le champ :attribute ne doit pas commencer par : :values.',
    'email'                => 'Le champ :attribute doit être une adresse e-mail valide.',
    'ends_with'            => 'Le champ :attribute doit se terminer par : :values.',
    'enum'                 => 'La valeur sélectionnée pour :attribute est invalide.',
    'exists'               => 'La valeur sélectionnée pour :attribute est invalide.',
    'file'                 => 'Le champ :attribute doit être un fichier.',
    'filled'               => 'Le champ :attribute doit avoir une valeur.',
    'gt'                   => [
        'array'   => 'Le champ :attribute doit avoir plus de :value éléments.',
        'file'    => 'Le fichier :attribute doit être supérieur à :value kilo-octets.',
        'numeric' => 'La valeur de :attribute doit être supérieure à :value.',
        'string'  => 'Le texte :attribute doit avoir plus de :value caractères.',
    ],
    'gte'                  => [
        'array'   => 'Le champ :attribute doit avoir :value éléments ou plus.',
        'file'    => 'Le fichier :attribute doit être supérieur ou égal à :value kilo-octets.',
        'numeric' => 'La valeur de :attribute doit être supérieure ou égale à :value.',
        'string'  => 'Le texte :attribute doit avoir :value caractères ou plus.',
    ],
    'image'                => 'Le champ :attribute doit être une image.',
    'in'                   => 'La valeur sélectionnée pour :attribute est invalide.',
    'in_array'             => 'Le champ :attribute n\'existe pas dans :other.',
    'integer'              => 'Le champ :attribute doit être un entier.',
    'ip'                   => 'Le champ :attribute doit être une adresse IP valide.',
    'ipv4'                 => 'Le champ :attribute doit être une adresse IPv4 valide.',
    'ipv6'                 => 'Le champ :attribute doit être une adresse IPv6 valide.',
    'json'                 => 'Le champ :attribute doit être une chaîne JSON valide.',
    'lowercase'            => 'Le champ :attribute doit être en minuscules.',
    'lt'                   => [
        'array'   => 'Le champ :attribute doit avoir moins de :value éléments.',
        'file'    => 'Le fichier :attribute doit être inférieur à :value kilo-octets.',
        'numeric' => 'La valeur de :attribute doit être inférieure à :value.',
        'string'  => 'Le texte :attribute doit avoir moins de :value caractères.',
    ],
    'lte'                  => [
        'array'   => 'Le champ :attribute ne doit pas avoir plus de :value éléments.',
        'file'    => 'Le fichier :attribute doit être inférieur ou égal à :value kilo-octets.',
        'numeric' => 'La valeur de :attribute doit être inférieure ou égale à :value.',
        'string'  => 'Le texte :attribute doit avoir :value caractères ou moins.',
    ],
    'mac_address'          => 'Le champ :attribute doit être une adresse MAC valide.',
    'max'                  => [
        'array'   => 'Le champ :attribute ne doit pas avoir plus de :max éléments.',
        'file'    => 'Le fichier :attribute ne doit pas dépasser :max kilo-octets.',
        'numeric' => 'La valeur de :attribute ne doit pas être supérieure à :max.',
        'string'  => 'Le texte :attribute ne doit pas dépasser :max caractères.',
    ],
    'max_digits'           => 'Le champ :attribute ne doit pas avoir plus de :max chiffres.',
    'mimes'                => 'Le champ :attribute doit être un fichier de type : :values.',
    'mimetypes'            => 'Le champ :attribute doit être un fichier de type : :values.',
    'min'                  => [
        'array'   => 'Le champ :attribute doit avoir au moins :min éléments.',
        'file'    => 'Le fichier :attribute doit avoir au moins :min kilo-octets.',
        'numeric' => 'La valeur de :attribute doit être au moins :min.',
        'string'  => 'Le texte :attribute doit avoir au moins :min caractères.',
    ],
    'min_digits'           => 'Le champ :attribute doit avoir au moins :min chiffres.',
    'missing'              => 'Le champ :attribute doit être absent.',
    'missing_if'           => 'Le champ :attribute doit être absent quand :other vaut :value.',
    'missing_unless'       => 'Le champ :attribute doit être absent sauf si :other vaut :value.',
    'missing_with'         => 'Le champ :attribute doit être absent quand :values est présent.',
    'missing_with_all'     => 'Le champ :attribute doit être absent quand :values sont présents.',
    'multiple_of'          => 'La valeur de :attribute doit être un multiple de :value.',
    'not_in'               => 'La valeur sélectionnée pour :attribute est invalide.',
    'not_regex'            => 'Le format du champ :attribute est invalide.',
    'numeric'              => 'Le champ :attribute doit être un nombre.',
    'password'             => [
        'letters'       => 'Le champ :attribute doit contenir au moins une lettre.',
        'mixed'         => 'Le champ :attribute doit contenir au moins une majuscule et une minuscule.',
        'numbers'       => 'Le champ :attribute doit contenir au moins un chiffre.',
        'symbols'       => 'Le champ :attribute doit contenir au moins un symbole.',
        'uncompromised' => 'Le :attribute indiqué est apparu dans une fuite de données. Veuillez choisir un autre :attribute.',
    ],
    'present'              => 'Le champ :attribute doit être présent.',
    'prohibited'           => 'Le champ :attribute est interdit.',
    'prohibited_if'        => 'Le champ :attribute est interdit quand :other vaut :value.',
    'prohibited_unless'    => 'Le champ :attribute est interdit sauf si :other est dans :values.',
    'prohibits'            => 'Le champ :attribute interdit la présence de :other.',
    'regex'                => 'Le format du champ :attribute est invalide.',
    'required'             => 'Le champ :attribute est obligatoire.',
    'required_array_keys'  => 'Le champ :attribute doit contenir des entrées pour : :values.',
    'required_if'          => 'Le champ :attribute est obligatoire quand :other vaut :value.',
    'required_if_accepted' => 'Le champ :attribute est obligatoire quand :other est accepté.',
    'required_unless'      => 'Le champ :attribute est obligatoire sauf si :other est dans :values.',
    'required_with'        => 'Le champ :attribute est obligatoire quand :values est présent.',
    'required_with_all'    => 'Le champ :attribute est obligatoire quand :values sont présents.',
    'required_without'     => 'Le champ :attribute est obligatoire quand :values est absent.',
    'required_without_all' => 'Le champ :attribute est obligatoire quand aucun de :values n\'est présent.',
    'same'                 => 'Les champs :attribute et :other doivent correspondre.',
    'size'                 => [
        'array'   => 'Le champ :attribute doit contenir :size éléments.',
        'file'    => 'Le fichier :attribute doit avoir une taille de :size kilo-octets.',
        'numeric' => 'La valeur de :attribute doit être :size.',
        'string'  => 'Le texte :attribute doit avoir :size caractères.',
    ],
    'starts_with'          => 'Le champ :attribute doit commencer par : :values.',
    'string'               => 'Le champ :attribute doit être une chaîne de caractères.',
    'timezone'             => 'Le champ :attribute doit être un fuseau horaire valide.',
    'unique'               => 'La valeur du champ :attribute est déjà utilisée.',
    'uploaded'             => 'Le champ :attribute n\'a pas pu être téléchargé.',
    'uppercase'            => 'Le champ :attribute doit être en majuscules.',
    'url'                  => 'Le champ :attribute doit être une URL valide.',
    'ulid'                 => 'Le champ :attribute doit être un ULID valide.',
    'uuid'                 => 'Le champ :attribute doit être un UUID valide.',

    'attributes' => [
        'name'                  => 'nom',
        'email'                 => 'adresse e-mail',
        'password'              => 'mot de passe',
        'password_confirmation' => 'confirmation du mot de passe',
        'date'                  => 'date',
        'date_of_birth'         => 'date de naissance',
        'phone'                 => 'numéro de téléphone',
        'address'               => 'adresse',
        'city'                  => 'ville',
        'country'               => 'pays',
        'facility_id'           => 'établissement',
        'role'                  => 'rôle',
        'file'                  => 'fichier',
        'message'               => 'message',
        'type'                  => 'type',
    ],
];
```

- [ ] **Step 3: Create `lang/en/passwords.php`**

```php
<?php
return [
    'reset'     => 'Your password has been reset.',
    'sent'      => 'We have emailed your password reset link.',
    'throttled' => 'Please wait before retrying.',
    'token'     => 'This password reset token is invalid.',
    'user'      => 'We cannot find a user with that email address.',
];
```

- [ ] **Step 4: Create `lang/fr/passwords.php`**

```php
<?php
return [
    'reset'     => 'Votre mot de passe a été réinitialisé.',
    'sent'      => 'Nous vous avons envoyé par e-mail le lien de réinitialisation du mot de passe.',
    'throttled' => 'Veuillez patienter avant de réessayer.',
    'token'     => 'Ce token de réinitialisation du mot de passe est invalide.',
    'user'      => 'Nous ne trouvons pas d\'utilisateur avec cette adresse e-mail.',
];
```

- [ ] **Step 5: Commit**

```bash
git add lang/en/validation.php lang/fr/validation.php lang/en/passwords.php lang/fr/passwords.php
git commit -m "feat(i18n): add validation.php and passwords.php EN/FR — replaces Laravel hardcoded fallbacks"
```

---

### Task 4: Create the i18n parity audit script

This is the safety net used at the end of every wave to catch any key drift.

**Files:**
- Create: `scripts/i18n-audit.php`

- [ ] **Step 1: Create `scripts/i18n-audit.php`**

```php
<?php
/**
 * i18n parity audit — run: php scripts/i18n-audit.php
 * Checks every lang/en/*.php against lang/fr/*.php and reports mismatches.
 */

$enDir = __DIR__ . '/../lang/en';
$frDir = __DIR__ . '/../lang/fr';

$files = glob($enDir . '/*.php');
$errors = 0;

foreach ($files as $enFile) {
    $name = basename($enFile);
    $frFile = $frDir . '/' . $name;

    if (!file_exists($frFile)) {
        echo "MISSING FR FILE: $name\n";
        $errors++;
        continue;
    }

    $en = require $enFile;
    $fr = require $frFile;

    $flat = function(array $arr, string $prefix = '') use (&$flat): array {
        $result = [];
        foreach ($arr as $k => $v) {
            $key = $prefix ? "$prefix.$k" : $k;
            if (is_array($v)) {
                $result = array_merge($result, $flat($v, $key));
            } else {
                $result[$key] = $v;
            }
        }
        return $result;
    };

    $enFlat = $flat($en);
    $frFlat = $flat($fr);

    $missingFr = array_diff_key($enFlat, $frFlat);
    $missingEn = array_diff_key($frFlat, $enFlat);

    if ($missingFr || $missingEn) {
        echo "\n[$name]\n";
        foreach ($missingFr as $k => $_) echo "  MISSING FR: $k\n";
        foreach ($missingEn as $k => $_) echo "  MISSING EN: $k\n";
        $errors++;
    } else {
        echo "[$name] OK — " . count($enFlat) . " keys\n";
    }
}

// Check FR files that don't have an EN counterpart
foreach (glob($frDir . '/*.php') as $frFile) {
    $name = basename($frFile);
    if (!file_exists($enDir . '/' . $name)) {
        echo "MISSING EN FILE: $name\n";
        $errors++;
    }
}

echo "\n" . ($errors === 0 ? "✓ ALL FILES HAVE PERFECT 1:1 PARITY" : "✗ $errors file(s) have parity issues") . "\n";
exit($errors > 0 ? 1 : 0);
```

- [ ] **Step 2: Run it — baseline must pass for existing 18 files**

```bash
php scripts/i18n-audit.php
```
Expected: All 18 existing files show `OK`. After Wave 1 completes, 22 files should all show `OK`.

- [ ] **Step 3: Commit**

```bash
git add scripts/i18n-audit.php
git commit -m "feat(i18n): add i18n-audit.php parity checker script"
```

---

## Wave 2 — Public Blade Views

> **Pattern for every task in this wave:**
> 1. Find hardcoded strings in the blade file
> 2. Add keys to `lang/en/landing.php` or `lang/en/public.php` (whichever the file already uses)
> 3. Add 1:1 French translations to the corresponding `lang/fr/` file
> 4. Replace hardcoded strings with `{{ __('namespace.key') }}`
> 5. For JS arrays in `<script>` tags, use `@json(__('namespace.key'))` or pass via Blade `@php`
> 6. For meta titles/descriptions use `__()` inside `@section('title', __('...'))`
> 7. Run `php scripts/i18n-audit.php` after each file
> 8. Commit

### Task 5: `landing.blade.php` — public landing page (Home 1)

**Files:**
- Modify: `resources/views/public/landing.blade.php`
- Modify: `lang/en/landing.php` (add keys)
- Modify: `lang/fr/landing.php` (add 1:1 FR keys)

- [ ] **Step 1: Read the file and catalogue every hardcoded string**

```bash
grep -n '[A-Z][a-z]' resources/views/public/landing.blade.php | grep -v '{{' | grep -v '@' | grep -v '//' | head -60
```

- [ ] **Step 2: Add missing keys to `lang/en/landing.php`**

For every hardcoded string found, add a key. Example pattern:
```php
// In lang/en/landing.php — append to existing keys:
'slide_labels'          => ['Health ID', 'Consent & Access', 'Emergency Access', 'Connected Care Network'],
'consent_badge'         => 'Consent & Access',
'consent_h1'            => 'You decide who sees your health information.',
'consent_body_1'        => 'Every access request from a hospital, clinic, lab, or insurer requires your explicit approval — you see exactly who is asking and what they want.',
'consent_body_2'        => 'Granular scopes let you share only what is necessary. Revoke access at any time, from anywhere, with a full audit trail of every action.',
'consent_cta_demo'      => 'Request Partnership Demo',
'consent_cta_how'       => 'See How It Works',
'consent_feat_scope'    => 'Granular scope control',
'consent_feat_audit'    => 'Full access audit log',
'consent_feat_revoke'   => 'Revoke any time',
'consent_feat_notify'   => 'Real-time notifications',
'em_badge'              => 'Emergency Access',
'em_h1'                 => 'Critical information available when seconds matter.',
'em_body_1'             => 'Authorised emergency providers can access life-saving data — allergies, blood type, active medications — under a strict audited override when a patient cannot consent.',
'em_body_2'             => 'Every override is logged with provider identity, timestamp, and clinical justification. The patient is notified as soon as they are able.',
'em_trust_1'            => 'Life-saving data access',
'em_trust_2'            => 'Full audit trail',
'em_trust_3'            => 'Provider identity verified',
'em_trust_4'            => 'Patient notified after',
'network_badge'         => 'Connected Care Network',
'network_h1'            => 'Every facility, one patient record. Zero duplication.',
'network_body'          => 'FHIR R4-compliant exchange means your existing HIS or LIS plugs in without rebuilding workflows. The bridge agent handles sync, mapping, and reconciliation automatically.',
'network_feat_1'        => 'FHIR R4 compliant',
'network_feat_2'        => 'Works with existing HIS/LIS',
'network_feat_3'        => 'Auto-sync bridge agent',
// aria-labels
'aria_prev_slide'       => 'Previous slide',
'aria_next_slide'       => 'Next slide',
'aria_slide_n'          => 'Slide :n',
```

- [ ] **Step 3: Add same keys to `lang/fr/landing.php`**

```php
'slide_labels'          => ['Identifiant Santé', 'Consentement & Accès', 'Accès d\'Urgence', 'Réseau de Soins'],
'consent_badge'         => 'Consentement & Accès',
'consent_h1'            => 'Vous décidez qui voit vos informations de santé.',
'consent_body_1'        => 'Chaque demande d\'accès d\'un hôpital, d\'une clinique, d\'un laboratoire ou d\'un assureur nécessite votre approbation explicite — vous voyez exactement qui demande et ce qu\'il veut.',
'consent_body_2'        => 'Des portées granulaires vous permettent de partager uniquement ce qui est nécessaire. Révoquez l\'accès à tout moment, de n\'importe où, avec un journal d\'audit complet.',
'consent_cta_demo'      => 'Demander une Démo Partenariat',
'consent_cta_how'       => 'Voir Comment Ça Marche',
'consent_feat_scope'    => 'Contrôle de portée granulaire',
'consent_feat_audit'    => 'Journal d\'accès complet',
'consent_feat_revoke'   => 'Révocation à tout moment',
'consent_feat_notify'   => 'Notifications en temps réel',
'em_badge'              => 'Accès d\'Urgence',
'em_h1'                 => 'Informations critiques disponibles quand les secondes comptent.',
'em_body_1'             => 'Les prestataires d\'urgence autorisés peuvent accéder aux données vitales — allergies, groupe sanguin, médicaments actifs — sous un protocole strict d\'accès audité d\'urgence.',
'em_body_2'             => 'Chaque accès est enregistré avec l\'identité du prestataire, l\'horodatage et la justification clinique. Le patient est notifié dès qu\'il est en mesure de l\'être.',
'em_trust_1'            => 'Accès aux données vitales',
'em_trust_2'            => 'Journal d\'audit complet',
'em_trust_3'            => 'Identité du prestataire vérifiée',
'em_trust_4'            => 'Patient notifié après',
'network_badge'         => 'Réseau de Soins Connecté',
'network_h1'            => 'Chaque établissement, un seul dossier patient. Zéro duplication.',
'network_body'          => 'L\'échange conforme FHIR R4 signifie que votre SIH ou SIL existant s\'intègre sans reconstruire les workflows. L\'agent pont gère automatiquement la synchronisation, la cartographie et la réconciliation.',
'network_feat_1'        => 'Conforme FHIR R4',
'network_feat_2'        => 'Compatible SIH/SIL existant',
'network_feat_3'        => 'Agent pont de synchronisation auto',
'aria_prev_slide'       => 'Diapositive précédente',
'aria_next_slide'       => 'Diapositive suivante',
'aria_slide_n'          => 'Diapositive :n',
```

- [ ] **Step 4: Replace hardcoded strings in the Blade file**

Replace every hardcoded instance. Example:
```blade
{{-- Before --}}
<span>Consent &amp; Access</span>

{{-- After --}}
<span>{{ __('landing.consent_badge') }}</span>
```

For the JS carousel labels array:
```blade
{{-- Before --}}
var labels = ['Health ID','Consent & Access','Emergency Access','Connected Care Network'];

{{-- After --}}
var labels = @json(__('landing.slide_labels'));
```

For aria-labels with dynamic values:
```blade
{{-- Before --}}
aria-label="Slide 1: Health ID"

{{-- After --}}
aria-label="{{ __('landing.aria_slide_n', ['n' => '1']) }}"
```

- [ ] **Step 5: Run parity check**

```bash
php scripts/i18n-audit.php
```
Expected: all files OK, `landing.php` key count incremented equally in EN and FR.

- [ ] **Step 6: Commit**

```bash
git add resources/views/public/landing.blade.php lang/en/landing.php lang/fr/landing.php
git commit -m "feat(i18n): wrap all hardcoded strings in landing.blade.php"
```

---

### Task 6: `landing2.blade.php` — Home 2 page

Same pattern as Task 5. File already uses `landing.*` namespace extensively.

**Files:**
- Modify: `resources/views/public/landing2.blade.php`
- Modify: `lang/en/landing.php`
- Modify: `lang/fr/landing.php`

- [ ] **Step 1: Read and catalogue**
```bash
grep -n '[A-Z][a-z]' resources/views/public/landing2.blade.php | grep -v '{{' | grep -v '@' | grep -v '//' | head -60
```

- [ ] **Step 2: Add new keys to EN, then identical structure to FR (same as Task 5 process)**

Identify every hardcoded string, add to both lang files simultaneously, replace in blade.

- [ ] **Step 3: Handle `@section('title', ...)` and meta description**
```blade
{{-- Before --}}
@section('title', 'OpesCare | Secure Digital Health Records')

{{-- After --}}
@section('title', __('landing.page_title_home2'))
@section('meta_description', __('landing.page_desc_home2'))
```

```php
// lang/en/landing.php
'page_title_home2' => 'OpesCare | Secure Digital Health Records — Cameroon',
'page_desc_home2'  => 'OpesCare connects patients, clinicians, labs, pharmacies and insurers on one secure FHIR-compliant platform.',

// lang/fr/landing.php
'page_title_home2' => 'OpesCare | Dossiers de Santé Numériques Sécurisés — Cameroun',
'page_desc_home2'  => 'OpesCare connecte patients, cliniciens, laboratoires, pharmacies et assureurs sur une plateforme sécurisée conforme FHIR.',
```

- [ ] **Step 4: Parity check + commit**

```bash
php scripts/i18n-audit.php
git add resources/views/public/landing2.blade.php lang/en/landing.php lang/fr/landing.php
git commit -m "feat(i18n): wrap all hardcoded strings in landing2.blade.php"
```

---

### Task 7: Remaining public pages (about, how_it_works, contact, faq, help, privacy, security, terms, consent, developers, interoperability, status, legal/index, solutions/*)

These pages use the `public.*` namespace (already 1,284 keys). Add missing keys there.

**Files (16 files total):**
- Modify: each `resources/views/public/*.blade.php`
- Modify: `lang/en/public.php`
- Modify: `lang/fr/public.php`

> **Repeat the following sub-steps for each file:** about, how_it_works, contact, faq, help, privacy, security, terms, consent, developers, interoperability, status, legal/index, solutions/patients, solutions/hospitals, solutions/pharmacies, solutions/laboratories

- [ ] **Sub-step A: Audit hardcoded strings**
```bash
grep -n '[A-Z][a-z]' resources/views/public/FILENAME.blade.php | grep -v '{{' | grep -v '@' | grep -v '//' | head -80
```

- [ ] **Sub-step B: Add keys simultaneously to EN and FR**

Pattern for `lang/en/public.php`:
```php
// ── about.php additions ──
'about_title'       => 'About OpesCare',
'about_mission_h'   => 'Our Mission',
'about_provides_h'  => 'What OpesCare provides',
'about_provides_sub'=> 'A connected set of modules designed for the full patient journey.',
'about_principles_h'=> 'Our guiding principles',
'about_principles_sub' => 'The values that shape every design decision, workflow, and policy in the OpesCare platform.',
// ... (add all strings found in Step A)
```

Pattern for `lang/fr/public.php` (identical keys, French values):
```php
'about_title'       => 'À propos d\'OpesCare',
'about_mission_h'   => 'Notre Mission',
'about_provides_h'  => 'Ce qu\'OpesCare fournit',
'about_provides_sub'=> 'Un ensemble de modules connectés conçus pour le parcours de soins complet.',
'about_principles_h'=> 'Nos principes directeurs',
'about_principles_sub' => 'Les valeurs qui guident chaque décision de conception, workflow et politique de la plateforme OpesCare.',
```

- [ ] **Sub-step C: Replace in blade + handle meta titles**

- [ ] **Sub-step D: Parity check**
```bash
php scripts/i18n-audit.php
```

- [ ] **Sub-step E: Commit per file**
```bash
git add resources/views/public/FILENAME.blade.php lang/en/public.php lang/fr/public.php
git commit -m "feat(i18n): wrap hardcoded strings in public/FILENAME.blade.php"
```

---

## Wave 3 — Portal Views: Patient Portal

**Files (~15 views):**
- `resources/views/portals/patient/index.blade.php`
- `resources/views/portals/patient/profile.blade.php`
- `resources/views/portals/patient/appointments.blade.php`
- `resources/views/portals/patient/clinical.blade.php`
- `resources/views/portals/patient/consent.blade.php`
- `resources/views/portals/patient/documents.blade.php`
- `resources/views/portals/patient/allergies.blade.php`
- `resources/views/portals/patient/immunizations.blade.php`
- `resources/views/portals/patient/labs.blade.php`
- `resources/views/portals/patient/logs.blade.php`
- `resources/views/portals/patient/prescriptions.blade.php`
- `resources/views/portals/patient/insurance/*.blade.php`
- `resources/views/portals/patient/family/*.blade.php`
- Modify: `lang/en/public.php` (portal.patient.* keys)
- Modify: `lang/fr/public.php`

### Task 8: Patient portal views — all files

- [ ] **Step 1: Audit all patient portal views**
```bash
grep -rn '[A-Z][a-z]' resources/views/portals/patient/ | grep -v '{{' | grep -v '@' | grep -v '//' | grep -v 'class=' | grep -v 'id=' | grep -v 'href=' | grep -v 'src=' | grep -v 'data-' > /tmp/patient-hardcoded.txt
wc -l /tmp/patient-hardcoded.txt
```

- [ ] **Step 2: For each hardcoded string found: add EN key to `lang/en/public.php` under `portal_patient_*` prefix, add FR translation to `lang/fr/public.php`, replace in blade**

> The existing `public.php` already has `portal.*` keys (1,284 total). Add new keys under logical groupings e.g. `portal_patient_profile_*`, `portal_patient_appointments_*`.

- [ ] **Step 3: Parity check**
```bash
php scripts/i18n-audit.php
```

- [ ] **Step 4: Commit**
```bash
git add resources/views/portals/patient/ lang/en/public.php lang/fr/public.php
git commit -m "feat(i18n): bilingualize patient portal views"
```

---

## Wave 4 — Portal Views: Staff Portal

**Files (~40 views across subdirectories):**
- `portals/staff/index.blade.php` + analytics/, appointments/, billing/, cdss/, clinical/, data_import/, files/, hr/, immunizations/, inventory/, queue*.blade.php, referrals/, search.blade.php, supply_chain/, support.blade.php, telemedicine/, visits/, wards/
- Modify: `lang/en/public.php` (staff_portal.* keys already exist — fill gaps)
- Modify: `lang/fr/public.php`

### Task 9: Staff portal views — all files

- [ ] **Step 1: Audit**
```bash
grep -rn '[A-Z][a-z]' resources/views/portals/staff/ | grep -v '{{' | grep -v '@' | grep -v '//' | grep -v 'class=\|id=\|href=\|src=\|data-' > /tmp/staff-hardcoded.txt
wc -l /tmp/staff-hardcoded.txt
```

- [ ] **Step 2: For each subdirectory, process views in batches — add EN keys, add FR keys, replace in blade**

Important: existing keys like `staff_portal.*` are already in public.php. Before adding a new key, `grep` to confirm it doesn't already exist:
```bash
grep 'your_key_name' lang/en/public.php
```
Only add if missing.

- [ ] **Step 3: Parity check**
```bash
php scripts/i18n-audit.php
```

- [ ] **Step 4: Commit per logical group (e.g., per subdirectory)**
```bash
git commit -m "feat(i18n): bilingualize staff portal — appointments views"
# ... repeat per subdirectory
```

---

## Wave 5 — Portal Views: Admin Portal

**Files (~60+ views):**
All subdirectories under `portals/admin/`: appointments/, bridge/, cdss/, certifications/, clinical/, code_mappings/, connect/, control_center/, developer/, facilities/, financial/, kpi/, legal/, onboarding/, organizations/, patients/, reports/, roles/, security_ops/, staff/, subscription/, support/, users/ + index.blade.php, go_live_readiness.blade.php

### Task 10: Admin portal views — all files

- [ ] **Step 1: Audit**
```bash
grep -rn '[A-Z][a-z]' resources/views/portals/admin/ | grep -v '{{' | grep -v '@' | grep -v '//' | grep -v 'class=\|id=\|href=\|src=\|data-' > /tmp/admin-hardcoded.txt
wc -l /tmp/admin-hardcoded.txt
```

- [ ] **Step 2: Process in subdirectory batches. Existing `admin_portal.*`, `admin_governance.*` keys in public.php — check before adding.**

- [ ] **Step 3: Parity check**
```bash
php scripts/i18n-audit.php
```

- [ ] **Step 4: Commit per subdirectory batch**
```bash
git commit -m "feat(i18n): bilingualize admin portal — [section] views"
```

---

## Wave 6 — Portal Views: Insurance, Lab, Pharmacy, HealthOrg, Developer, Lite

### Task 11: Insurance portal (6 files)

**Files:** `portals/insurance/_sidebar_nav.blade.php`, `claims.blade.php`, `dashboard.blade.php`, `policies.blade.php`, `preauths.blade.php`, `providers.blade.php`

- [ ] **Step 1: Audit**
```bash
grep -rn '[A-Z][a-z]' resources/views/portals/insurance/ | grep -v '{{' | grep -v '@' | grep -v '//' | grep -v 'class=\|id=\|href=\|src=\|data-'
```
- [ ] **Step 2: Add missing keys to `lang/en/public.php` under `insurance_portal.*` and mirror in FR**
- [ ] **Step 3: Parity check + commit**
```bash
php scripts/i18n-audit.php
git add resources/views/portals/insurance/ lang/en/public.php lang/fr/public.php
git commit -m "feat(i18n): bilingualize insurance portal views"
```

---

### Task 12: Lab portal (5 files)

**Files:** `portals/lab/_sidebar.blade.php`, `dashboard.blade.php`, `orders.blade.php`, `results.blade.php`, `samples.blade.php`

- [ ] **Step 1–3: Same pattern as Task 11, using `lab_portal.*` key prefix**
```bash
php scripts/i18n-audit.php
git add resources/views/portals/lab/ lang/en/public.php lang/fr/public.php
git commit -m "feat(i18n): bilingualize lab portal views"
```

---

### Task 13: Pharmacy portal (5 files)

**Files:** `portals/pharmacy/_sidebar.blade.php`, `controlled.blade.php`, `dashboard.blade.php`, `inventory.blade.php`, `prescriptions.blade.php`

- [ ] **Step 1–3: Same pattern, using `pharmacy_portal.*` key prefix**
```bash
git commit -m "feat(i18n): bilingualize pharmacy portal views"
```

---

### Task 14: HealthOrg portal (6 files)

**Files:** `portals/healthorg/_sidebar.blade.php`, `dashboard.blade.php`, `outreach.blade.php`, `programs.blade.php`, `reports.blade.php`, `signals.blade.php`

- [ ] **Step 1–3: Same pattern, using `healthorg_portal.*` key prefix**
```bash
git commit -m "feat(i18n): bilingualize healthorg portal views"
```

---

### Task 15: Developer portal (10 files)

**Files:** `portals/developer/_sidebar.blade.php`, `analytics.blade.php`, `app_detail.blade.php`, `apps.blade.php`, `create_app.blade.php`, `create_production_request.blade.php`, `dashboard.blade.php`, `onboard.blade.php`, `production_requests.blade.php`, `webhook_deliveries.blade.php`

- [ ] **Step 1–3: Same pattern, using `developer_portal.*` key prefix**
```bash
git commit -m "feat(i18n): bilingualize developer portal views"
```

---

### Task 16: Lite portal (7 files)

**Files:** `portals/lite/billing.blade.php`, `checkin.blade.php`, `conflicts.blade.php`, `consultation.blade.php`, `dashboard.blade.php`, `devices.blade.php`, `lookup.blade.php`, `offline_events.blade.php`, `register_patient.blade.php`

- [ ] **Step 1–3: Same pattern, using `lite_portal.*` key prefix**
```bash
git commit -m "feat(i18n): bilingualize lite portal views"
```

---

## Wave 7 — API Controllers: Replace Hardcoded Response Messages

> **Rule:** Replace every `'message' => 'Hardcoded string'` and `'error' => 'Hardcoded string'` in API controllers with `'message' => __('api.key_name')`. The key must already exist in `lang/en/api.php` and `lang/fr/api.php` (created in Task 1). If a message doesn't map to an existing key, add the key to both files first.

### Task 17: Mobile API controllers (20 files)

**Files:** All `app/Http/Controllers/Api/Mobile/*.php`

- [ ] **Step 1: Audit all mobile controllers**
```bash
grep -rn "['\"]\(message\|error\|success\)['\"].*=>" app/Http/Controllers/Api/Mobile/ | grep -v '__(' | grep -v 'trans(' | head -60
```

- [ ] **Step 2: Replace in each file — example transformation:**

```php
// Before (MobileAuthController.php)
return response()->json(['message' => 'Patient not found.'], 404);
return response()->json(['message' => 'Invalid credentials.'], 401);
return response()->json(['message' => 'OTP sent to your registered phone number.'], 200);

// After
return response()->json(['message' => __('api.patient_not_found')], 404);
return response()->json(['message' => __('api.invalid_credentials')], 401);
return response()->json(['message' => __('api.otp_sent')], 200);
```

```php
// Before (MobileConsentController.php)
return response()->json(['message' => 'Consent request not found or already processed.'], 404);
return response()->json(['message' => 'Consent grant revoked. Existing tokens invalidated.'], 200);

// After
return response()->json(['message' => __('api.consent_not_found')], 404);
return response()->json(['message' => __('api.consent_revoked')], 200);
```

- [ ] **Step 3: If a message has no key in api.php yet, add it to both EN and FR first, then replace**

```php
// lang/en/api.php — add:
'offline_sync_complete' => 'Offline sync completed.',
// lang/fr/api.php — add:
'offline_sync_complete' => 'Synchronisation hors ligne terminée.',
```

- [ ] **Step 4: Parity check**
```bash
php scripts/i18n-audit.php
```

- [ ] **Step 5: Commit**
```bash
git add app/Http/Controllers/Api/Mobile/ lang/en/api.php lang/fr/api.php
git commit -m "feat(i18n): replace hardcoded strings in Mobile API controllers with __('api.*')"
```

---

### Task 18: V1 API controllers (60+ files)

**Files:** All `app/Http/Controllers/Api/V1/*.php`

- [ ] **Step 1: Audit**
```bash
grep -rn "['\"]\(message\|error\|success\)['\"].*=>" app/Http/Controllers/Api/V1/ | grep -v '__(' | grep -v 'trans(' | wc -l
```

- [ ] **Step 2: Process in logical batches (by domain: appointments, blood, lab, prescriptions, referrals, etc.)**

For dynamic messages like:
```php
// Before
return response()->json(['message' => "Units {$direction}ed."], 200);
// After — add parameterized key
return response()->json(['message' => __('api.units_direction', ['direction' => $direction])], 200);
// lang/en/api.php: 'units_direction' => 'Units :directioned.'
// lang/fr/api.php: 'units_direction' => 'Unités :directioned.'
// OR split into two keys:
// lang/en/api.php: 'units_added' / 'units_removed' and use conditional
```

- [ ] **Step 3: Parity check**
```bash
php scripts/i18n-audit.php
```

- [ ] **Step 4: Commit per domain batch**
```bash
git add app/Http/Controllers/Api/V1/AppointmentController.php ...
git commit -m "feat(i18n): bilingualize V1 API — appointments, blood, lab controllers"
```

---

## Wave 8 — Web Controllers: Flash Messages

### Task 19: Admin and MedicalId controllers (40+ files)

**Files:** All `app/Http/Controllers/MedicalId/*.php`

- [ ] **Step 1: Audit**
```bash
grep -rn "redirect().*with\|session()->flash\|->with('success'\|->with('error'" app/Http/Controllers/MedicalId/ | grep -v '__(' | head -60
```

- [ ] **Step 2: Replace with `flash.*` keys — example:**

```php
// Before (AdminAppointmentsController.php)
return redirect()->back()->with('success', 'Appointment cancelled successfully.');
return redirect()->back()->with('error', 'Failed to cancel appointment: ' . $e->getMessage());
return redirect()->back()->with('error', 'Only cancelled appointments can be deleted.');

// After
return redirect()->back()->with('success', __('flash.appointment_cancelled'));
return redirect()->back()->with('error', __('flash.appointment_cancel_failed'));
return redirect()->back()->with('error', __('flash.appointment_only_cancelled_deletable'));
```

- [ ] **Step 3: Process all 40+ MedicalId controllers using the same pattern**

- [ ] **Step 4: Parity check**
```bash
php scripts/i18n-audit.php
```

- [ ] **Step 5: Commit**
```bash
git add app/Http/Controllers/MedicalId/ lang/en/flash.php lang/fr/flash.php
git commit -m "feat(i18n): replace flash messages in MedicalId controllers with __('flash.*')"
```

---

## Wave 9 — Meta Tags and Page Titles

### Task 20: All public page meta tags

**Files:** All `resources/views/public/*.blade.php` + portal layouts

- [ ] **Step 1: Find all hardcoded `@section('title', ...)` and `@section('meta_description', ...)`**
```bash
grep -rn "@section('title'\|@section('meta_description'" resources/views/ | grep -v '__('
```

- [ ] **Step 2: Add page title and description keys for every page found**

Example for `lang/en/public.php` / `lang/fr/public.php`:
```php
// EN
'page_title_about'        => 'About OpesCare | Secure Digital Health in Cameroon',
'page_title_contact'      => 'Contact OpesCare | Partner with Us',
'page_title_faq'          => 'Frequently Asked Questions | OpesCare',
'page_title_privacy'      => 'Privacy Policy | OpesCare',
'page_title_terms'        => 'Terms of Service | OpesCare',
'page_title_how_it_works' => 'How OpesCare Works | Digital Health Platform',
'page_title_developers'   => 'Developer API | OpesCare Connect Suite',
'page_title_status'       => 'System Status | OpesCare',
'page_desc_about'         => 'Learn about OpesCare\'s mission to digitize healthcare in Cameroon and Central Africa.',
// ... (all pages)

// FR
'page_title_about'        => 'À Propos d\'OpesCare | Santé Numérique Sécurisée au Cameroun',
'page_title_contact'      => 'Contacter OpesCare | Devenez Partenaire',
// ... (all pages)
```

- [ ] **Step 3: Replace in every affected blade**
```blade
{{-- Before --}}
@section('title', 'About OpesCare | Secure Digital Health in Cameroon')
@section('meta_description', 'Learn about...')

{{-- After --}}
@section('title', __('public.page_title_about'))
@section('meta_description', __('public.page_desc_about'))
```

- [ ] **Step 4: Parity check + commit**
```bash
php scripts/i18n-audit.php
git add resources/views/public/ lang/en/public.php lang/fr/public.php
git commit -m "feat(i18n): bilingualize all page titles and meta descriptions"
```

---

## Wave 10 — JavaScript Strings

### Task 21: JS strings embedded in Blade `<script>` blocks

**Files:** Any blade file with `var labels = [...]` or similar JS string arrays

- [ ] **Step 1: Find all JS string literals in Blade files**
```bash
grep -rn "var [a-z]* = \['" resources/views/ | grep -v '__('
grep -rn 'labels\s*=\s*\[' resources/views/
```

- [ ] **Step 2: Replace with Blade `@json()` helper**

```blade
{{-- Before --}}
<script>
var labels = ['Health ID','Consent & Access','Emergency Access','Connected Care Network'];
</script>

{{-- After --}}
<script>
var labels = @json(__('landing.slide_labels'));
</script>
```

For toast/notification strings passed to JS:
```blade
{{-- In blade --}}
<script>
var i18n = @json([
    'loading'  => __('public.loading'),
    'error'    => __('public.error_generic'),
    'success'  => __('public.success'),
    'confirm'  => __('public.confirm'),
    'cancel'   => __('public.cancel'),
]);
</script>
```

- [ ] **Step 3: Add any new keys required to both EN and FR lang files**

- [ ] **Step 4: Parity check + commit**
```bash
php scripts/i18n-audit.php
git add resources/views/ lang/en/ lang/fr/
git commit -m "feat(i18n): bilingualize inline JavaScript strings via @json()"
```

---

## Wave 11 — Final QA Pass

### Task 22: Full parity verification

- [ ] **Step 1: Run the audit script — all files must show PERFECT PARITY**
```bash
php scripts/i18n-audit.php
```
Expected output: every file `OK`. Any failure must be fixed before proceeding.

- [ ] **Step 2: Visual spot-check — switch locale and load every portal type**

Test matrix (visit each URL with `?lang=fr` appended and verify no English text bleeds through):
```
/            → public landing
/home2       → landing2
/about       → about
/faq         → FAQ
/home2?lang=fr
/portals/patient/login → patient portal
/portals/staff/login   → staff portal
/portals/admin/login   → admin portal
/portals/insurance/login
/portals/lab/login
/portals/pharmacy/login
/portals/healthorg/login
/portals/developer/login
/portals/lite/login
```

- [ ] **Step 3: Test validation errors in French**

Submit an empty login form with FR locale active. Confirm error messages appear in French.

- [ ] **Step 4: Test API responses in French**

```bash
curl -s -H "Accept-Language: fr" -X POST http://localhost/api/v1/... | jq '.message'
```
Expected: French string.

- [ ] **Step 5: Test flash messages in French**

Log in as admin with FR locale, perform an action (cancel an appointment), confirm the flash message appears in French.

- [ ] **Step 6: Check for any remaining hardcoded strings**
```bash
grep -rn '"[A-Z][a-z][^"]*\."' resources/views/ | grep -v '{{' | grep -v '@' | grep -v '//' | grep -v 'class=\|id=\|href='
```
Any results must be fixed.

- [ ] **Step 7: Final commit**
```bash
git add .
git commit -m "feat(i18n): final QA pass — 100% bilingualization complete"
```

---

### Task 23: Tag the bilingualization milestone

- [ ] **Step 1: Create a git tag**
```bash
git tag -a i18n-1.0 -m "100% EN/FR bilingualization complete — all 22+ lang files, 464 blade views, 126 controllers"
```

- [ ] **Step 2: Push the tag**
```bash
git push origin i18n-1.0
```

---

## Summary

| Wave | Scope | Tasks | Files touched |
|---|---|---|---|
| Wave 1 | Infrastructure — new lang files, parity script | 1–4 | 6 new lang files + 1 script |
| Wave 2 | Public Blade views | 5–7 | 20 public blade files |
| Wave 3 | Patient portal | 8 | ~15 files |
| Wave 4 | Staff portal | 9 | ~40 files |
| Wave 5 | Admin portal | 10 | ~60 files |
| Wave 6 | Insurance, Lab, Pharmacy, HealthOrg, Developer, Lite | 11–16 | ~39 files |
| Wave 7 | API controllers (Mobile + V1) | 17–18 | ~80 controllers |
| Wave 8 | Web controller flash messages | 19 | ~40 controllers |
| Wave 9 | Meta tags / page titles | 20 | ~30 blade files |
| Wave 10 | JS strings | 21 | ~10 blade files |
| Wave 11 | Final QA | 22–23 | verification only |

**Total estimated new translation keys added: ~800–1,000**
**Total files modified: ~330**
**Every key: 1:1 EN ↔ FR parity, enforced by `scripts/i18n-audit.php`**

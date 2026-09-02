<?php

namespace Database\Seeders;

use App\Models\LegalDocument;
use App\Models\LegalDocumentVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * LegalDocumentSeeder
 *
 * Seeds all required and supplementary legal documents for OpesCare.
 * Idempotent — safe to run multiple times. Skips documents that already
 * have a current version (does NOT overwrite existing published content).
 *
 * Documents published here are minimal policy stubs — production deployments
 * MUST replace content with jurisdiction-specific legal text before go-live.
 *
 * Supports: LegalDocumentService::REQUIRED_DOCUMENTS + supplementary set.
 */
class LegalDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $documents = $this->documentCatalog();

        foreach ($documents as $slug => $config) {
            $document = LegalDocument::firstOrCreate(
                ['slug' => $slug],
                [
                    'title'         => $config['title'],
                    'document_type' => $config['type'],
                    'language'      => 'en',
                    'is_active'     => true,
                ]
            );

            // Skip if a current version already exists
            if ($document->versions()->where('is_current', true)->exists()) {
                continue;
            }

            // Mark any non-current versions as non-current just in case
            $document->versions()->update(['is_current' => false]);

            $contentHtml = $this->documentContent($config['title'], $config['type'], $config['stub_summary']);

            LegalDocumentVersion::create([
                'legal_document_id'     => $document->id,
                'version'               => '1.0',
                'content_html'          => $contentHtml,
                'content_hash'          => hash('sha256', $contentHtml),
                'is_current'            => true,
                'requires_reacceptance' => false,
                'change_summary'        => 'Initial version',
                'published_by'          => null,
                'published_at'          => now(),
                'effective_at'          => now(),
            ]);
        }
    }

    // ── Document Catalog ──────────────────────────────────────────────────────

    private function documentCatalog(): array
    {
        return [
            // ── Core Required Documents ────────────────────────────────────
            'terms-of-use' => [
                'title' => 'Terms of Use',
                'type'  => 'terms',
                'stub_summary' => 'These Terms of Use govern access to and use of the OpesCare platform by patients, healthcare providers, and partner organisations. By accessing OpesCare, you agree to comply with these terms.',
            ],
            'privacy-policy' => [
                'title' => 'Privacy Policy',
                'type'  => 'privacy',
                'stub_summary' => 'This Privacy Policy explains how OpesCare collects, processes, stores, and protects personal and health data in accordance with applicable data protection law.',
            ],
            'patient-consent-policy' => [
                'title' => 'Patient Consent Policy',
                'type'  => 'consent',
                'stub_summary' => 'This policy describes how OpesCare obtains, manages, and respects patient consent for data processing, sharing, and clinical care activities.',
            ],
            'data-processing-agreement' => [
                'title' => 'Data Processing Agreement',
                'type'  => 'dpa',
                'stub_summary' => 'This Data Processing Agreement (DPA) governs the processing of personal data by OpesCare on behalf of facilities and partner organisations acting as data controllers.',
            ],
            'facility-agreement' => [
                'title' => 'Facility Agreement',
                'type'  => 'facility_agreement',
                'stub_summary' => 'This Facility Agreement governs the relationship between OpesCare and healthcare facilities onboarded to the platform, including service obligations, data responsibilities, and go-live requirements.',
            ],
            'api-developer-terms' => [
                'title' => 'API / Developer Terms',
                'type'  => 'api_terms',
                'stub_summary' => 'These API Developer Terms govern access to the OpesCare API, including permitted uses, rate limits, production approval requirements, and security obligations.',
            ],

            // ── Supplementary Documents ───────────────────────────────────
            'cookie-policy' => [
                'title' => 'Cookie Policy',
                'type'  => 'privacy',
                'stub_summary' => 'This Cookie Policy explains what cookies OpesCare uses, why we use them, and how users can manage cookie preferences on our web portals and patient-facing applications.',
            ],
            'pharmacy-partner-agreement' => [
                'title' => 'Pharmacy Partner Agreement',
                'type'  => 'facility_agreement',
                'stub_summary' => 'This agreement governs the terms under which pharmacy partners integrate with OpesCare for prescription dispensing, stock management, and patient medication records.',
            ],
            'laboratory-partner-agreement' => [
                'title' => 'Laboratory Partner Agreement',
                'type'  => 'facility_agreement',
                'stub_summary' => 'This agreement governs the terms under which laboratory partners integrate with OpesCare for lab order management, result reporting, and LOINC-mapped result delivery.',
            ],
            'insurance-partner-agreement' => [
                'title' => 'Insurance Partner Agreement',
                'type'  => 'facility_agreement',
                'stub_summary' => 'This agreement governs the terms under which insurance partners and review organisations access OpesCare for claims processing, pre-authorisation, and minimum necessary clinical data access.',
            ],
            'public-health-data-sharing-policy' => [
                'title' => 'Public Health Data Sharing Policy',
                'type'  => 'privacy',
                'stub_summary' => 'This policy defines the conditions under which de-identified or aggregate health data from OpesCare may be shared with public health authorities, including anonymisation standards and reporting protocols.',
            ],
            'acceptable-use-policy' => [
                'title' => 'Acceptable Use Policy',
                'type'  => 'terms',
                'stub_summary' => 'This Acceptable Use Policy defines permitted and prohibited uses of the OpesCare platform for all user roles, including clinical staff, patients, developers, and administrative users.',
            ],
            'data-retention-policy' => [
                'title' => 'Data Retention Policy',
                'type'  => 'privacy',
                'stub_summary' => 'This policy defines the retention periods for all categories of health and operational data held by OpesCare, including deletion procedures and audit requirements.',
            ],
            'data-deletion-policy' => [
                'title' => 'Data Deletion Policy',
                'type'  => 'privacy',
                'stub_summary' => 'This policy defines how patients and organisations may request deletion of their data, the criteria that apply, and the process for fulfilling deletion requests under applicable law.',
            ],
            'patient-rights-policy' => [
                'title' => 'Patient Rights Policy',
                'type'  => 'consent',
                'stub_summary' => 'This policy explains the rights patients have regarding their health data held by OpesCare, including the right to access, correct, restrict, port, and request deletion of their records.',
            ],
            'incident-breach-notification-policy' => [
                'title' => 'Incident and Breach Notification Policy',
                'type'  => 'privacy',
                'stub_summary' => 'This policy defines how OpesCare detects, responds to, and notifies affected parties of security incidents and data breaches, including regulatory notification timelines.',
            ],
            'clinical-disclaimer' => [
                'title' => 'Clinical Disclaimer',
                'type'  => 'consent',
                'stub_summary' => 'OpesCare is a health information management platform. It does not provide medical advice, diagnosis, or treatment. All clinical decisions remain the sole responsibility of licensed healthcare professionals.',
            ],
            'cdss-disclaimer' => [
                'title' => 'Clinical Decision Support Disclaimer',
                'type'  => 'consent',
                'stub_summary' => 'Clinical Decision Support System (CDSS) alerts and recommendations provided by OpesCare are decision-support tools only. They do not replace professional clinical judgment and must never be interpreted as diagnostic or prescriptive authority.',
            ],
            'telemedicine-disclaimer' => [
                'title' => 'Telemedicine Disclaimer',
                'type'  => 'consent',
                'stub_summary' => 'This disclaimer governs telemedicine consultations conducted through OpesCare, including limitations of remote care, patient consent requirements, and data security during virtual consultations.',
            ],
            'care-access-map-disclaimer' => [
                'title' => 'Care Access Map Disclaimer',
                'type'  => 'terms',
                'stub_summary' => 'The OpesCare Care Access Map is provided for informational purposes only. Facility, pharmacy, and laboratory listings are not endorsements. Availability and services should be verified directly with the listed facility.',
            ],
            'support-access-policy' => [
                'title' => 'Support Access Policy',
                'type'  => 'privacy',
                'stub_summary' => 'This policy governs when and how OpesCare support staff may access facility or patient data for troubleshooting purposes, including audit requirements and patient notification obligations.',
            ],
            'research-data-access-policy' => [
                'title' => 'Research Data Access Policy',
                'type'  => 'privacy',
                'stub_summary' => 'This policy governs requests to access de-identified OpesCare data for research purposes, including ethics approval requirements, data sharing agreements, and prohibited re-identification activities.',
            ],
        ];
    }

    // ── Stub Content Generator ────────────────────────────────────────────────

    private function documentContent(string $title, string $type, string $summary): string
    {
        $effectiveDate = now()->format('d F Y');
        $sections = $this->sectionsForType($type);

        return <<<HTML
<div class="legal-document">
    <h1>{$title}</h1>
    <p class="legal-meta"><strong>Effective Date:</strong> {$effectiveDate} &nbsp;|&nbsp; <strong>Version:</strong> 1.0</p>

    <h2>Overview</h2>
    <p>{$summary}</p>

    {$sections}

    <h2>Contact</h2>
    <p>Questions, privacy requests, incident notices, and consent withdrawals may be sent to the OpesCare Privacy Officer or the facility privacy contact provided during onboarding. Requests are logged, triaged, and answered within the timeframe required by applicable law.</p>

    <h2>Updates</h2>
    <p>OpesCare may update this document to reflect product, legal, regulatory, or security changes. Material changes require publication of a new version and may require affected users or organisations to re-accept the updated terms before continuing to use protected features.</p>
</div>
HTML;
    }

    private function sectionsForType(string $type): string
    {
        return match ($type) {
            'privacy' => <<<HTML
    <h2>Data Protection Commitments</h2>
    <p>OpesCare processes personal data and protected health information only for care delivery, patient engagement, facility operations, payment, public-health reporting, security, and legally authorised interoperability. Processing follows applicable privacy laws, including GDPR-style transparency, purpose limitation, data minimisation, accuracy, storage limitation, integrity, confidentiality, and accountability principles, and HIPAA-style safeguards where protected health information is handled for covered healthcare workflows.</p>

    <h2>Information We Process</h2>
    <p>Data may include identity details, contact details, Health IDs, clinical records, prescriptions, laboratory results, insurance information, consent records, audit logs, device identifiers, support requests, and security telemetry. Sensitive data is encrypted or access-controlled according to its risk classification.</p>

    <h2>Sharing and Subprocessors</h2>
    <p>OpesCare shares data only with authorised facilities, clinicians, laboratories, pharmacies, insurers, public-health authorities, technology processors, and support personnel with a lawful basis, role-based access, contractual confidentiality, and audit logging. Data is not sold.</p>

    <h2>Individual Rights</h2>
    <p>Patients may request access, correction, portability, restriction, deletion where legally available, consent withdrawal, and an accounting of relevant disclosures. Requests may be limited where retention, clinical safety, fraud prevention, public-health, or legal obligations require continued processing.</p>

    <h2>Security and Retention</h2>
    <p>OpesCare applies encryption, least-privilege access, MFA for privileged roles, logging, backup controls, incident response, and retention schedules aligned to clinical, legal, and operational obligations.</p>
HTML,
            'consent' => <<<HTML
    <h2>Consent Scope</h2>
    <p>By accepting this document, the patient or authorised representative permits OpesCare and participating care organisations to collect, use, display, exchange, and store relevant health information for treatment, care coordination, referrals, prescriptions, laboratory and imaging workflows, insurance administration, patient support, and emergency access where permitted by law.</p>

    <h2>Clinical Data Sharing</h2>
    <p>Clinical data may be shared with verified healthcare providers and partner organisations only when needed for a documented purpose, backed by patient consent, another lawful basis, or an emergency exception. Each access event is logged with actor, organisation, purpose, time, and relevant consent or emergency-access reference.</p>

    <h2>Withdrawal and Limits</h2>
    <p>Consent may be withdrawn for future processing through OpesCare settings, facility support, or the Privacy Officer. Withdrawal does not invalidate prior lawful processing and may not prevent processing required for clinical safety, legal retention, billing records, fraud prevention, or public-health duties.</p>

    <h2>Patient Understanding</h2>
    <p>The patient acknowledges that withholding or withdrawing consent may limit digital services, provider visibility into records, appointment coordination, or partner fulfilment, while core clinical care remains subject to facility policy and applicable law.</p>
HTML,
            'dpa' => <<<HTML
    <h2>Processing Roles</h2>
    <p>For facility-controlled records, the facility or partner organisation acts as controller or equivalent responsible party, and OpesCare acts as processor or service provider unless a separate agreement states otherwise. OpesCare processes data only under documented instructions and applicable law.</p>

    <h2>Processor Obligations</h2>
    <p>OpesCare maintains confidentiality, technical and organisational safeguards, access controls, audit logs, subprocessors under written terms, breach notification procedures, data subject request support, and deletion or return processes at termination subject to lawful retention.</p>

    <h2>International Transfers</h2>
    <p>Cross-border processing requires an approved transfer mechanism, adequate contractual safeguards, and review of hosting, support, and backup locations before production enablement.</p>
HTML,
            'facility_agreement' => <<<HTML
    <h2>Facility Responsibilities</h2>
    <p>The facility is responsible for lawful patient onboarding, identity verification, staff credentialing, role assignment, consent capture, clinical accuracy, local regulatory compliance, and prompt removal of access when staff roles change.</p>

    <h2>OpesCare Responsibilities</h2>
    <p>OpesCare provides platform availability, access controls, audit logging, secure interoperability services, support workflows, and documented operational safeguards according to the subscribed service level and deployment terms.</p>

    <h2>Operational Controls</h2>
    <p>Facilities must maintain approved devices, secure networks, backup procedures, incident escalation contacts, and staff training before production use. Misuse, unsafe configuration, or regulatory non-compliance may result in suspension of access.</p>
HTML,
            'api_terms' => <<<HTML
    <h2>API Access Conditions</h2>
    <p>API access is limited to approved applications, registered clients, authorised scopes, and documented use cases. Developers must protect credentials, use TLS, rotate secrets, validate webhook signatures, respect rate limits, and avoid collecting or transmitting more health data than required.</p>

    <h2>Prohibited Uses</h2>
    <p>Developers may not scrape, re-identify de-identified data, bypass consent, share credentials, disable audit trails, introduce malware, perform unauthorised load testing, or use API data for advertising, profiling, or unrelated commercial purposes.</p>

    <h2>Monitoring and Suspension</h2>
    <p>OpesCare may monitor API use, throttle abusive clients, require remediation, rotate credentials, or suspend access to protect patients, facilities, platform security, or regulatory compliance.</p>
HTML,
            default => <<<HTML
    <h2>Scope</h2>
    <p>This document applies to all users, facilities, partner organisations, developers, and support personnel who access or use the OpesCare platform.</p>

    <h2>Acceptable Use</h2>
    <p>Users must access only information they are authorised to view, use the platform for legitimate care, operational, payment, support, or compliance purposes, keep credentials confidential, and promptly report suspected security incidents or inaccurate records.</p>

    <h2>Clinical and Legal Responsibility</h2>
    <p>OpesCare supports health information workflows but does not replace licensed clinical judgment, facility governance, professional obligations, or local legal requirements. Organisations remain responsible for their clinical decisions and regulatory compliance.</p>
HTML,
        };
    }
}

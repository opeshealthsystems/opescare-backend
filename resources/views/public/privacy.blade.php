@extends('layouts.public')

@section('title', __('public.privacy_page.page_title'))
@section('meta_description', __('public.privacy_page.meta_description'))

@section('content')

    <header class="content-header">
        <div class="container">
            <div class="badge" style="background:rgba(15,76,129,.12);color:#0F4C81;margin-bottom:1rem;">{{ __('public.privacy_page.badge') }}</div>
            <h1>{{ __('public.privacy_page.hero_title') }}</h1>
            <p class="text-muted" style="max-width:760px;margin:0 auto;">
                {{ __('public.privacy_page.hero_subtitle') }}
            </p>
            <div style="margin-top:1.5rem;display:flex;justify-content:center;gap:1.5rem;flex-wrap:wrap;font-size:0.875rem;color:var(--color-text-muted);">
                <span><strong>{{ __('public.privacy_page.effective_date') }}</strong> 1 June 2026</span>
                <span><strong>{{ __('public.privacy_page.last_updated') }}</strong> 7 June 2026</span>
                <span><strong>{{ __('public.privacy_page.version') }}</strong> 2.0</span>
            </div>
        </div>
    </header>

    {{-- Quick nav --}}
    <section style="background:#F8FAFC;border-bottom:1px solid #E2E8F0;padding:1.25rem 0;">
        <div class="container">
            <p style="font-size:0.8125rem;color:var(--color-text-muted);margin:0 0 0.5rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">{{ __('public.privacy_page.jump_to') }}</p>
            <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
                @foreach([
                    ['#controller','Data Controller'],
                    ['#legal-basis','Legal Basis'],
                    ['#data-collected','Data We Collect'],
                    ['#purposes','Purposes'],
                    ['#sharing','Data Sharing'],
                    ['#retention','Retention'],
                    ['#security','Security'],
                    ['#rights','Your Rights'],
                    ['#children','Children'],
                    ['#emergency','Emergency Access'],
                    ['#supervisory','Supervisory Authority'],
                    ['#contact','Contact DPO'],
                ] as [$href,$label])
                <a href="{{ $href }}" style="font-size:0.8125rem;padding:4px 12px;background:#fff;border:1px solid #E2E8F0;border-radius:999px;color:var(--color-primary);text-decoration:none;">{{ $label }}</a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="content-body">
        <div class="container rich-text" style="max-width:860px;">

            {{-- 0. Regulatory alignment notice --}}
            <div style="background:linear-gradient(135deg,#EFF6FF,#F0FDF4);border:1px solid #BFDBFE;border-radius:1rem;padding:1.75rem;margin-bottom:3rem;">
                <div style="display:flex;gap:1rem;align-items:flex-start;">
                    <i data-lucide="shield-check" style="width:1.5rem;height:1.5rem;color:#0F4C81;flex-shrink:0;margin-top:0.1rem;"></i>
                    <div>
                        <p style="margin:0 0 0.5rem;font-weight:700;color:#0F4C81;">Regulatory Alignment</p>
                        <p style="margin:0;font-size:0.9rem;color:#374151;line-height:1.65;">
                            This policy is designed to comply with the <strong>Republic of Cameroon Law No. 2010/012 of 21 December 2010</strong> on cybersecurity and cybercriminality, <strong>Law No. 2010/021</strong> on electronic commerce, <strong>Law No. 96/03 of 4 January 1996</strong> (Framework Law on Health), and the <strong>Cameroon Ministry of Public Health (MINSANTE)</strong> National Digital Health Strategy 2026–2030. It further reflects principles of the <strong>WHO Global Strategy on Digital Health 2020–2025</strong>, the <strong>African Union Convention on Cyber Security and Personal Data Protection (Malabo Convention, 2014)</strong>, and the <strong>General Data Protection Regulation (GDPR, EU 2016/679)</strong> as an international best-practice benchmark.
                        </p>
                    </div>
                </div>
            </div>

            {{-- 1. Data Controller --}}
            <h2 id="controller">1. Data Controller</h2>
            <p>The data controller responsible for your personal and health data is:</p>
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:0.875rem;padding:1.5rem;margin:1rem 0;">
                <p style="margin:0;"><strong>Opes Health Systems Sarl</strong><br>
                Republic of Cameroon<br>
                Registration: RCCM / RC No. [Yaoundé Commercial Registry]<br>
                VAT: [Numéro Identifiant Unique — NIU]<br>
                Email: <a href="mailto:privacy@opeshealthsystems.com">privacy@opeshealthsystems.com</a><br>
                Platform: OpesCare (opescare.com)</p>
            </div>
            <p>Opes Health Systems Sarl operates as a <strong>health data intermediary and interoperability platform</strong>. We do not provide clinical advice or act as a healthcare provider. We facilitate the secure, consent-governed exchange of patient health records between registered healthcare facilities and patients.</p>

            {{-- 2. Legal Basis --}}
            <h2 id="legal-basis">2. Legal Basis for Processing</h2>
            <p>We process your personal and health data on the following legal grounds, consistent with Cameroon Law No. 2010/012 and internationally recognised data protection principles:</p>
            <ul>
                <li><strong>Explicit Consent (Article 46, Law 2010/012):</strong> For all non-emergency access to your clinical records, processing is based on your freely given, specific, informed and unambiguous consent, recorded electronically within the OpesCare platform and timestamped in an immutable audit log.</li>
                <li><strong>Contractual Necessity:</strong> Processing necessary to create and maintain your OpesCare Health ID, authenticate your identity, and fulfil obligations under our Terms of Service.</li>
                <li><strong>Vital Interest / Public Health Emergency:</strong> In a verified, life-threatening emergency where you are unable to consent, a limited emergency health profile may be accessed by an approved healthcare provider. This constitutes a vital-interest basis as recognised under international health data protection frameworks. Every such access is strictly audited.</li>
                <li><strong>Legal Obligation:</strong> Where we are required to process or disclose data to comply with an order of a competent Cameroonian court, the National Agency for Information and Communication Technologies (ANTIC), or a lawful directive from MINSANTE.</li>
                <li><strong>Legitimate Interests (Platform Security &amp; Audit):</strong> Processing necessary for fraud prevention, platform security monitoring, and the maintenance of immutable audit logs, provided this does not override your fundamental rights.</li>
            </ul>

            {{-- 3. Data Collected --}}
            <h2 id="data-collected">3. Categories of Personal Data We Process</h2>

            <h3>3.1 Identity and Demographic Data</h3>
            <ul>
                <li>Full legal name, date of birth, sex/gender, nationality</li>
                <li>National Identification Number (CNI/NIC), CNAMGS social security identifier, passport number where applicable</li>
                <li>OpesCare Health ID (unique platform identifier)</li>
                <li>Photograph or biometric identifier (where provided for identity verification)</li>
                <li>Contact information: phone number, email address, postal address</li>
                <li>Emergency contact name, phone, and relationship</li>
            </ul>

            <h3>3.2 Clinical and Health Data (Special Category)</h3>
            <p style="background:#FEF3C7;border-left:4px solid #F59E0B;padding:0.75rem 1rem;border-radius:0 0.5rem 0.5rem 0;font-size:0.9rem;"><strong>Note:</strong> Health data is a special category of personal data under Cameroon law and international standards. We apply the highest level of protection to all clinical information.</p>
            <ul>
                <li>Medical history, diagnoses, clinical conditions (ICD-10/ICD-11 coded)</li>
                <li>Prescription records, medication lists, dispensing history</li>
                <li>Laboratory results, diagnostic reports, DICOM imaging metadata</li>
                <li>Immunisation and vaccination history</li>
                <li>Allergy records and adverse reaction history</li>
                <li>Blood group and transfusion records</li>
                <li>Clinical encounter and visit records (facility, provider, date, summary)</li>
                <li>Referral records and care plan information</li>
                <li>Insurance policy numbers and coverage data</li>
                <li>Official health documents (discharge summaries, certificates, advance directives)</li>
            </ul>

            <h3>3.3 Platform Usage and Access Data</h3>
            <ul>
                <li>Consent grants, denials, revocations, and modification history</li>
                <li>Access event logs (who accessed, when, from which facility, stated purpose, scope of data retrieved)</li>
                <li>QR code generation and verification timestamps</li>
                <li>Authentication events (login, logout, password changes, 2FA)</li>
                <li>IP address, device type, browser agent (retained for security audit purposes only)</li>
            </ul>

            <h3>3.4 Data We Do Not Collect</h3>
            <p>We do not collect financial payment card data, biometric raw data for identification (beyond what is necessary for identity verification), social media profiles, or location tracking data beyond what you explicitly provide as part of your address.</p>

            {{-- 4. Purposes --}}
            <h2 id="purposes">4. Purposes of Processing</h2>
            <table style="width:100%;border-collapse:collapse;font-size:0.9rem;margin:1rem 0;">
                <thead>
                    <tr style="background:#F1F5F9;">
                        <th style="padding:0.75rem 1rem;text-align:left;border-bottom:2px solid #E2E8F0;">Purpose</th>
                        <th style="padding:0.75rem 1rem;text-align:left;border-bottom:2px solid #E2E8F0;">Legal Basis</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach([
                        ['Creating and maintaining your OpesCare Health ID', 'Contract / Legal obligation'],
                        ['Identity verification and patient matching across facilities', 'Contract / Consent'],
                        ['Enabling consent-based clinical record sharing between authorised providers', 'Explicit consent'],
                        ['Emergency access to critical health information', 'Vital interest / Emergency health basis'],
                        ['Medication and blood availability verification', 'Consent / Public health'],
                        ['Generating immutable audit logs of all access events', 'Legal obligation / Legitimate interest'],
                        ['Sending notifications about consent requests and access events', 'Contract / Consent'],
                        ['Platform security monitoring and fraud prevention', 'Legitimate interest'],
                        ['Anonymised public health reporting (aggregated data only, no individual identifiers)', 'Public interest / Legal obligation (MINSANTE reporting)'],
                        ['Compliance with court orders or regulatory directives from ANTIC or MINSANTE', 'Legal obligation'],
                        ['Improving platform reliability and user experience (anonymised analytics only)', 'Legitimate interest'],
                    ] as [$purpose,$basis])
                    <tr>
                        <td style="padding:0.75rem 1rem;border-bottom:1px solid #E2E8F0;">{{ $purpose }}</td>
                        <td style="padding:0.75rem 1rem;border-bottom:1px solid #E2E8F0;color:var(--color-primary);font-weight:600;white-space:nowrap;">{{ $basis }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- 5. Data Sharing --}}
            <h2 id="sharing">5. Data Sharing and Disclosure</h2>

            <h3>5.1 Authorised Healthcare Providers</h3>
            <p>We share your health records only with healthcare facilities and providers you have explicitly authorised through the OpesCare consent system. Each consent grant specifies the facility, the purpose, the scope of data accessible, and the duration. Providers receive only the minimum data necessary for the stated clinical purpose (<em>data minimisation principle</em>).</p>

            <h3>5.2 Emergency Access</h3>
            <p>In a documented life-threatening emergency, an approved healthcare provider may access your emergency health profile (identity, blood group, critical allergies, active chronic conditions) without prior consent. This access is automatically logged, an alert is sent to compliance officers, and you or your emergency contact are notified at the earliest opportunity. See Section 10 for the full emergency access protocol.</p>

            <h3>5.3 Sub-Processors</h3>
            <p>We engage third-party sub-processors for hosting, backup, and security monitoring. All sub-processors are bound by data processing agreements (DPAs) requiring equivalent data protection standards. We do not use sub-processors established in jurisdictions that do not provide adequate data protection without implementing appropriate safeguards (Standard Contractual Clauses or equivalent). Our sub-processors are reviewed annually.</p>

            <h3>5.4 Public Health and Government Authorities</h3>
            <p>We may share <strong>aggregated, de-identified</strong> data with MINSANTE, the World Health Organisation (WHO), and other public health bodies for disease surveillance, epidemiological reporting, health system planning, and to support the objectives of Cameroon's National Digital Health Strategy 2026–2030. No individual patient record is shared for this purpose.</p>

            <h3>5.5 Legal Compliance</h3>
            <p>We will disclose personal data where required by a binding and lawful order of a Cameroonian court, ANTIC, MINSANTE, or other competent regulatory authority. We will, where permitted by law, notify you of any such request prior to disclosure.</p>

            <h3>5.6 What We Will Never Do</h3>
            <ul>
                <li>Sell, rent, or trade your personal or health data to any third party for commercial purposes.</li>
                <li>Use your identifiable health data for advertising, profiling, or marketing purposes.</li>
                <li>Share your data with insurers, employers, or government agencies outside lawful clinical and regulatory channels without your explicit consent.</li>
                <li>Transfer your data outside the Central African Economic and Monetary Community (CEMAC) region or the African Union without appropriate safeguards in place.</li>
            </ul>

            {{-- 6. Retention --}}
            <h2 id="retention">6. Data Retention</h2>
            <p>We retain personal and health data in accordance with Cameroon health legislation, WHO guidance on health record retention, and the requirements of MINSANTE:</p>
            <ul>
                <li><strong>Clinical health records:</strong> Minimum 10 years from the date of last clinical encounter, consistent with Cameroon health legislation and WHO recommendations. Records relating to minors are retained until the patient reaches 28 years of age, or 10 years from last encounter, whichever is later.</li>
                <li><strong>Audit and access logs:</strong> Minimum 10 years. Audit logs are immutable and cannot be deleted even at the patient's request, as they form part of the legally required accountability framework.</li>
                <li><strong>Consent records:</strong> For the full duration of the data subject's registration and at least 5 years thereafter, or as required by applicable law.</li>
                <li><strong>Identity data:</strong> For the duration of platform registration and up to 7 years after account closure to satisfy legal obligations.</li>
                <li><strong>Platform usage / authentication logs:</strong> 2 years, for security purposes.</li>
            </ul>
            <p>Upon expiry of these periods, or upon lawful erasure request, data is securely destroyed using cryptographic erasure and/or physical deletion from all storage systems, including backups.</p>

            {{-- 7. Security --}}
            <h2 id="security">7. Security Measures</h2>
            <p>We implement institutional-grade technical and organisational security measures including, but not limited to:</p>
            <ul>
                <li>AES-256 encryption for all health data at rest</li>
                <li>TLS 1.3 for all data in transit; older protocol versions are rejected</li>
                <li>Zero-trust network architecture with role-based access control (RBAC)</li>
                <li>Multi-factor authentication (MFA) for all staff and healthcare provider accounts</li>
                <li>Regular independent penetration testing and vulnerability assessments</li>
                <li>Immutable, append-only audit log infrastructure</li>
                <li>24/7 automated threat detection and incident response</li>
                <li>Secure data centre infrastructure with physical access controls, CCTV, and fire suppression</li>
                <li>Annual security awareness training for all staff with access to patient data</li>
                <li>Data breach response plan with a maximum 72-hour notification obligation to ANTIC and affected data subjects</li>
            </ul>
            <p>Full details are available on our <a href="{{ route('public.security') }}" class="text-primary font-bold">Security Architecture</a> page.</p>

            {{-- 8. Rights --}}
            <h2 id="rights">8. Your Rights</h2>
            <p>As a data subject under Cameroon Law No. 2010/012 and in alignment with internationally recognised data protection standards, you have the following rights:</p>

            <div style="display:grid;gap:1rem;margin:1.5rem 0;">
                @foreach([
                    ['Right of Access (Droit d\'accès)','You have the right to obtain confirmation of whether we process personal data about you, and to receive a copy of that data in an intelligible format within 30 days of a valid request.'],
                    ['Right to Rectification (Droit de rectification)','You have the right to request correction of inaccurate or incomplete identity data. Clinical data recorded by healthcare providers must be corrected by the originating facility.'],
                    ['Right to Erasure (Droit à l\'effacement)','Where processing is based solely on consent and no legal retention obligation applies, you may request deletion of your data. Audit logs and legally required retention records are excluded from this right.'],
                    ['Right to Restriction of Processing','You may request that we restrict processing of your data in certain circumstances, for example while a rectification request is pending.'],
                    ['Right to Data Portability','You have the right to receive your clinical data in a structured, machine-readable format (HL7 FHIR R4 / JSON) and to transmit it to another health information system or provider.'],
                    ['Right to Object','You have the right to object to processing based on legitimate interests. Where your objection is upheld, we will cease that processing unless we can demonstrate compelling legitimate grounds.'],
                    ['Right to Withdraw Consent','Where processing is based on consent, you may withdraw consent at any time via your patient portal or by contacting our Data Protection Officer. Withdrawal does not affect the lawfulness of prior processing.'],
                    ['Right not to be subject to automated decision-making','We do not make legally significant automated decisions about your health, clinical care, or insurance eligibility solely on the basis of automated processing without human review.'],
                ] as [$right,$desc])
                <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:0.875rem;padding:1.25rem 1.5rem;">
                    <p style="margin:0 0 0.35rem;font-weight:700;color:#0F2744;">{{ $right }}</p>
                    <p style="margin:0;font-size:0.9rem;color:#4B5563;">{{ $desc }}</p>
                </div>
                @endforeach
            </div>

            <p>To exercise any of the above rights, submit a written request to <a href="mailto:privacy@opeshealthsystems.com">privacy@opeshealthsystems.com</a>. We will respond within <strong>30 calendar days</strong>. We may request proof of identity before processing your request. The exercise of rights is free of charge; however, for manifestly unfounded or excessive requests, we reserve the right to charge a reasonable administrative fee.</p>

            {{-- 9. Special Protections --}}
            <h2>9. Special Category Health Data</h2>
            <p>Health data is recognised as a special category of personal data requiring heightened protection under Cameroon law and international frameworks. In addition to our standard safeguards:</p>
            <ul>
                <li>Clinical records are accessible only to verified healthcare providers with a confirmed and documented clinical purpose.</li>
                <li>Sensitive diagnostic categories (including HIV status, mental health records, reproductive health, and substance dependency) are subject to additional access controls and are not included in emergency profiles unless clinically critical.</li>
                <li>We implement the principle of <em>least privilege</em>: each provider role receives access only to the data categories relevant to their clinical function.</li>
                <li>All special category data processing is documented in our Article 30-equivalent Record of Processing Activities (ROPA), available to ANTIC upon request.</li>
            </ul>

            {{-- 10. Children --}}
            <h2 id="children">10. Children and Minors</h2>
            <p>OpesCare may process health records for minors (persons under 18 years of age in the Republic of Cameroon). The following safeguards apply:</p>
            <ul>
                <li>Consent for the registration and health data processing of a minor must be provided by a parent or lawfully appointed guardian.</li>
                <li>Access to a minor's health record requires explicit authorisation from the parent/guardian, except in verified medical emergencies.</li>
                <li>Guardians may designate a transition date for patient majority, after which the patient gains full independent control of their Health ID and consent management.</li>
                <li>Records for minors are retained until the patient reaches 28 years of age or 10 years from the date of last clinical encounter, whichever is later, consistent with Cameroon health documentation standards.</li>
                <li>We do not knowingly register minors on the patient-facing portal without verified parental or guardian consent.</li>
            </ul>

            {{-- 11. Emergency Access --}}
            <h2 id="emergency">11. Emergency Access Protocol</h2>
            <p>In life-threatening clinical emergencies, where a patient is unconscious or unable to provide informed consent, an approved healthcare provider registered on the OpesCare platform may access a strictly limited <strong>Emergency Health Profile</strong>. This profile contains:</p>
            <ul>
                <li>Full legal name and date of birth</li>
                <li>Blood group and Rh factor</li>
                <li>Active critical allergies and contraindications</li>
                <li>Current chronic conditions material to emergency treatment</li>
                <li>Active critical medications</li>
                <li>Emergency contact name and phone number</li>
            </ul>
            <p>Every emergency access event:</p>
            <ul>
                <li>Requires the accessing provider to document a clinical reason before the profile is displayed</li>
                <li>Generates an immediate high-priority audit alert visible to facility compliance officers and platform administrators</li>
                <li>Is logged with the provider's authenticated identity, facility, timestamp, and device</li>
                <li>Triggers an automatic notification to the patient's registered contact at the earliest safe opportunity</li>
                <li>Is subject to post-event review by the relevant facility's clinical governance team</li>
            </ul>
            <p>Emergency access does <strong>not</strong> grant access to the patient's full clinical history, mental health records, reproductive health data, or consent logs.</p>

            {{-- 12. Supervisory Authority --}}
            <h2 id="supervisory">12. Supervisory Authority</h2>
            <p>The competent supervisory authority for personal data protection in the Republic of Cameroon is:</p>
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:0.875rem;padding:1.5rem;margin:1rem 0;">
                <p style="margin:0;"><strong>Agence Nationale des Technologies de l'Information et de la Communication (ANTIC)</strong><br>
                B.P. 6170, Yaoundé, Cameroon<br>
                Web: <a href="https://www.antic.cm" target="_blank" rel="noopener noreferrer">www.antic.cm</a><br>
                Tel: +237 222 22 35 58</p>
            </div>
            <p>If you believe your data protection rights have been violated, you have the right to lodge a complaint with ANTIC at any time. We encourage you to contact us first at <a href="mailto:privacy@opeshealthsystems.com">privacy@opeshealthsystems.com</a> so that we may address your concern directly.</p>

            {{-- 13. International Transfers --}}
            <h2>13. Cross-Border Data Transfers</h2>
            <p>OpesCare primarily processes and stores patient data within secure infrastructure within the Central Africa region. Where any component of our infrastructure or sub-processor is located outside Cameroon, we ensure that equivalent data protection safeguards are in place, including:</p>
            <ul>
                <li>Data processing agreements (DPAs) requiring compliance with Cameroon Law 2010/012 and the Malabo Convention</li>
                <li>Data localisation for clinical records in CEMAC-region-compliant storage where technically and commercially feasible</li>
                <li>Annual review of all sub-processor locations and data transfer mechanisms</li>
            </ul>

            {{-- 14. Cookies and Tracking --}}
            <h2>14. Cookies and Tracking Technologies</h2>
            <p>The OpesCare public website and patient portal use the following types of cookies:</p>
            <ul>
                <li><strong>Essential / Functional cookies:</strong> Required for authentication session management, CSRF protection, and platform security. Cannot be disabled without impairing platform functionality.</li>
                <li><strong>Analytics cookies:</strong> Anonymous, aggregated page-view analytics used to improve the platform. No personal or health data is transmitted to analytics providers.</li>
                <li><strong>No advertising or tracking cookies</strong> are used on any OpesCare platform.</li>
            </ul>
            <p>You may manage non-essential cookies via your browser settings at any time.</p>

            {{-- 15. Changes --}}
            <h2>15. Changes to This Policy</h2>
            <p>We may update this Privacy Policy from time to time to reflect changes in law, our processing activities, or platform features. We will notify registered users of material changes via email and in-platform notification at least 30 days before changes take effect. The current version will always be available at this URL with its effective date. Continued use of the platform after the effective date constitutes acceptance of the updated policy.</p>

            {{-- 16. Contact --}}
            <h2 id="contact">16. Contact — Data Protection Officer</h2>
            <p>For all data protection enquiries, rights requests, or complaints:</p>
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:0.875rem;padding:1.5rem;margin:1rem 0;">
                <p style="margin:0;"><strong>Data Protection Officer — Opes Health Systems Sarl</strong><br>
                Email: <a href="mailto:privacy@opeshealthsystems.com">privacy@opeshealthsystems.com</a><br>
                Subject line: <em>"DPO Request — [Your Name / Health ID]"</em><br>
                Response time: 30 calendar days for all valid requests</p>
            </div>

            {{-- Footer note --}}
            <div style="margin-top:4rem;padding:2rem;background:var(--color-bg,#F8FAFC);border:1px solid #E2E8F0;border-radius:1rem;font-size:0.875rem;color:var(--color-text-muted);">
                <p style="margin:0 0 0.5rem;"><strong>Effective date:</strong> 1 June 2026 &nbsp;|&nbsp; <strong>Version:</strong> 2.0 &nbsp;|&nbsp; <strong>Last updated:</strong> 7 June 2026</p>
                <p style="margin:0;">{{ __('public.privacy_page.governing_law') }} Republic of Cameroon &nbsp;|&nbsp; {{ __('public.privacy_page.footer_note') }} &nbsp;|&nbsp; For legal correspondence: <a href="mailto:legal@opeshealthsystems.com">legal@opeshealthsystems.com</a></p>
            </div>

        </div>
    </section>

@endsection

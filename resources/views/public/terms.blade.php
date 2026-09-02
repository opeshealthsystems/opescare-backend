@extends('layouts.public')

@section('title', __('public.terms_page.page_title'))
@section('meta_description', __('public.terms_page.meta_description'))

@section('content')

    <header class="content-header">
        <div class="container">
            <div class="badge" style="background:rgba(15,76,129,.12);color:#0F4C81;margin-bottom:1rem;">{{ __('public.terms_page.badge') }}</div>
            <h1>{{ __('public.terms_page.hero_title') }}</h1>
            <p class="text-muted" style="max-width:760px;margin:0 auto;">
                {{ __('public.terms_page.hero_subtitle') }}
            </p>
            <div style="margin-top:1.5rem;display:flex;justify-content:center;gap:1.5rem;flex-wrap:wrap;font-size:0.875rem;color:var(--color-text-muted);">
                <span><strong>{{ __('public.terms_page.effective_date') }}</strong> 1 June 2026</span>
                <span><strong>{{ __('public.terms_page.last_updated') }}</strong> 7 June 2026</span>
                <span><strong>{{ __('public.terms_page.version') }}</strong> 2.0</span>
                <span><strong>{{ __('public.terms_page.governing_law_label') }}</strong> Republic of Cameroon</span>
            </div>
        </div>
    </header>

    {{-- Quick nav --}}
    <section style="background:#F8FAFC;border-bottom:1px solid #E2E8F0;padding:1.25rem 0;">
        <div class="container">
            <p style="font-size:0.8125rem;color:var(--color-text-muted);margin:0 0 0.5rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">{{ __('public.terms_page.sections_label') }}</p>
            <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
                @foreach([
                    ['#parties','Parties'],
                    ['#platform','Platform Scope'],
                    ['#regulatory','Regulatory Framework'],
                    ['#eligibility','Eligibility'],
                    ['#provider-obligations','Provider Obligations'],
                    ['#patient-obligations','Patient Obligations'],
                    ['#data','Data Processing'],
                    ['#aup','Acceptable Use'],
                    ['#ip','Intellectual Property'],
                    ['#availability','Availability'],
                    ['#liability','Liability'],
                    ['#indemnity','Indemnity'],
                    ['#governing-law','Governing Law'],
                    ['#termination','Termination'],
                    ['#contact','Contact'],
                ] as [$href,$label])
                <a href="{{ $href }}" style="font-size:0.8125rem;padding:4px 12px;background:#fff;border:1px solid #E2E8F0;border-radius:999px;color:var(--color-primary);text-decoration:none;">{{ $label }}</a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="content-body">
        <div class="container rich-text" style="max-width:860px;">

            {{-- Acceptance banner --}}
            <div style="background:#FEF3C7;border:1px solid #FCD34D;border-radius:1rem;padding:1.5rem;margin-bottom:3rem;">
                <div style="display:flex;gap:1rem;align-items:flex-start;">
                    <i data-lucide="alert-triangle" style="width:1.25rem;height:1.25rem;color:#D97706;flex-shrink:0;margin-top:0.15rem;"></i>
                    <p style="margin:0;font-size:0.9rem;color:#92400E;line-height:1.65;">
                        <strong>By creating an account, registering a facility, or using any OpesCare service, you confirm that you have read, understood and agree to be bound by these Terms and Conditions in their entirety.</strong> If you do not agree, you must not use the OpesCare platform. Institutions using the platform on behalf of an employer or healthcare organisation confirm that a duly authorised representative has accepted these terms on behalf of that entity.
                    </p>
                </div>
            </div>

            {{-- 1. Parties --}}
            <h2 id="parties">1. Parties to This Agreement</h2>
            <p><strong>Service Provider:</strong> Opes Health Systems Sarl, a company incorporated under the laws of the Republic of Cameroon, operating the OpesCare health interoperability platform ("OpesCare", "we", "us", "our").</p>
            <p><strong>User:</strong> Any individual, healthcare professional, institution, organisation, or developer ("you", "your") who accesses or uses the OpesCare platform in any capacity, including as:</p>
            <ul>
                <li>A <strong>Patient</strong> — an individual registering to obtain or manage an OpesCare Health ID</li>
                <li>A <strong>Healthcare Provider</strong> — a licensed medical professional or staff member of a registered healthcare facility</li>
                <li>An <strong>Institutional Administrator</strong> — an entity registering a hospital, clinic, pharmacy, laboratory, or insurer on the platform</li>
                <li>A <strong>Developer or Technology Partner</strong> — an individual or organisation integrating with the OpesCare API, SDK, or related services</li>
            </ul>

            {{-- 2. Platform Scope --}}
            <h2 id="platform">2. Nature and Scope of the Platform</h2>
            <p>OpesCare is a <strong>digital health interoperability and identity platform</strong>. Our services include:</p>
            <ul>
                <li>Issuance and management of digital OpesCare Health IDs</li>
                <li>Secure, consent-governed exchange of patient health records between registered healthcare facilities</li>
                <li>Patient portal for health record review, consent management, and access auditing</li>
                <li>Staff and facility portals for clinical workflow support</li>
                <li>API, SDK, Widget, Bridge Agent, and Webhooks for third-party integration</li>
                <li>Medicine and blood availability verification tools</li>
                <li>Anonymous aggregated public health data reporting</li>
            </ul>
            <p><strong>OpesCare is not a healthcare provider.</strong> We do not diagnose, prescribe, treat, or offer any clinical advice. All clinical decisions are the sole responsibility of qualified, licensed healthcare professionals. We facilitate the exchange of health information to support — not replace — clinical judgment.</p>
            <p>OpesCare operates in support of the <strong>Cameroon Ministry of Public Health (MINSANTE) National Digital Health Strategy 2026–2030</strong> and the WHO Global Strategy on Digital Health 2020–2025, with the objective of contributing to universal health coverage through interoperable, patient-centred digital health infrastructure.</p>

            {{-- 3. Regulatory Framework --}}
            <h2 id="regulatory">3. Applicable Regulatory Framework</h2>
            <p>OpesCare operates under and in compliance with the following legal and regulatory instruments:</p>
            <div style="display:grid;gap:0.875rem;margin:1rem 0;">
                @foreach([
                    ['Cameroon Law No. 2010/012 of 21 December 2010','On cybersecurity and cybercriminality — governs personal data protection, electronic evidence, and cybercrime.'],
                    ['Cameroon Law No. 2010/021 of 21 December 2010','On electronic commerce — governs online service provision, electronic contracts, and digital consumer rights.'],
                    ['Cameroon Law No. 2010/013 of 21 December 2010','On electronic communications — governs telecommunications and electronic service infrastructure.'],
                    ['Cameroon Law No. 96/03 of 4 January 1996','Framework Law on Health — defines the health system structure, patient rights, and obligations of health actors in Cameroon.'],
                    ['CNAMGS Regulations','Caisse Nationale d\'Assurance Maladie et de Garantie Sociale — governing health insurance and social coverage data in Cameroon.'],
                    ['WHO Global Strategy on Digital Health 2020–2025','International framework for digital health implementation, interoperability, data governance, and patient rights.'],
                    ['HL7 FHIR R4 / ISO 27799','International standards for health data interoperability and information security management in health organisations.'],
                    ['African Union Malabo Convention (2014)','African Union Convention on Cyber Security and Personal Data Protection (EX.CL/846(XXV)) — regional data protection framework.'],
                    ['MINSANTE National Digital Health Strategy 2026–2030','Cameroon\'s national roadmap for health digitalization, EHR implementation, interoperability, and telemedicine regulation.'],
                ] as [$law,$desc])
                <div style="background:#F8FAFC;border-left:4px solid #0F4C81;border-radius:0 0.75rem 0.75rem 0;padding:1rem 1.25rem;">
                    <p style="margin:0 0 0.25rem;font-weight:700;font-size:0.9rem;color:#0F2744;">{{ $law }}</p>
                    <p style="margin:0;font-size:0.875rem;color:#4B5563;">{{ $desc }}</p>
                </div>
                @endforeach
            </div>

            {{-- 4. Eligibility --}}
            <h2 id="eligibility">4. Eligibility and Registration</h2>
            <p>To register on and use the OpesCare platform:</p>
            <ul>
                <li>You must be at least 18 years of age, or a parent or guardian registering a minor.</li>
                <li>You must provide accurate, complete, and current registration information. You agree to update this information promptly if it changes.</li>
                <li>Healthcare providers must hold a valid licence to practice issued by the competent Cameroonian professional authority (Ordre des Médecins, Ordre des Pharmaciens, Ordre National des Infirmiers, or equivalent).</li>
                <li>Healthcare institutions must hold a valid operating permit issued by MINSANTE or the competent regional health authority.</li>
                <li>Developers and technology partners must execute a separate OpesCare Partner Agreement before accessing the production API environment.</li>
                <li>You must not be prohibited by any applicable law from using the platform.</li>
            </ul>
            <p>We reserve the right to verify the credentials and licencing status of any user or institution at any time and to suspend or terminate accounts found to be non-compliant with eligibility requirements.</p>

            {{-- 5. Healthcare Provider Obligations --}}
            <h2 id="provider-obligations">5. Healthcare Provider and Institutional Obligations</h2>
            <p>Healthcare providers and institutional administrators who access patient data through OpesCare agree to:</p>
            <ul>
                <li><strong>Authorised Purpose Only:</strong> Access patient records solely for direct, legitimate clinical or administrative purposes related to the care of the patient for whom consent has been granted.</li>
                <li><strong>Staff Management:</strong> Ensure all staff with platform access are individually registered, appropriately trained, and authorised. You are responsible for revoking access for staff who leave or change roles.</li>
                <li><strong>Consent Integrity:</strong> Submit consent requests only when there is a genuine and documented clinical need. Fabrication or misrepresentation of clinical purpose is a criminal offence under Cameroon law.</li>
                <li><strong>Record Accuracy:</strong> Ensure that clinical data submitted to OpesCare is accurate, complete, and recorded in a timely manner consistent with professional obligations.</li>
                <li><strong>Data Minimisation:</strong> Request and access only the categories of clinical data necessary for the stated clinical purpose.</li>
                <li><strong>Emergency Access Accountability:</strong> Providers who invoke emergency access must record a documented clinical justification and are subject to post-event review by their facility's clinical governance team and by OpesCare compliance officers.</li>
                <li><strong>Breach Reporting:</strong> Notify OpesCare immediately (and in any case within 24 hours) upon becoming aware of any actual or suspected data breach, unauthorised access, or misuse of platform credentials.</li>
                <li><strong>Regulatory Compliance:</strong> Maintain all professional licences, facility permits, and regulatory registrations in good standing for the duration of platform use.</li>
                <li><strong>No Onward Sharing:</strong> Not transfer, copy, print, photograph, or otherwise disclose patient data obtained through OpesCare to any unauthorised third party.</li>
            </ul>

            {{-- 6. Patient Obligations --}}
            <h2 id="patient-obligations">6. Patient User Obligations</h2>
            <p>Patients using the OpesCare platform agree to:</p>
            <ul>
                <li><strong>Accurate Identity Information:</strong> Provide accurate identity information during registration. You must not create a Health ID using another person's identity.</li>
                <li><strong>Credential Security:</strong> Keep your login credentials, Health ID, and QR code secure. You must not share your account access with any other person. Notify us immediately at <a href="mailto:support@opescare.com">support@opescare.com</a> if you suspect your account has been compromised.</li>
                <li><strong>Responsible Consent Management:</strong> Exercise your consent rights responsibly. You acknowledge that revoking consent for a provider currently involved in your active care may affect the quality or continuity of your treatment.</li>
                <li><strong>Truthful Reporting:</strong> Not submit false reports, claims, or complaints about facilities or providers through the platform.</li>
                <li><strong>One Account Per Person:</strong> Maintain only one OpesCare Health ID per individual. The creation of duplicate identities undermines patient safety and is prohibited.</li>
            </ul>

            {{-- 7. Data Processing --}}
            <h2 id="data">7. Health Data Processing</h2>
            <p>The processing of personal and health data through OpesCare is governed in detail by our <a href="{{ route('public.privacy') }}" class="text-primary font-bold">Privacy Policy</a>, which forms an integral part of these Terms. By accepting these Terms, you acknowledge and consent to the processing described in the Privacy Policy.</p>
            <p><strong>Institutional Data Processing Agreement:</strong> Registered healthcare institutions are deemed to have executed a Data Processing Agreement (DPA) with Opes Health Systems Sarl by accepting these Terms. The DPA governs OpesCare's role as data processor for facility-sourced clinical records. Institutions that require a separate signed DPA for their own compliance purposes should contact <a href="mailto:legal@opeshealthsystems.com">legal@opeshealthsystems.com</a>.</p>
            <p><strong>Record Ownership:</strong> The clinical records held on OpesCare remain the property of the patient. The originating healthcare facility retains responsibility for the accuracy and completeness of records it has submitted. OpesCare acts as a data intermediary and does not own or claim intellectual property rights over patient health data.</p>

            {{-- 8. Acceptable Use --}}
            <h2 id="aup">8. Acceptable Use Policy</h2>

            <h3>8.1 Permitted Uses</h3>
            <ul>
                <li>Accessing and managing your own Health ID and medical records as a patient</li>
                <li>Requesting, granting, or revoking consent for clinical record access</li>
                <li>Accessing patient records for which you hold a valid, current consent grant in your capacity as an authorised healthcare provider</li>
                <li>Integrating OpesCare services into healthcare applications under a valid Partner Agreement</li>
                <li>Using the medication and blood availability tools for clinical decision support</li>
                <li>Reviewing and responding to access logs and audit trail entries</li>
            </ul>

            <h3>8.2 Prohibited Uses</h3>
            <p>The following activities are strictly prohibited and may result in immediate account suspension, permanent termination, and referral to the relevant authorities including ANTIC, MINSANTE, professional regulatory bodies, and the Cameroonian judiciary:</p>
            <ul>
                <li>Accessing patient records without a valid consent grant, outside of the documented emergency access protocol</li>
                <li>Using another person's credentials, QR code, or Health ID without their explicit authorisation</li>
                <li>Creating or using false clinical justifications to access patient data</li>
                <li>Attempting to reverse-engineer, circumvent, or attack the OpesCare platform or its security controls</li>
                <li>Scraping, bulk-downloading, or systematically extracting patient data beyond what is necessary for individual clinical encounters</li>
                <li>Using patient data obtained through OpesCare for insurance underwriting, employment decisions, law enforcement purposes, or marketing without lawful basis and explicit patient consent</li>
                <li>Sharing API credentials, access tokens, or authentication data with unauthorised parties</li>
                <li>Submitting false, misleading, or fabricated medical records to the platform</li>
                <li>Using the platform to facilitate any activity that violates Cameroon law, international health data standards, or professional medical ethics</li>
                <li>Reproducing, publishing, or distributing any part of the OpesCare platform, documentation, or patient data without written authorisation</li>
            </ul>

            {{-- 9. Intellectual Property --}}
            <h2 id="ip">9. Intellectual Property</h2>
            <p>All rights in the OpesCare platform, including software, designs, trademarks, logos, documentation, databases, and user interface elements, are the exclusive intellectual property of Opes Health Systems Sarl or its licensors, protected under the laws of the Republic of Cameroon, the Organisation Africaine de la Propriété Intellectuelle (OAPI), and applicable international intellectual property treaties.</p>
            <p>No use, reproduction, distribution, or creation of derivative works of OpesCare intellectual property is permitted without the prior written consent of Opes Health Systems Sarl.</p>
            <p>Patient health data and clinician-generated records are expressly excluded from the above; they remain the property of patients and originating facilities respectively.</p>
            <p>Open-source components used in the OpesCare platform are subject to their respective open-source licences. A list is available upon written request.</p>

            {{-- 10. Service Availability --}}
            <h2 id="availability">10. Service Availability and Maintenance</h2>
            <p>OpesCare targets a minimum of 99.5% platform availability for core services (Health ID Registry, Consent Engine, Patient Portal, and API). Planned maintenance windows will be communicated at least 48 hours in advance via the <a href="{{ route('public.status') }}" class="text-primary">System Status</a> page and in-platform notifications.</p>
            <p>We reserve the right to perform emergency maintenance at any time where necessary to protect the security or integrity of the platform or patient data.</p>
            <p>OpesCare does not guarantee continuous, uninterrupted availability. We are not liable for service interruptions caused by third-party infrastructure providers, telecommunications networks, force majeure events, or factors outside our reasonable control.</p>

            {{-- 11. Limitation of Liability --}}
            <h2 id="liability">11. Limitation of Liability</h2>
            <p>To the fullest extent permitted by Cameroon Law and applicable regulations:</p>
            <ul>
                <li><strong>Platform Role:</strong> OpesCare provides infrastructure for health data exchange and is not responsible for the clinical accuracy, completeness, or currency of medical records submitted by healthcare facilities. All clinical decisions must be made by qualified, licensed healthcare professionals based on their professional judgment.</li>
                <li><strong>No Clinical Liability:</strong> OpesCare expressly disclaims any liability for clinical outcomes, whether arising from the use of, or inability to use, patient records exchanged through the platform.</li>
                <li><strong>Indirect Damages:</strong> OpesCare shall not be liable for any indirect, consequential, incidental, special, or exemplary damages arising from use or inability to use the platform, including loss of profit, data loss, or business interruption.</li>
                <li><strong>Maximum Liability Cap:</strong> To the extent permitted by law, our total liability to any user in connection with these Terms shall not exceed the fees paid by that user to OpesCare in the twelve (12) months preceding the event giving rise to the claim.</li>
                <li><strong>Patient Safety Exception:</strong> Nothing in this clause limits our liability for death or personal injury caused by our gross negligence or wilful misconduct, or for fraud or fraudulent misrepresentation, or for any liability that cannot be excluded or limited under Cameroon law.</li>
            </ul>
            <p>Healthcare providers bear sole and full professional responsibility for clinical decisions made in reliance on information accessed through OpesCare, consistent with their obligations under Cameroon Law No. 96/03 and the standards of their relevant professional regulatory body.</p>

            {{-- 12. Indemnification --}}
            <h2 id="indemnity">12. Indemnification</h2>
            <p>You agree to defend, indemnify and hold harmless Opes Health Systems Sarl, its officers, directors, employees, agents, and sub-processors from and against any claims, liabilities, damages, judgements, awards, losses, costs, expenses, and legal fees arising out of or relating to:</p>
            <ul>
                <li>Your violation of these Terms</li>
                <li>Your misuse of patient data accessed through the platform</li>
                <li>Your unauthorised access to patient records</li>
                <li>Any breach of your professional obligations as a healthcare provider</li>
                <li>Any claim by a third party arising from your use of OpesCare services</li>
            </ul>

            {{-- 13. Force Majeure --}}
            <h2>13. Force Majeure</h2>
            <p>Neither party shall be in breach of these Terms for any delay or failure in performance caused by circumstances beyond their reasonable control, including but not limited to: acts of God, natural disasters, war, terrorism, civil unrest, actions of government authorities, public health emergencies declared by WHO or MINSANTE, telecommunications failures, or widespread internet infrastructure outages. Force majeure events will be notified promptly and performance obligations will be suspended for the duration of the event.</p>

            {{-- 14. Governing Law --}}
            <h2 id="governing-law">14. Governing Law and Dispute Resolution</h2>
            <p><strong>Governing Law:</strong> These Terms are governed by and construed in accordance with the laws of the <strong>Republic of Cameroon</strong>, including Law No. 2010/012, Law No. 2010/021, and the Framework Law on Health No. 96/03.</p>
            <p><strong>Amicable Resolution:</strong> In the event of a dispute arising from or in connection with these Terms, the parties agree to first attempt to resolve the matter amicably through direct negotiation for a period of thirty (30) days from the date of written notice of the dispute.</p>
            <p><strong>Jurisdiction:</strong> If the dispute is not resolved amicably within that period, it shall be submitted to the exclusive jurisdiction of the competent courts of <strong>Yaoundé, Cameroon</strong>. Both parties irrevocably submit to the jurisdiction of those courts.</p>
            <p><strong>Language:</strong> Proceedings shall be conducted in French or English, as determined by the presiding court.</p>

            {{-- 15. Termination --}}
            <h2 id="termination">15. Suspension and Termination</h2>

            <h3>15.1 By OpesCare</h3>
            <p>We reserve the right to suspend or terminate your access to OpesCare immediately and without prior notice if:</p>
            <ul>
                <li>You breach any provision of these Terms</li>
                <li>We receive a lawful order from ANTIC, MINSANTE, or a court requiring suspension or closure of your account</li>
                <li>You engage in any conduct that poses a risk to the security or integrity of the platform or patient data</li>
                <li>Your professional licence or facility permit is suspended or revoked by the relevant authority</li>
                <li>We reasonably suspect fraudulent, abusive, or unlawful use of the platform</li>
            </ul>
            <p>For less urgent matters, we will endeavour to provide 14 days' notice before termination.</p>

            <h3>15.2 By You</h3>
            <p>Patients may request account closure at any time by contacting <a href="mailto:support@opescare.com">support@opescare.com</a>. Healthcare institutions may terminate their platform agreement by providing 60 days' written notice to <a href="mailto:legal@opeshealthsystems.com">legal@opeshealthsystems.com</a>.</p>

            <h3>15.3 Effect of Termination</h3>
            <p>Upon termination:</p>
            <ul>
                <li>Your access to the platform and any associated data will be revoked</li>
                <li>Patient health records remain accessible to the patient in a portable format (HL7 FHIR R4 / JSON export) for 90 days following account closure</li>
                <li>Data is retained for the minimum periods required by law (see Privacy Policy Section 6) before secure destruction</li>
                <li>Clauses relating to intellectual property, data protection, liability, indemnity, and governing law survive termination</li>
            </ul>

            {{-- 16. Changes to Terms --}}
            <h2>16. Changes to These Terms</h2>
            <p>We may update these Terms from time to time to reflect changes in law, our services, or regulatory requirements. We will provide at least <strong>30 days' notice</strong> of material changes via email and in-platform notification before they take effect. Your continued use of OpesCare after the effective date of updated Terms constitutes acceptance. If you do not accept the updated Terms, you must discontinue use and request account closure.</p>

            {{-- 17. Severability --}}
            <h2>17. Miscellaneous</h2>
            <p><strong>Severability:</strong> If any provision of these Terms is found by a competent court to be invalid, unlawful, or unenforceable, the remaining provisions shall remain in full force and effect.</p>
            <p><strong>Waiver:</strong> Failure or delay by OpesCare in enforcing any right under these Terms shall not constitute a waiver of that right.</p>
            <p><strong>Entire Agreement:</strong> These Terms, together with the Privacy Policy, the OpesCare Consent Framework, and any applicable Partner Agreement, constitute the entire agreement between you and Opes Health Systems Sarl in respect of the OpesCare platform and supersede all prior agreements, representations, and understandings.</p>
            <p><strong>Assignment:</strong> You may not assign your rights or obligations under these Terms without our prior written consent. OpesCare may assign its rights to a successor entity in the event of a merger, acquisition, or restructuring, provided that the successor assumes all obligations under these Terms.</p>
            <p><strong>Language:</strong> These Terms are published in English. A French translation is available upon request. In the event of any conflict between the English and French versions, the English version shall prevail unless required otherwise by Cameroonian courts.</p>

            {{-- 18. Contact --}}
            <h2 id="contact">18. Contact</h2>
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:0.875rem;padding:1.5rem;margin:1rem 0;">
                <p style="margin:0;"><strong>Legal Enquiries:</strong> <a href="mailto:legal@opeshealthsystems.com">legal@opeshealthsystems.com</a><br>
                <strong>Data Protection:</strong> <a href="mailto:privacy@opeshealthsystems.com">privacy@opeshealthsystems.com</a><br>
                <strong>Technical Support:</strong> <a href="mailto:support@opescare.com">support@opescare.com</a><br>
                <strong>Partnership:</strong> <a href="mailto:partners@opescare.com">partners@opescare.com</a><br>
                <strong>Security Vulnerability Disclosure:</strong> <a href="mailto:security@opeshealthsystems.com">security@opeshealthsystems.com</a></p>
            </div>

            {{-- Footer --}}
            <div style="margin-top:4rem;padding:2rem;background:var(--color-bg,#F8FAFC);border:1px solid #E2E8F0;border-radius:1rem;font-size:0.875rem;color:var(--color-text-muted);">
                <p style="margin:0 0 0.5rem;"><strong>{{ __('public.terms_page.effective_date') }}</strong> 1 June 2026 &nbsp;|&nbsp; <strong>{{ __('public.terms_page.version') }}</strong> 2.0 &nbsp;|&nbsp; <strong>{{ __('public.terms_page.last_updated') }}</strong> 7 June 2026</p>
                <p style="margin:0;"><strong>{{ __('public.terms_page.governing_law_label') }}</strong> Republic of Cameroon &nbsp;|&nbsp; <strong>{{ __('public.terms_page.jurisdiction') }}</strong> Courts of Yaoundé, Cameroon &nbsp;|&nbsp; <strong>{{ __('public.terms_page.languages') }}</strong> English (French available on request)</p>
            </div>

        </div>
    </section>

@endsection

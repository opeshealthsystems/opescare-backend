---
title: "OpesCare: Every Module and the Problem It Solves"
slug: "opescare-modules-features-guide"
description: "A complete guide to OpesCare's modules for patients, hospitals, pharmacies, laboratories, imaging centres, blood banks and developers in Cameroon."
date: 2026-10-19
updated: 2026-10-19
author: "OPES Health Systems"
category: "Product"
tags: ["OpesCare", "product guide", "Health ID", "pharmacy", "laboratory", "blood bank", "developers"]
translation_of: "opescare-modules-fonctionnalites-guide"
hero_alt: "Overview of OpesCare modules arranged around a central Health ID: patients, hospitals, pharmacies, labs, imaging, blood banks, developers"
reading_minutes: 12
key_takeaways:
  - "OpesCare is a discovery, identity and coordination network, not a hospital information system or full medical record."
  - "Patients get a free Health ID, a QR card, a consent dashboard, an access log, documents, a Health Passport and family accounts."
  - "Facilities get desks for the counter: Health ID lookup with consent, prescriptions and dispensing, lab orders and results, referrals, queue and triage, blood inventory."
  - "Developers connect through FHIR R4 inbound ingest, an API, signed webhooks and a documented sandbox."
  - "Care is never gated on payment; mobile money payments are for bills at the counter where a facility enables them."
faq:
  - q: "Is OpesCare free for patients?"
    a: "Yes, the Health ID is free. Optional patient plans exist, but care, records and access are never blocked on payment."
  - q: "Is OpesCare an electronic medical record?"
    a: "No. OpesCare is a discovery, identity and coordination network. It holds a longitudinal record of visits, results, prescriptions and referrals, but it does not replace a hospital's own system for full clinical documentation, admissions or billing."
  - q: "Which payment methods does OpesCare support?"
    a: "MTN Mobile Money and Orange Money, for paying an invoice for care at a facility that enables it. Payments become available once the mobile-money merchant accounts are connected; until then the app says payments are unavailable. OpesCare does not process insurance claims."
  - q: "Does OpesCare work in French?"
    a: "Yes. Every screen is available in English and French, on the web and in the mobile app."
  - q: "Can a pharmacy upload its stock from a spreadsheet?"
    a: "A stock import tool that reads CSV, Excel and OpenDocument files, with column mapping and a preview before anything is committed, is available in the pharmacy stock menu ('Import stock'). Stock can also be updated from the pharmacy's stock screens."
sources:
  - title: "HL7 FHIR Release 4"
    publisher: "HL7 International"
    url: "https://hl7.org/fhir/R4/"
    accessed: 2026-10-19
---

OpesCare is easiest to understand from the problems it was built to solve. A patient is a different person at every facility. A clinician decides from half a history. A family phones five hospitals looking for blood. A pharmacy has the medicine but nobody knows.

This guide lists every OpesCare module that is live today, who it is for, and the problem each one solves. Where something is limited, being rolled out, or deliberately left out, we say so.

## What OpesCare is — and is not

OpesCare is a **discovery, identity and coordination** network for Cameroon, in English and French. Patients find care and carry one identity; facilities stay findable and serve Health ID holders at the counter.

It is **not** a hospital information system or a complete electronic medical record. Full clinical documentation, admissions and discharges, procurement, HR and insurance claims are out of scope on purpose. OpesCare connects to the software a facility already runs rather than replacing it. It is a private network operated by OPES Health Systems, not a government service.

## For patients

### Health ID and QR card

**Problem:** a new card and a new file number at every facility.
**Module:** one free Health ID, issued when you [sign up](https://opescare.com/signup/patient), with a QR card, a Presentation Mode for showing at a counter, and a public verification page so staff can check the card is genuine. Staff find you by exact ID or QR — never by name search.

### Consent and access log

**Problem:** patients cannot see who opened their record or why.
**Module:** consent requests tell you who is asking, why, for which data and for how long; you approve, deny or revoke. Every access is written to a log you can read. Emergency break-glass access opens only a limited profile, requires a reason and alerts you. Two privacy switches let you tighten what is shown. See [who can see your record](/blog/patient-consent-who-sees-your-record).

### Longitudinal record

**Problem:** history scattered across facilities.
**Module:** visits, results, prescriptions, vitals, immunisations and referrals in one timeline that outlives any single facility. You can also import past records and export your record in FHIR.

### Appointments and the queue

**Problem:** long, uncertain waits.
**Module:** book a slot with a provider at a facility, reschedule from the app, and follow your place in the facility's queue. Triage can reorder the queue by urgency; it does not make waits shorter by itself, and care is never conditioned on your place in the queue.

### Documents with a verification QR

**Problem:** paper results and certificates are easy to forge and hard to check.
**Module:** official PDFs — record exports, prescriptions and other documents — carry a QR code that leads to a verification page at opescare.com, sealed with a fingerprint of the document. Patient PDFs are generated when you download them, not stored.

### Health Passport

**Problem:** no single summary to carry when travelling or changing doctor.
**Module:** a multi-page Health Passport built from your real records. Pages with no data stay empty; nothing is invented to fill them.

### Family and guardians

**Problem:** parents and carers manage health for people who cannot manage it themselves.
**Module:** guardian accounts and family links let a parent or carer act on behalf of a child or dependent, with a guardian dashboard and inbox. Guardians can register at a dedicated sign-up page.

### Finders

**Problem:** phoning around to find a medicine, blood, a test or a facility.
**Module:** the [Medicine Finder](https://opescare.com/network/medicine-finder), [Blood Finder](https://opescare.com/network/blood-finder), lab-test finder, imaging-exam finder and the [Care Map](https://opescare.com/care-map) facility directory, with in-app maps and directions. Results come only from what facilities publish; an empty result is shown as empty.

### Payments and plans

**Problem:** paying a bill means queuing at a cashier.
**Module:** pay a facility invoice with **MTN Mobile Money** or **Orange Money** where the facility has enabled it. Payments switch on once the mobile-money merchant accounts are connected; until then OpesCare shows them as unavailable rather than pretending. Optional patient plans can be paid daily, monthly or yearly in whole francs. **Nothing in care is ever blocked on payment** — this is enforced by automated tests.

### Mobile app, offline and languages

The mobile app covers the patient journey, shows when you are offline, and syncs certain queued entries when the connection returns. The whole product is available in **English and French**.

## For hospitals, clinics and health centres

### Clinician portal

**Problem:** clinicians cannot see the history they need, safely.
**Module:** a clinician portal on the web and in the app — facility switching, patient rosters, schedule, results, referrals, messaging — with every chart read checked against a care relationship or consent grant. Clinicians can scan a patient's QR from the app.

### Prescriptions

**Problem:** handwritten prescriptions are lost, misread or forged.
**Module:** a prescriber desk issues prescriptions against the Health ID, with drug-interaction warnings. Each prescription carries its own verification link, and pharmacies dispense against it.

### Referrals

**Problem:** a referred patient arrives with no information.
**Module:** a referral desk for sending and receiving referrals, scoped to what the next provider needs.

### Queue, triage, immunisation and vitals

Front-desk queue management, triage ordering, an immunisation desk and vitals entry with consistent adult reference bands.

### Facility administration

Staff invitations and roles, departments, a ward and bed register for occupancy counts, invoices, reports with CSV export of aggregates, broadcasts, tasks and a go-live readiness checklist. Facilities can also prepare **aggregate counts** for national DHIS2 reporting, reviewed by an administrator before sending — never patient-level data.

## For pharmacies

### Dispensing by Health ID

**Problem:** no link between a prescription and what was actually dispensed.
**Module:** the counter looks up the patient by Health ID, sees the prescription, and records full or partial dispensing. Stock is decremented when the patient collects.

### Stock, prices and orders

**Problem:** patients cannot know a pharmacy has a medicine.
**Module:** stock screens feed the Medicine Finder, showing when each line was last confirmed. A price list in whole XAF keeps its history. A patient orders desk receives reservations.

### Stock import (rolling out)

A stock import tool reads CSV, Excel (.xlsx) and OpenDocument (.ods) files, lets the pharmacy map its own columns, shows a preview, and only then commits the lines as ordinary stock movements. Find it in your pharmacy stock menu under "Import stock".

## For laboratories

**Problem:** results are delayed, lost, or reach the patient before the doctor can explain them.
**Module:** a lab desk for patient requests, sample tracking and rejection, result validation and release to patient and prescriber, a printable report, critical-value read-back, and turnaround figures. The lab's published test menu feeds the public lab-test finder.

## For imaging centres

An imaging desk for requests and release of results, with radiology results linked to the patient record, and a published exam list that feeds the imaging-exam finder. Radiology reporting software and PACS stay in the centre's own systems.

## For blood banks

**Problem:** nobody knows where compatible blood is.
**Module:** a blood desk to keep inventory by group and component current, and to receive and respond to blood requests. Published inventory appears on the Blood Finder. See [how blood availability search works](/blog/blood-availability-search-cameroon).

## For developers and partners

**Problem:** every integration is a custom project.
**Module:**

- **FHIR R4 inbound ingest** for DiagnosticReport, Observation, Immunization and transaction bundles, with scoped bearer tokens;
- a REST API and documentation at [opescare.com/docs](https://opescare.com/docs);
- **signed, replayable webhooks** (internal network addresses are refused);
- a developer dashboard with activity log, quickstart and analytics;
- an embeddable widget and a sandbox.

Developers can register at the developer sign-up page. Outbound FHIR server and HL7 v2 are not available.

## Behind the scenes: identity and safety

### Master Patient Index

Duplicate records are detected at registration and in scheduled scans, then reviewed by a person. Nothing merges automatically, and merges are reversible. See [why duplicate records are dangerous](/blog/duplicate-patient-records-mpi).

### Security and privacy

- Facility identity always comes from the signed-in session, never from what a request claims.
- Cross-facility access is refused.
- Clinical entries are never deleted; they are amended or voided with a trail.
- Platform administrators must use multi-factor authentication.
- A strict content security policy and hardened webhooks protect the web application.

Read our [privacy notice](https://opescare.com/privacy) and [data protection guide](/blog/data-protection-digital-health-cameroon).

## What is deliberately absent

To avoid any confusion: OpesCare does **not** do insurance claims or pre-authorisation, procurement or supplier management, HR and payroll, admissions/discharge workflows, laboratory instrument interfaces, or full clinical notes. Telemedicine is limited to booking a consultation; there is no in-app video.

## Conclusion

Each OpesCare module answers a concrete question: who is this patient, who may see their record, where is the medicine, where is the blood, and how does information get from one facility to the next. Together they form a first-of-its-kind, patient-owned Health ID network in Cameroon.

**Patients:** [get your free Health ID](https://opescare.com/signup/patient). **Facilities:** [apply to join](https://opescare.com/signup/organization). **Developers:** [read the docs](https://opescare.com/docs). Questions? Visit [opescare.com/help](https://opescare.com/help).

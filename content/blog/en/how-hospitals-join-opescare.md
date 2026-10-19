---
title: "How Hospitals, Clinics and Pharmacies Join OpesCare"
slug: "how-hospitals-join-opescare"
description: "The five pilot steps for a Cameroonian facility to join OpesCare: apply, verification, account activation, team invitations and starting with Health IDs."
date: 2026-10-19
updated: 2026-10-19
author: "OPES Health Systems"
category: "Guides"
tags: ["hospitals", "onboarding", "pilot", "facilities", "pharmacies", "laboratories", "Cameroon"]
translation_of: "comment-hopitaux-rejoignent-opescare"
hero_alt: "Five-step pilot timeline: apply, OpesCare verifies, activate, invite your team, start with Health IDs"
reading_minutes: 9
key_takeaways:
  - "Facilities join in five steps: apply online, OpesCare verifies, activate the account, invite the team, start with Health IDs."
  - "OpesCare does not replace your existing software; it sits alongside it for identity, consent and exchange."
  - "Staff verify the patient's Health ID and ask for consent before opening a record."
  - "Each facility type — hospital, clinic, pharmacy, laboratory, imaging centre, blood bank — gets a dashboard scoped to its work at the counter."
  - "A go-live readiness checklist helps you confirm your listing, team and services before patients start arriving."
faq:
  - q: "How does a hospital join OpesCare?"
    a: "Apply online at opescare.com/signup/organization with your registration and licence numbers. OpesCare reviews the application and creates the facility account, your contact person activates it from an invitation link, you invite your team, and staff start serving patients by Health ID with consent."
  - q: "Do we have to change our hospital information system?"
    a: "No. OpesCare connects to the systems you already run. It handles patient identity, consent and exchange; your own system keeps clinical documentation, admissions and billing."
  - q: "Which types of facilities can join?"
    a: "Hospitals, clinics, health centres, pharmacies, laboratories, imaging centres and blood banks. Each has a dashboard adapted to its counter work."
  - q: "Is OpesCare approved by the Ministry of Public Health?"
    a: "OpesCare is a private network operated by OPES Health Systems. It does not claim government approval or a MINSANTE partnership. Facilities remain responsible for their own regulatory obligations."
sources:
  - title: "The 2020–2024 National Digital Health Strategic Plan"
    publisher: "Ministry of Public Health (MINSANTE), Cameroon"
    url: "https://www.minsante.cm/site/sites/default/files/EN_DOCUMENT_PLAN%20STRATEGIQUE%20NATIONAL%20DE%20SANTE%20NUMERIQUE_R%C3%A9duit.pdf"
    accessed: 2026-10-19
  - title: "Data Protection in Cameroon"
    publisher: "DLA Piper"
    url: "https://www.dlapiperdataprotection.com/?c=CM&t=law"
    accessed: 2026-10-19
---

Most facilities in Cameroon already run something — a hospital information system, a pharmacy point-of-sale, a laboratory register, or paper. Asking them to throw it away is the fastest way to make a digital health project fail. OpesCare is designed the other way round: it connects to what you have and adds what no single facility can provide alone — a patient identity that works everywhere, consent, and a way to be found.

This guide walks through how a facility joins OpesCare, step by step, and what changes on day one.

## Before you start: what OpesCare is for a facility

OpesCare is a **discovery, identity and coordination** network. For a facility, that means:

- **Being findable** — your listing, address and map location, hours, services, tests or medicines with prices in XAF, and your verification status.
- **Being reachable** — appointment requests, the day's queue and triage, patient messages, referrals in and out, blood requests, medicine reservations and lab requests.
- **Serving Health ID holders at the counter** — exact Health ID lookup with consent, a consent-scoped view of the record, prescriptions issued and dispensed, lab orders and results, vitals, immunisations, documents and audited emergency access.
- **Running the team** — staff invitations, roles, deactivation and an audit of who did what.
- **Knowing the numbers** — counts, waits, turnaround and invoices, exported as aggregates.
- **Money at the counter** — invoices and MTN or Orange mobile money payments for services rendered, never a gate on care.

It is **not** a replacement for full clinical documentation, admissions and discharges, procurement, HR or insurance claims. Those stay in your own systems.

## The five pilot steps

### Step 1: Apply online

Go to [opescare.com/signup/organization](https://opescare.com/signup/organization) and choose your facility type. You provide your facility's details, including its **registration and licence numbers**, and a contact person. When you submit, you receive a **reference** for your application.

Tip: have your official registration documents to hand and make sure the contact person's phone and email are ones they check.

### Step 2: OpesCare verifies

The OpesCare team **reviews the application** against the details you provided. Verification exists to protect patients: a facility that appears as verified on the network should be who it says it is. Once the review is complete, OpesCare **creates your facility account**.

If anything is missing, we contact the person named on the application.

### Step 3: Activate your account

Your contact person receives an **invitation link** to set up access — choosing a password and completing sign-in. From then on, the facility dashboard is available on the web and, for most roles, in the mobile app.

### Step 4: Invite your team

From your facility admin page, you **invite doctors, nurses, pharmacists, laboratory staff and desk staff**, and give each the right role. Roles matter: a receptionist and a doctor see different things, and access is checked by role on every screen. When someone leaves, you deactivate them; their past actions remain in the audit trail.

### Step 5: Start with Health IDs

Your staff start serving patients by **Health ID**. At the counter they look the patient up by exact ID or scan the QR code — there is no search by name. **Before opening the record, staff verify the Health ID and ask for consent**, unless a care relationship such as an appointment already exists. Patients without a Health ID can be helped to register at [opescare.com/signup/patient](https://opescare.com/signup/patient).

## What each facility type gets

### Hospitals, clinics and health centres

An admin overview, staff roster, departments, a ward and bed register for occupancy counts, invoices, reports with CSV export, a referral desk, an immunisation desk, queue and triage, tasks, a help desk and broadcasts. Clinicians use the clinician portal for their patients, schedule, results, prescriptions and referrals.

### Pharmacies

A dashboard with a patient orders desk, dispensing by Health ID against electronic prescriptions, stock screens that feed the public [Medicine Finder](https://opescare.com/network/medicine-finder), a whole-franc price list with history, settings with map and photo, and team management. A spreadsheet stock import is available.

### Laboratories

Patient requests, sample tracking and rejection, result validation and release, printable reports, critical-value read-back, turnaround figures, and a published test menu that feeds the public lab-test finder.

### Imaging centres

An imaging desk for requests and result release, and a published exam list that feeds the imaging-exam finder.

### Blood banks

A blood desk to keep inventory by group and component current, publish it to the [Blood Finder](https://opescare.com/network/blood-finder), and answer blood requests.

## Connecting your existing system

If your facility runs its own software, it can connect to OpesCare instead of double entry:

- **FHIR R4 inbound ingest** for diagnostic reports, observations, immunisations and transaction bundles, with scoped tokens;
- a REST API and **signed webhooks**;
- developer documentation at [opescare.com/docs](https://opescare.com/docs).

Your vendor or IT team can register as a developer and test against a sandbox. Read [how the interoperability layer works](/blog/health-id-interoperability-mpi-explained).

## Go-live readiness

Before patients arrive, use the **go-live readiness checklist** in your dashboard. It helps you confirm that:

- your listing, address, map location and hours are correct;
- your services, tests or medicines and prices are published;
- your team is invited with the right roles;
- your staff know how to look up a Health ID and request consent.

## A practical first week

Every facility is different, but a first week on OpesCare often looks like this.

### Day 1: set up the facility

The facility administrator completes "My facility" settings: address, map location, opening hours, contacts, a photo and the services offered. For a pharmacy, that means the price list and initial stock; for a laboratory, the test menu; for a blood bank, the current inventory.

### Day 2: invite the team

The administrator invites staff and assigns roles. Each person activates their own account. Shared accounts are avoided: individual accounts are what make the audit trail meaningful.

### Day 3: a short walkthrough at the counter

Desk staff practise looking a patient up by Health ID and by QR code, and sending a consent request when no care relationship exists. Clinicians practise opening a record from an appointment, writing a prescription and sending a referral.

### Days 4–5: first patients

Staff invite patients without a Health ID to register, and start serving those who have one. The administrator watches the dashboard numbers — appointments, queue, requests — and adjusts.

### End of week: review

The administrator checks the go-live readiness checklist again, confirms the public listing looks right on the [Care Map](https://opescare.com/care-map), and reviews the activity log for anything unexpected.

## Roles at a facility

Access on OpesCare is defined by role, and each role sees only what it needs. Typical roles include:

- **Facility administrator** — settings, team, reports and invoices.
- **Clinician** — patients under their care, prescriptions, results, referrals.
- **Nurse** — triage, vitals and immunisations.
- **Front desk** — appointments, queue and Health ID lookup.
- **Pharmacist** — dispensing, stock and prices.
- **Laboratory staff** — requests, samples, results and validation.
- **Blood bank staff** — inventory and blood requests.

A person can hold different roles at different facilities and switch between them; each facility's data stays separate.

## Your responsibilities

Joining OpesCare does not change your obligations as a facility. You remain responsible for your licences, for clinical decisions, and for your obligations under Cameroonian law, including Law No. 2024/017 on personal data protection. OpesCare's design — consent before access, a patient-visible access log, no name search, no cross-facility access — is meant to make good practice the default. See our [data protection guide](/blog/data-protection-digital-health-cameroon).

## What patients notice

Joining is not only an administrative change; patients feel it:

- they can **find you** on the Care Map and finders, with your hours, services and prices;
- they can **book** with you and follow their place in the queue;
- they arrive with a **Health ID** instead of a new card;
- their **results and prescriptions** from your facility appear in their own record;
- they can **verify** documents you issue by scanning the QR code;
- they can **pay a bill** by mobile money where you enable it.

## Frequently raised concerns

**"Will patients see everything we write?"** Patients see their own record and who accessed it. What a facility shares follows the record's rules and the patient's consent.

**"What if the internet goes down?"** The mobile app signals when you are offline and syncs certain queued entries when the connection returns. Your own systems keep running as before.

**"Do we have to accept mobile money?"** No. Payments are optional and enabled per facility. Care is never conditioned on payment.

## Conclusion

Joining OpesCare takes five steps: apply, verification, activation, team, Health IDs. You keep your software; you gain a patient identity that works across facilities, consent you can show, and a listing patients can actually find.

**Apply now at [opescare.com/signup/organization](https://opescare.com/signup/organization)**, or [talk to us](https://opescare.com/contact) about a pilot at your facility.

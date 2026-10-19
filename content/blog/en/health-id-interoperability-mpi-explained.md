---
title: "Health ID, Interoperability and MPI in Cameroon, Explained"
slug: "health-id-interoperability-mpi-explained"
description: "How OpesCare's Health ID, consent-based access, FHIR R4 ingest and Master Patient Index work, plus the Blood Finder, Drug Finder and Facility Directory."
date: 2026-10-19
updated: 2026-10-19
author: "OPES Health Systems"
category: "Interoperability"
tags: ["Health ID", "interoperability", "FHIR R4", "Master Patient Index", "consent", "Cameroon"]
translation_of: "identifiant-sante-interoperabilite-index-patients-explique"
hero_alt: "Diagram of a Health ID linking a hospital, a laboratory and a pharmacy through the OpesCare exchange layer"
reading_minutes: 10
key_takeaways:
  - "A Health ID is a portable identifier the patient owns; staff find a patient by exact ID or QR code, never by name search."
  - "Every clinician read is checked against a care relationship or a patient-approved consent grant, and every access is logged for the patient to see."
  - "Partners can send results to OpesCare in HL7 FHIR R4 (DiagnosticReport, Observation, Immunization, transaction bundles) with scoped bearer tokens."
  - "The Master Patient Index detects likely duplicates but never merges on its own; merges are human-reviewed and reversible."
  - "The Blood Finder, Drug Finder and Facility Directory show only what facilities publish themselves; an empty result is an honest result."
faq:
  - q: "What is a Master Patient Index?"
    a: "A Master Patient Index (MPI) is the register that makes sure one person corresponds to one patient record across systems. OpesCare's MPI flags likely duplicates and lets a trained reviewer merge them; it never merges automatically."
  - q: "Does OpesCare support FHIR?"
    a: "Yes, for inbound exchange. Partner systems can send DiagnosticReport, Observation and Immunization resources, and transaction bundles, to OpesCare's FHIR R4 endpoint using scoped tokens. Patients can also export their own record in FHIR from the mobile app. OpesCare does not run a general outbound FHIR server or HL7 v2."
  - q: "Can a hospital see my record just because I have a Health ID?"
    a: "No. A clinician needs a care relationship with you, such as an appointment or an active episode at the facility, or a consent grant you approved. You can see every access in your log."
  - q: "Is the OpesCare Health ID a national ID?"
    a: "No. It is a private, patient-centred health identifier on the OpesCare network. It does not replace any government identity document."
sources:
  - title: "HL7 FHIR Release 4"
    publisher: "HL7 International"
    url: "https://hl7.org/fhir/R4/"
    accessed: 2026-10-19
  - title: "The 2020–2024 National Digital Health Strategic Plan"
    publisher: "Ministry of Public Health (MINSANTE), Cameroon"
    url: "https://www.minsante.cm/site/sites/default/files/EN_DOCUMENT_PLAN%20STRATEGIQUE%20NATIONAL%20DE%20SANTE%20NUMERIQUE_R%C3%A9duit.pdf"
    accessed: 2026-10-19
  - title: "Cameroon begs civilians to donate blood on World Blood Donor Day"
    publisher: "VOA News"
    url: "https://www.voanews.com/a/africa_cameroon-begs-civilians-donate-blood-world-blood-donor-day/6207013.html"
    accessed: 2026-10-19
  - title: "Poor-Quality Medicines in Cameroon: A Critical Review"
    publisher: "American Journal of Tropical Medicine and Hygiene"
    url: "https://ajtmh.org/downloadpdf/journals/tpmd/105/2/article-p284.xml"
    accessed: 2026-10-19
---

"Interoperability" is one of those words that appears in every health strategy and very few waiting rooms. Cameroon's 2020–2024 National Digital Health Strategic Plan lists standards and interoperability as one of its strategic areas. But what does it look like when a patient actually walks from a laboratory to a clinic to a pharmacy?

This article explains, in plain language, the building blocks OpesCare uses: the **Health ID**, **consent-based access**, the **FHIR R4 interoperability layer**, the **Master Patient Index (MPI)**, and the three public network services — the **Blood Finder**, the **Drug Finder** and the national **Facility Directory**. Everything described here is live on [opescare.com](https://opescare.com).

## The problem in one sentence

The same person becomes a different patient at every facility, and nothing links those records back to one human being.

Everything below is a layer that tackles part of that sentence. Each layer depends on the one before it: you cannot exchange records safely until you know whose record it is, and you cannot know whose record it is until identity is resolved.

## Layer 1: the Health ID

### What it is

A Health ID is a portable identifier that belongs to the patient. You register once — on the web at [opescare.com/signup/patient](https://opescare.com/signup/patient) or in the mobile app — and you carry the same ID to every facility on the network.

Your Health ID comes with:

- a **QR card** you can show on your phone or print;
- a **Presentation Mode** on the app, designed to be shown at a counter;
- a public **verification page**, so staff can check that a QR code is genuine.

### How staff use it

At a connected facility, staff look a patient up by **exact Health ID** or by **scanning the QR code**. There is deliberately **no search by patient name**. That design choice protects patients: nobody can browse the register looking for a neighbour, a colleague or a public figure.

### What it is not

The OpesCare Health ID is not a national identity card and not a government document. It is a private, patient-centred health identifier on the OpesCare network, and it does not replace your national ID.

## Layer 2: consent-based access

Knowing who a patient is does not give anyone the right to read their record. OpesCare checks every clinician's read against a gate.

### The relationship check

A clinician can open a patient's record only when one of these is true:

- the patient has an **appointment** with that clinician;
- the clinician is on the patient's **care team**;
- the clinician is the **treating clinician**;
- the patient has an open **episode at that facility**;
- the patient has **approved a consent grant**.

If none applies, the clinician can send a **consent request**. The patient sees who is asking, why, what data, and for how long — and can approve, deny, or later revoke.

### The access log

Every access is recorded and visible to the patient in their access log. If you want to know who opened your record and when, you can check.

### Emergency access

When normal access is impossible — an unconscious patient, for example — a clinician can use **break-glass access**. It opens a limited emergency profile (identity, blood group, allergies, active conditions, emergency contact), requires a stated reason, alerts the patient, and is reviewed afterwards. Patients can also restrict this through their privacy settings.

Read more in our [patient guide to consent](/blog/patient-consent-who-sees-your-record).

## Layer 3: interoperability with FHIR R4

### Why a standard matters

A laboratory system, a hospital system and a pharmacy system each store data their own way. A standard gives them a common format. **HL7 FHIR** (Fast Healthcare Interoperability Resources) is the international standard for exchanging health data as structured "resources" over web APIs. OpesCare uses **FHIR Release 4 (R4)**.

### What OpesCare accepts today

Partner systems can send data **into** OpesCare through a FHIR R4 endpoint:

- **DiagnosticReport** — for example a laboratory report;
- **Observation** — individual results and measurements;
- **Immunization** — vaccination records;
- **transaction bundles** — several resources submitted together.

Each partner receives a **bearer token limited to specific scopes**. A partner allowed to send observations cannot send immunisations unless that scope was granted. Incoming data passes through an ingest guard that checks the patient link before anything joins a record. A public `metadata` endpoint describes what the server supports.

Patients can also **export their own record in FHIR** from the mobile app.

### What is not there yet

To be precise: OpesCare does not run a general-purpose **outbound** FHIR server for third parties to query, and it does not support **HL7 v2** messaging. If your integration needs those, [talk to us](https://opescare.com/contact) — but they are not live.

Developers can read the documentation at [opescare.com/docs](https://opescare.com/docs).

## Layer 4: the Master Patient Index

### What an MPI does

A Master Patient Index is the register that guarantees one person equals one record. Without it, a patient who registered twice — once with "Ngono Marie", once with "Marie Ngono", a different phone number and no date of birth — has two half-histories.

### How OpesCare's MPI works

- **Detection.** Possible duplicates are detected when patients are created and by scheduled scans (nightly and weekly). Administrators can also raise a pair by hand.
- **Human review.** Candidates go to an MPI desk where a trained reviewer compares them. **Nothing auto-merges.** An uncertain match is never resolved by a guess.
- **Reversible merges.** When two records are merged, one becomes the survivor and the other's Health ID becomes an **alias** that keeps working. Merges can be undone, most recent first.
- **Merge-aware lookups.** Lookups follow merges, so a lab result sent against the old identifier still reaches the right person.
- **Blocking.** Pairs that are known to be different people can be marked so they are not raised again.

This "never merge on a guess" rule is slower than automatic matching, and that is the point. A wrong merge puts one person's allergies or HIV status on another person's chart. Our article on [duplicate patient records](/blog/duplicate-patient-records-mpi) goes deeper.

## The network services

Identity and exchange are the foundation. On top of them, OpesCare runs three public services anyone can use without an account.

### The Drug Finder (Medicine Finder)

The [Medicine Finder](https://opescare.com/network/medicine-finder) shows which pharmacies on the network hold a medicine, how far they are from you, and **when they last updated** that stock.

Why it matters: studies of medicine quality in Cameroon have found that most samples come from the informal sector, where falsified products have been documented. Making it easier to find a registered pharmacy that has the medicine is one practical way to reduce that risk.

Results come only from **live stock records** whose source is on an allow-list. Seeded, demo or test data is never shown as availability. If no pharmacy has reported the medicine, the page tells you so. Full guide: [how to find a medicine in stock near you](/blog/find-medicine-in-stock-cameroon).

### The Blood Finder

The [Blood Finder](https://opescare.com/network/blood-finder) shows available blood by **group** (A, B, AB, O, Rhesus) and **component** across connected hospitals and blood banks. Blood banks keep their inventory current from a blood desk, and can receive and respond to blood requests.

Why it matters: blood donation in Cameroon has historically fallen short of need — VOA reported a sharp drop in units collected in 2020. When stocks are short, knowing where a compatible unit exists saves time. Availability is information to act on, not a reservation: always call the facility. Full guide: [how blood availability search works](/blog/blood-availability-search-cameroon).

### The Facility Directory and Care Map

The [Care Map](https://opescare.com/care-map) is a searchable directory of health facilities across Cameroon — hospitals, clinics, health centres, pharmacies, laboratories, imaging centres and blood banks — with address, location, hours and services where the facility has provided them. It understands everyday search words such as *dentiste*, *maternité* or *ophtalmologue*, and maps and directions open inside OpesCare.

Two related finders sit next to it: a **lab-test finder** and an **imaging-exam finder**, built from the test menus that facilities publish.

A facility in the directory is not necessarily a connected facility. Facilities that join OpesCare are verified and then show their live services; others appear as directory listings.

## How the layers work together: one journey

1. Awa registers and receives her Health ID.
2. At a laboratory, staff scan her QR code. The lab system sends her results to OpesCare as a FHIR DiagnosticReport.
3. She books an appointment with a doctor at a clinic. Because she has an appointment, the doctor can read her record — and the read appears in her access log.
4. The doctor writes a prescription. Awa uses the Medicine Finder to locate a pharmacy that reports the medicine, and the pharmacist dispenses it against her Health ID.
5. Months later, Awa registers at a hospital under a slightly different name. The MPI raises the pair; a reviewer confirms it is the same person and merges. Her history is whole again.

## Conclusion

Interoperability is not a single feature. It is a stack: a Health ID the patient owns, consent checked on every read, a standard format for exchange, an index that refuses to guess, and public services that tell the truth about what is available.

OpesCare is a private, patient-centred Health ID network — Cameroon's first Health ID and a first of its kind — that works alongside the systems facilities already run.

**[Get your Health ID](https://opescare.com/signup/patient)**, or, if you are a developer or facility, [read the docs](https://opescare.com/docs) and [connect your system](https://opescare.com/signup/organization).

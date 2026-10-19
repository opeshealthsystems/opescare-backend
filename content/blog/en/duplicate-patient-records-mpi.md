---
title: "Why Duplicate Patient Records Are Dangerous, and How an MPI Helps"
slug: "duplicate-patient-records-mpi"
description: "Duplicate patient records split medical histories and cause errors. How OpesCare's Master Patient Index detects duplicates and merges only after human review."
date: 2026-10-19
updated: 2026-10-19
author: "OPES Health Systems"
category: "Health ID"
tags: ["Master Patient Index", "MPI", "duplicate records", "patient safety", "patient identification"]
translation_of: "doublons-dossiers-patients-index-maitre"
hero_alt: "Two patient records with slightly different names joined by a reviewer into one record, with the old ID kept as an alias"
reading_minutes: 9
key_takeaways:
  - "WHO lists patient misidentification as a cause of medication, transfusion and testing errors."
  - "Duplicate records split a person's history, so allergies, results and medication can be missed."
  - "Where names are spelled many ways and dates of birth are often missing, duplicates are common unless something prevents them."
  - "OpesCare's MPI detects candidates at registration and in scheduled scans, but nothing merges automatically; a person reviews each pair."
  - "Merges are reversible, the old Health ID keeps working as an alias, and lookups follow merges."
faq:
  - q: "What is a duplicate patient record?"
    a: "It is when one person has two or more records in a system, usually because they were registered more than once with small differences in name, date of birth or phone number."
  - q: "Why are duplicate records dangerous?"
    a: "Because each record holds only part of the person's history. A clinician may miss an allergy or a recent result, and a result can be filed against the wrong record. WHO lists patient misidentification as a cause of medication, transfusion and testing errors."
  - q: "Does OpesCare merge records automatically?"
    a: "No. The Master Patient Index flags likely duplicates, and a trained reviewer decides. Nothing is merged on a guess, and every merge can be reversed."
  - q: "What happens to my old Health ID after a merge?"
    a: "It becomes an alias of the surviving Health ID and keeps working. A result or prescription sent against the old ID still reaches you."
sources:
  - title: "Patient Identification — Patient Safety Solutions, volume 1, solution 2 (May 2007)"
    publisher: "WHO Collaborating Centre for Patient Safety Solutions"
    url: "https://mri.alexu.edu.eg/images/patiden.pdf"
    accessed: 2026-10-19
  - title: "WHO launches 'Nine patient safety solutions'"
    publisher: "World Health Organization"
    url: "https://www.who.int/news/item/02-05-2007-who-launches-nine-patient-safety-solutions"
    accessed: 2026-10-19
  - title: "The 2020–2024 National Digital Health Strategic Plan"
    publisher: "Ministry of Public Health (MINSANTE), Cameroon"
    url: "https://www.minsante.cm/site/sites/default/files/EN_DOCUMENT_PLAN%20STRATEGIQUE%20NATIONAL%20DE%20SANTE%20NUMERIQUE_R%C3%A9duit.pdf"
    accessed: 2026-10-19
---

Imagine two records. One says "Mbarga Jean-Paul, born 1984, 6 77 …". The other says "Jean Paul Mbarga, date of birth unknown, 6 99 …". Are they the same man? If they are, his penicillin allergy is on one record and his new prescription for amoxicillin is on the other.

Duplicate patient records are one of the least visible and most dangerous problems in health information. This article explains why they happen, why they matter, and how OpesCare's **Master Patient Index (MPI)** deals with them — carefully, and never by guessing.

## What a duplicate record is

A duplicate record exists when **one person has more than one record** in a system. It usually happens at registration:

- the name is written in a different order (family name first, then given name, or the reverse);
- the spelling changes ("Ngo Bassa", "Ngobassa");
- the date of birth is missing, estimated, or entered differently;
- the phone number has changed;
- the patient did not have, or did not bring, a card from a previous visit.

Each new registration starts a new, partial history.

## Why duplicates are dangerous

### Patient identification is a safety issue

In 2007 the WHO Collaborating Centre for Patient Safety Solutions published patient identification as one of nine patient safety solutions launched by WHO. It states that failure to identify patients correctly continues to result in medication errors, transfusion errors, testing errors and wrong-person procedures. Duplicate records are one form of that failure.

### A split history hides what matters

When a history is split across two records, the clinician looking at one sees only part of it:

- an **allergy** recorded on the first record is invisible on the second;
- a **recent result** — an HIV test, a blood sugar, a pregnancy test — may be missed;
- a **current medication** may be prescribed twice, or combined with something it interacts with;
- **immunisations** may be repeated or skipped.

### Results can land on the wrong person

The opposite mistake is just as serious: two different people wrongly treated as one. Merging them puts one person's diagnosis, blood group or results on another person's record. That is why "just merge anything that looks similar" is not a safe answer.

### Planning and reporting suffer

Duplicates also inflate counts. A health system that wants reliable figures — for vaccination coverage or Universal Health Coverage enrolment — needs to count people, not records. Cameroon's 2020–2024 National Digital Health Strategic Plan names standards and interoperability among its strategic areas; reliable patient identity underpins both.

## Why Cameroon needs particular care

Several everyday realities make duplicates likely anywhere, and especially where:

- names can be written in several orders and spellings;
- many adults do not have a precisely recorded date of birth;
- phone numbers change frequently;
- patients move between public, private and faith-based facilities that do not share a register.

None of this is a failing of patients. It is a reason for systems to be designed so that one person can be recognised reliably.

## How OpesCare's MPI works

### Step 1: prevention with the Health ID

The best duplicate is the one never created. With a **Health ID**, a patient carries one identifier and a QR card. Staff look the patient up by **exact Health ID or QR** — there is no name search — so a returning patient is found rather than re-registered.

### Step 2: detection

Duplicates can still happen — someone forgets their card, or registers again. OpesCare's MPI detects possible duplicates:

- **when a patient is created**, by comparing against existing records;
- in **scheduled scans**, nightly and weekly;
- **by hand**, when an administrator spots a likely pair and raises it.

### Step 3: human review — nothing auto-merges

Every candidate pair goes to an **MPI desk**. A trained reviewer compares the records and decides: same person, or different people. **OpesCare never merges automatically**, and an uncertain match is never resolved by a guess.

This is slower than fully automatic matching. It is deliberate. A wrong merge is harder to detect and more harmful than a duplicate that waits a day for review.

### Step 4: a reversible merge

When the reviewer confirms a match:

- one record becomes the **survivor**;
- the other's Health ID becomes an **alias** that keeps working;
- the history is combined under the survivor.

Merges can be **undone**, most recent first, and the reversal is available from the desk's merged list.

### Step 5: lookups that follow merges

Many parts of OpesCare — prescriptions, lab results, appointments, documents — **follow merges**. A laboratory that sends a result against the old Health ID still reaches the right person.

### Step 6: remembering "not the same"

If the reviewer decides two records belong to different people — two cousins with the same name, for example — the pair can be **blocked** so it is not raised again.

## Why not let the computer decide?

Many systems elsewhere use "probabilistic matching": an algorithm scores how similar two records are and merges automatically above a threshold. It is fast, and it can work well where names are spelled consistently and every record carries a reliable date of birth and national identifier.

Those conditions are often missing. Under them, an automatic threshold faces an impossible trade-off:

- set it **low**, and it merges different people — two brothers with similar names, a mother and daughter sharing a phone;
- set it **high**, and it misses most real duplicates, so the problem remains.

OpesCare uses algorithms to **find** candidates, because a computer is good at scanning thousands of records overnight. It leaves the **decision** to a person, because a person can see context: a different village, a different mother's name, a note that one record belongs to a child.

## What a reviewer looks at

A reviewer comparing a pair typically considers:

- name variants and order;
- date of birth, or the absence of one;
- sex;
- phone numbers and addresses, current and past;
- facilities visited and dates of visits;
- whether one record could belong to a relative.

If the evidence is not strong enough, the pair waits or is marked as different. "I am not sure" is an acceptable answer; a wrong merge is not.

## A worked example

A patient registered at a health centre in 2025 as "Ngo Bassa Esther", with a date of birth. In 2026 she registered at a district hospital as "Esther Ngobassa", without one, because she had left her card at home.

1. The nightly scan raises the pair as a candidate.
2. A reviewer sees the same phone number, the same village, and no conflicting details. She confirms the match.
3. The 2025 record becomes the survivor; the 2026 Health ID becomes an alias.
4. A lab result that the hospital sends the following week against the 2026 ID arrives on her single record.
5. Had the reviewer been wrong, the merge could have been undone from the merged list.

## What patients can do

- **Bring your Health ID** — on your phone or printed — to every visit.
- **Tell the desk if you already have a Health ID**, even if you forgot the card.
- **Check your details** — name order, date of birth, phone number — when you register.
- **Look at your record** in the app. If you see results or visits that are not yours, or miss ones that are, [contact us](https://opescare.com/contact).

## What the MPI does not do

To be clear about limits:

- It does **not** verify civil identity against a government register. The OpesCare Health ID is a health identifier on a private network, not a national ID.
- It does **not** merge records held inside a facility's own software. It resolves identity on the OpesCare network; connected systems link to the Health ID.
- It does **not** guarantee that every duplicate is found immediately. Scans run nightly and weekly, and review takes human time.
- It does **not** delete clinical history. Merging combines records under a survivor; nothing clinical is erased.

## What facilities can do

- Look patients up **by Health ID or QR first**, before registering anyone new.
- Record the **date of birth** and phone number carefully.
- If your own system holds patient identifiers, connect it so results arrive linked to the Health ID — see [the interoperability layer explained](/blog/health-id-interoperability-mpi-explained).

## Conclusion

Duplicate records are not a clerical nuisance; they are a patient safety risk. OpesCare tackles them in layers: a Health ID that prevents re-registration, detection at creation and on a schedule, human review with no automatic merging, reversible merges with working aliases, and lookups that follow merges.

**[Get your free Health ID](https://opescare.com/signup/patient)** so your history stays in one place. Facilities: [join the network](https://opescare.com/signup/organization) and stop creating new duplicates at the front desk.

Related reading: [every OpesCare module explained](/blog/opescare-modules-features-guide).

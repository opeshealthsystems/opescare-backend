# 10 — Health ID card (CR80)

Template `resources/views/portals/patient/health-id-card-pdf.blade.php`, route
`GET patient/health-id-card.pdf`. 90 × 54 mm, `@page margin 0`, own design (not the A4 sheet).

**Fields.** Name, Health ID, DOB, sex, blood group, verified/provisional pill, QR to
`/verify/qr` (token only, no PII), issue date.

**Legibility pass (2026-10-18).** The smallest text was 3.9–4.6px (~3pt) and unreadable in
print. New floor: labels and captions ≥ 5.4px (~4pt, which at card size is the practical ISO/IEC
7810 wallet-card minimum), values 7.6px, name 9.5px, Health ID 9px mono. Footer text is now
white on the navy band instead of grey.

Security: QR, verified shield/pill, brand seal. EN/FR: `family.card_*`.

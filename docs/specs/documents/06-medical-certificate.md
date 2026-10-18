# 06 — Medical certificate (certificat médical)

**Issuing flow (new).** Certificate desk, `capability:prescribe` (doctors, medical supervisors):

| Route | Name |
|---|---|
| `GET facility/certificates` | `portals.certificates.index` (list issued by this facility) |
| `GET facility/certificates/new?type=medical_certificate` | `portals.certificates.create` (Health ID lookup, merge-following) |
| `POST facility/certificates` | `portals.certificates.store` |
| `GET facility/certificates/{id}/pdf` | `portals.certificates.pdf` (issuing facility only) |

Nav: "Certificates" link in the doctor, specialist, resident and medical-supervisor sidebars.
Controller `App\Http\Controllers\MedicalId\CertificateIssuanceController`; issuance through the
existing `DocumentIssuanceService::issue()` (document number, verification code + `vdt_` token,
audit). Facility from request attributes / `DeskFacilityContext` — never from the body. The
clinician must hold a clinical relationship (`ClinicalRelationshipGate`).

**Render.** `resources/views/documents/certificate.blade.php` via `PatientDocumentService`
(type `certificate`); the patient downloads it from their documents page
(`patient/documents/{id}/download`), the facility from the desk. Never stored.

**Basis.** Code de déontologie médicale (Cameroon): a certificate states only what the
physician personally observed, is dated and signed, and is handed to the patient
**[U: article numbers]**.

## Payload (`official_documents.payload_json`)
| Key | Meaning |
|---|---|
| `certificate_kind` | `medical_certificate` |
| `examined_on` | date of examination (≤ today) |
| `purpose` | `general_fitness`, `school`, `employment`, `sport`, `travel`, `administrative`, `other` (translated) |
| `statement` | the physician's findings and conclusion (required) |
| `recipient` | optional addressee |
| `issuer_name`, `issuer_credential`, `issuer_licence` | snapshot of the signatory at issue |

Validity: `expires_at` = now + validity_days (default 90).

## Sheet
Identity (+sex) → document no., issued, valid until, state pill → issuing facility block →
attestation sentence ("I, the undersigned … certify that I examined … on …") + statement box →
particulars (translated labels; unknown legacy keys still printed, humanised) → signature block
with registration no. → verification code. Revoked/expired: watermark on every page.

Security: `/verify/doc` QR (sha256-sealed) **and** the `vdt_` verification code. EN/FR:
`document.cert_*`, `doc_ex_cert.*`.

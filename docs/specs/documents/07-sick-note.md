# 07 — Sick note / work stoppage (arrêt de travail)

Same desk, routes, render and security as 06, with `type=sick_note`.

**Purpose.** Justify absence from work to an employer / CNPS.

**Basis.** Cameroon Labour Code (Law 92/007 of 14 Aug 1992) — sickness suspends the contract
on production of a medical certificate **[U: exact article, believed art. 32]**. Medical
secrecy: the note must **not** disclose a diagnosis; the form says so and the hint tells the
clinician not to write one.

## Payload
| Key | Meaning |
|---|---|
| `certificate_kind` | `sick_note` |
| `examined_on` | date of examination |
| `rest_from`, `rest_to` | inclusive dates (`rest_to ≥ rest_from`) |
| `rest_days` | computed, inclusive |
| `statement` | that rest is medically required (no diagnosis) |
| `recipient` | optional (employer) |
| `issuer_*` | signatory snapshot |

`expires_at` = end of `rest_to`.

Sheet sentence: "I, the undersigned …, certify that the condition of … requires rest from
work for N day(s), from … to … inclusive." plus a medical-confidentiality line.

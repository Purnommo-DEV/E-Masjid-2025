# Governed Master Data Inventory — Financial V2

**Audit date:** 2026-08-09  
**Scope:** master-data onboarding only. This inventory does not authorize a historical migration, opening balance, cutover, or transaction posting.

## Decision rule

A value is eligible for Financial V2 onboarding only when its source identifies the value, accountable owner, effective date, and the applicable approval or policy basis. A source-code constant, legacy seed, UI example, or a test fixture is a **candidate**, not an approved V2 master by itself.

`Account`, `FinancialAccount`, `Fund`, and `Program` remain separate dimensions. No candidate below is mapped across those dimensions without an approved mapping register.

## Sources inspected

| Source | Result | Treatment |
|---|---|---|
| `FINANCIAL-ARCHITECTURE-V2.md` | Architecture and conceptual examples; no approved operating register. | Design constraint only; do not seed values from examples. |
| `ACCOUNTING-POLICY-FINANCIAL-GOVERNANCE-MANUAL.md` | Policy, business rules, conceptual voucher formats, and evidence types. | Policy source; it does not supply entity-specific master values, limits, retention, or approval matrix. |
| `FINANCIAL-PHASE-2-IMPLEMENTATION-BLUEPRINT.md` | Requires approved Fund, FinancialAccount, CoA, voucher, evidence, and approval registers. | Confirms the missing governance artefacts; it is not a master-data register. |
| `FINANCIAL-PHASE-2-ACCOUNTING-FOUNDATION.md` | V2 data contract and control requirements. | Technical design only; no business master records. |
| `database/seeders/AkunKeuanganSeeder.php` | 41 legacy-account candidates, including liquidity, liability, revenue, expense, and net-asset labels. | Legacy mapping candidate only. No V2 AccountGroup mapping, entity owner, effective date, or Finance Controller sign-off exists. |
| `database/seeders/KeuanganSeeder.php` | Four cash-box labels and eight legacy transaction-category labels. | Legacy/UI candidates only; no V2 transaction-type/default-rule mapping or approval. |
| `database/seeders/QurbanSettingSeeder.php` and `qurban_settings` | Qurban presentation settings include a bank reference. | Sensitive operational configuration; not a FinancialAccount register and not onboarded. The number is deliberately omitted here. |
| `database/seeders/DanaTerikatMustahikSeeder.php` / `DanaTerikatStatusApril2026Seeder.php` | Refer to legacy `ZISWAF-2026` and beneficiary data. | Historical/legacy source candidate. The development DB has no corresponding program row; no Fund classification, restriction matrix, or approval package exists. |
| Existing legacy development tables | `masjids`, `profil_masjids`, `akun_keuangan`, `kategori_keuangans`, `jenis_kotak_infaks`, and reviewed `dana_terikat_*` tables contain zero rows. | No usable live master source. Read-only audit only. |
| Existing V2 development tables | All scoped V2 master registers contain zero rows. | Expected before governed onboarding; no seed was run. |
| Financial V2 tests | Deterministic fixture values only. | Test-only data; prohibited as production/development master source. |

## Development-database snapshot

The local development connection is `mrj_prod_db`. The audit was read-only.

| Register / fact | Count before onboarding decision | Result |
|---|---:|---|
| `financial_v2_accounting_entities` | 0 | No V2 entity available for a master register. |
| `financial_v2_accounts` / `financial_v2_financial_accounts` | 0 / 0 | No V2 CoA or FinancialAccount exists. |
| `financial_v2_funds` / `financial_v2_programs` / `financial_v2_categories` | 0 / 0 / 0 | No V2 attribution master exists. |
| `financial_v2_posting_rules` / `financial_v2_document_sequences` | 0 / 0 | No business rule catalogue or approved sequence exists. |
| `financial_v2_journals` / `financial_v2_journal_lines` / `financial_v2_ledger_entries` | 0 / 0 / 0 | No official accounting fact exists. |
| `financial_v2_opening_balance_batches` | 0 | No opening balance exists. |

## Master-register readiness

| Master scope | Candidate/source found | Ready to onboard | Gap / required decision |
|---|---|---|---|
| Accounting Entity | Legacy seed examples only; no live profile row or legal owner package. | No | Legal name, entity code, functional currency, timezone, fiscal-year start, owner, effective date, approval reference. |
| Accounting Calendar & Period | No calendar register. | No | Fiscal calendar, period boundaries/statuses, and accountable approver. |
| Account Group | Implicit legacy types/groups only. | No | Approved V2 group taxonomy and legacy-to-V2 mapping. |
| Chart of Accounts | 41 legacy candidates. | No | Signed V2 CoA register including V2 class, group, normal balance, posting/liquidity/control flags, validity, dimension rules, and mapping rationale. |
| FinancialAccount / Rekening | Bank/cash labels and a sensitive Qurban bank configuration. | No | Verified account holder, custody, status, opening/closing date, masked/protected reference, and approval. Do not infer a FinancialAccount from an Account label. |
| Fund Type & Restriction | Zakat, infaq, shodaqoh, wakaf, qurban, and dana-terikat labels occur in legacy/code context. | No | Formal classification, restriction severity, purpose, policy basis, effective dates, and Syariah/finance sign-off where applicable. |
| Fund & Fund Policy Version | Legacy labels/candidates only. | No | Approved Fund register and executable allowed/prohibited matrix; missing rule must remain fail-closed. |
| Program / Cost Center | Qurban and `ZISWAF-2026` references; no live legacy program row. | No | Approved code/name, owner, dates/status, and explicit confirmation that it is a use/cost-center dimension—not a Fund. |
| Category | Eight legacy labels. | No | V2 transaction-type association, default posting-rule relationship, validity, and approval. |
| Counterparty | Legacy beneficiary/reference names and Qurban contacts. | No | Privacy review, party type, source of authority, effective status, and approved import scope. |
| Transaction Type | Architecture/policy specifies RCV, PAY, TRF, IFT, ALLOC, REALIZATION, OPB, ADJ, and REV. | No | Entity-specific activation and owner/effective-date approval. Codes in a design are not an active operating catalogue. |
| Posting Rule & Version | Test fixtures and conceptual templates only. | No | Approved source transaction, debit/credit lines, resolvers, dimension rules, effective dates, and Finance Controller sign-off. |
| Document Sequence | Conceptual formats only. | No | Per-entity/type scope, prefix, reset rule, numbering policy, effective date, and approval. |
| Approval configuration | Policy requires it, but no limit/authority matrix is present. | No | Approved approval matrix including thresholds, roles, segregation, effective dates, and overrides. |
| Evidence requirement | Policy names evidence classes and V2 enforces supported file types. | Partial technical control only | Approved per-transaction requirement/optionality, retention period, supersession policy, and policy-owner approval. |

## Conflicts and duplicate risks

1. The legacy `AkunKeuanganSeeder` mixes liquidity locations, income/expense accounts, and Fund-like labels. Reusing it as V2 CoA would breach the V2 separation of `Account`, `FinancialAccount`, and `Fund`.
2. Legacy terminology contains both `Infaq` and `Infak`, and `Shodaqoh`/`Shadaqah`; no canonical naming or mapping decision exists.
3. A Qurban payment setting cannot establish a Masjid FinancialAccount: account holder, custody, whether it is still active, and relation to a legal accounting entity are not evidenced.
4. `ZISWAF-2026` is referenced by a legacy seeder but not created by it or found in the local legacy database. It cannot be assumed to be a Program, Fund, or both.
5. Legacy beneficiary/personal reference data is not a Counterparty onboarding source without purpose, lawful basis, minimisation, and approval.

## Onboarding result for this run

**No business master rows were created.** This is intentional and is the safe automatic outcome: every candidate requires a business, accounting, or Syariah decision that is absent from the inspected sources.

The V2 schema, master-governance services, and test-only fixtures remain available for a future approved reference-data package. This inventory is the required onboarding gap register; it does not convert legacy records or designate a cutover date.

## Minimum approval package for the next onboarding run

1. Accounting entity and fiscal-calendar charter.
2. Signed V2 CoA and AccountGroup register with dimension requirement mapping.
3. Verified FinancialAccount register with masked/protected identifiers and custodian approval.
4. Fund register, restriction classification, policy versions, and allowed/prohibited decision matrix.
5. Program/CostCenter and Category registers with explicit non-Fund classification.
6. Posting-rule catalogue and document-sequence policy approved by the Finance Controller.
7. Approval matrix and evidence-retention matrix approved by the policy owner; Syariah review where required.

Until those artefacts exist, Financial V2 remains master-empty, no financial facts may be posted, and no opening balance or cutover work may begin.

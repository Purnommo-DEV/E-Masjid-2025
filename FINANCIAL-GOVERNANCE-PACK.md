# Financial Governance Pack — Financial V2

**Status:** `READY FOR APPROVAL`  
**Prepared:** 2026-08-09  
**Scope:** a no-code working document for the authorised owner, Finance Controller, treasury/custodian, and Syariah reviewer where applicable.

## Purpose and use

This pack turns the gaps recorded in `FINANCIAL-GOVERNED-MASTER-DATA.md` and `GOVERNED-MASTER-DATA-INVENTORY.md` into decisions that can be formally approved. It is not an approval record by itself: an `APPROVED` status may be used only after the required owner, evidence reference, and effective date have been completed.

The pack preserves these binding distinctions:

- `Account` classifies an accounting effect; `FinancialAccount` identifies a cash/bank/e-wallet custody location; `Fund` identifies restriction/ownership; `Program` identifies use/cost centre.
- Ledger remains the only official balance source. A register or policy approval creates no balance.
- A policy or legacy value below is a **reference/candidate**, never an approved V2 master unless the owner completes its approval fields.

**Source hierarchy used:** Financial Architecture V2; Accounting Policy & Financial Governance Manual; Financial Phase 2 Implementation Blueprint; Financial Phase 2 Accounting Foundation; governed-master register/inventory; and the Phase 3 implementation report.

---

## 1. Governance Decision Register

Status meanings: `APPROVED` = signed evidence exists; `PENDING` = decision is defined but awaits formal approval; `GAP` = required value/evidence is absent; `NOT APPLICABLE` = not executable in this gate.

| ID | Decision | Required Owner | Required Evidence | Current Status | Decision Needed | Effective Date | Impact |
|---|---|---|---|---|---|---|---|
| GOV-01 | Accounting Entity, currency, timezone, fiscal convention, and calendar | Policy owner + Finance Controller | Entity/calendar charter and approval reference | GAP | Identify legal/operating entity, code, currency, timezone, fiscal-year boundaries, and period convention | [TO BE APPROVED] | Blocks every scoped V2 master and period. |
| GOV-02 | Accounting basis and restricted-contribution treatment | Policy owner | Approved policy memo; accounting treatment rationale | PENDING | Confirm the policy choice reflected in the manual without adding an unapproved treatment | [TO BE APPROVED] | Controls CoA, Fund presentation, and posting rules. |
| GOV-03 | Fund register, restriction class, permitted use, and Syariah review | Policy owner; Syariah reviewer for Zakat/Fidyah/Wakaf/Qurban | Signed Fund register, policy matrix, review record | GAP | Approve the actual Funds in scope, their policy versions, and effective dates | [TO BE APPROVED] | Restricted Funds must fail closed until approved. |
| GOV-04 | FinancialAccount register and Fund–FinancialAccount matrix | Financial authority + treasury/custodian | Verified account/cash register, masked identifier, custodian and reconciliation schedule | GAP | Confirm live accounts/cash locations, permitted Funds, status, and owner | [TO BE APPROVED] | No live cash/bank custody master may be activated. |
| GOV-05 | V2 CoA, AccountGroup, normal balance, and dimension requirements | Policy owner + Finance Controller | Signed V2 CoA, group/dimension matrix, legacy mapping rationale | GAP | Approve V2 mapping or exception for every legacy candidate in scope | [TO BE APPROVED] | Blocks deterministic debit/credit rules and FinancialAccount linkage. |
| GOV-06 | Program, CostCenter, Category, and Counterparty scope | Operational owner + Finance Controller; privacy owner where applicable | Approved register, owner, effective dates, privacy basis | GAP | Define operational dimensions without converting them into Funds | [TO BE APPROVED] | Blocks attributable operating transactions where these dimensions are required. |
| GOV-07 | Transaction types and Posting Rule Catalogue | Finance Controller; Policy owner for accounting policy | Versioned catalogue, resolver mapping, approval evidence | GAP | Approve no-ID-hardcoded rule versions for RCV/PAY/TRF/IFT/ALLOC/REALIZATION/OPB/ADJ/REV | [TO BE APPROVED] | Blocks posting; Posting Engine must not infer mappings. |
| GOV-08 | Voucher policy and DocumentSequence scope | Finance Controller | Approved numbering policy and scope specification | PENDING | Select official entity/type/period format, issuance point, reset policy, and journal-voucher relation | [TO BE APPROVED] | Voucher uniqueness must be atomic and auditable. |
| GOV-09 | Approval limits, segregation, and exceptions | Financial authority | Signed approval matrix and mandate/limit reference | GAP | Set thresholds, required approvers, exception authority, and segregation rules | [TO BE APPROVED] | Material transactions cannot be safely approved. |
| GOV-10 | Evidence, retention, supersession, privacy, and legal hold | Policy owner | Approved evidence/retention matrix and access policy | GAP | Confirm mandatory evidence, retention period, supersession, hash, and access rules | [TO BE APPROVED] | Evidence completeness/retention cannot be finalised. |
| GOV-11 | Opening-balance method and reconciliation baseline | Finance Controller; migration lead; financial authority | Method paper, mapping/evidence template, reconciliation plan | NOT APPLICABLE | Do not approve or create an opening balance in this gate; prepare only after GOV-01–10 are approved | [TO BE APPROVED] | Future G3 prerequisite; no amount is authorised. |
| GOV-12 | Cutover charter and go/no-go authority | Policy owner + authorised steering/financial authority | Formal cutover charter, UAT/sign-off, safety gate | NOT APPLICABLE | Do not select a cutover date in this gate | [TO BE APPROVED] | Future G4/G5 prerequisite; V2 remains inactive. |

---

## 2. Accounting Entity & Calendar

No entity or period is being created. Complete the following once for each legal/operating accounting boundary.

| Decision field | Value / decision | Required evidence | Required owner | Approval |
|---|---|---|---|---|
| Accounting Entity code | [TO BE APPROVED] | Charter / authority record | Policy owner | [TO BE APPROVED] |
| Official name | [TO BE APPROVED] | Legal/organisational record | Policy owner | [TO BE APPROVED] |
| Legal name, if different | [TO BE APPROVED] | Legal record | Policy owner | [TO BE APPROVED] |
| Functional currency | [TO BE APPROVED] | Accounting policy memo | Policy owner + Finance Controller | [TO BE APPROVED] |
| Timezone | [TO BE APPROVED] | Operating-calendar decision | Policy owner | [TO BE APPROVED] |
| Fiscal-year start / end | [TO BE APPROVED] | Fiscal calendar | Policy owner + Finance Controller | [TO BE APPROVED] |
| Accounting period convention | [TO BE APPROVED] | Calendar and period-status policy | Finance Controller | [TO BE APPROVED] |
| Calendar code/name | [TO BE APPROVED] | Approved calendar register | Finance Controller | [TO BE APPROVED] |
| Period boundaries and initial status | [TO BE APPROVED] | Approved period schedule | Finance Controller | [TO BE APPROVED] |

The Foundation requires no overlapping active periods for an Accounting Entity. This pack does not select dates or make any period Open.

---

## 3. Chart of Accounts

### 3.1 V2 CoA approval template

| Code | Name | Account Type | Account Group | Normal Balance | Parent | Active | Effective Date | Owner Approval |
|---|---|---|---|---|---|---|---|---|
| [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] |

Before approval, attach the dimension requirement matrix for Fund, FinancialAccount, Program, CostCenter, Counterparty, and Category. A leaf liquidity Account must be separately linked to a verified FinancialAccount; a header Account cannot receive postings.

### 3.2 Legacy CoA candidate catalogue — not approved V2 Accounts

The `AkunKeuanganSeeder` contains **41** candidate rows. Its legacy type/group is retained below solely to support mapping review; it is not a V2 AccountGroup, Fund, or FinancialAccount decision.

| Legacy Code | Legacy Candidate Name | Legacy Type | Normal Balance | V2 Status |
|---|---|---|---|---|
| 10001 | Kas Utama | aset | debit | LEGACY CANDIDATE |
| 10002 | Bank Syariah Indonesia (BSI) | aset | debit | LEGACY CANDIDATE |
| 10003 | Bank BNI | aset | debit | LEGACY CANDIDATE |
| 10004 | Bank Mandiri Syariah | aset | debit | LEGACY CANDIDATE |
| 10005 | Kas Kecil (Petty Cash) | aset | debit | LEGACY CANDIDATE |
| 10006 | Piutang Donatur | aset | debit | LEGACY CANDIDATE |
| 11001 | Tanah & Bangunan Masjid | aset | debit | LEGACY CANDIDATE |
| 11002 | Akumulasi Penyusutan Bangunan | aset | kredit | LEGACY CANDIDATE |
| 20001 | Zakat Fitrah Belum Disalurkan | liabilitas | kredit | LEGACY CANDIDATE |
| 20002 | Zakat Maal | liabilitas | kredit | LEGACY CANDIDATE |
| 20003 | Fidyah Belum Disalurkan | liabilitas | kredit | LEGACY CANDIDATE |
| 20004 | Infaq Terikat (Yatim/Dhuafa) | liabilitas | kredit | LEGACY CANDIDATE |
| 20005 | Infaq Umum | liabilitas | kredit | LEGACY CANDIDATE |
| 20006 | Shodaqoh | liabilitas | kredit | LEGACY CANDIDATE |
| 20099 | Dana Titipan (Belum Dikelompokkan) | liabilitas | kredit | LEGACY CANDIDATE |
| 30001 | Infak Kotak Jumat | pendapatan | kredit | LEGACY CANDIDATE |
| 30002 | Infak Kotak Kajian | pendapatan | kredit | LEGACY CANDIDATE |
| 30003 | Infak Kotak Ramadhan | pendapatan | kredit | LEGACY CANDIDATE |
| 30004 | Infak Kotak Qurban | pendapatan | kredit | LEGACY CANDIDATE |
| 30005 | Infaq/Shadaqah Umum (Kotak) | pendapatan | kredit | LEGACY CANDIDATE |
| 30006 | Donasi Umum / Non-Terikat | pendapatan | kredit | LEGACY CANDIDATE |
| 30007 | QRIS / Transfer Infak Umum | pendapatan | kredit | LEGACY CANDIDATE |
| 30008 | Hibah Non-Terikat | pendapatan | kredit | LEGACY CANDIDATE |
| 40001 | Perlengkapan Kebersihan | beban | debit | LEGACY CANDIDATE |
| 40002 | Konsumsi Marbot | beban | debit | LEGACY CANDIDATE |
| 40003 | Air Minum Jamaah | beban | debit | LEGACY CANDIDATE |
| 40004 | Jumat Berkah | beban | debit | LEGACY CANDIDATE |
| 40005 | Biaya Pemeliharaan Kecil | beban | debit | LEGACY CANDIDATE |
| 40006 | Beban Admin Bank | beban | debit | LEGACY CANDIDATE |
| 40010 | Gaji Imam | beban | debit | LEGACY CANDIDATE |
| 40011 | Gaji Marbot | beban | debit | LEGACY CANDIDATE |
| 40012 | Honor Khatib Jumat | beban | debit | LEGACY CANDIDATE |
| 40013 | Honor Pengajian | beban | debit | LEGACY CANDIDATE |
| 40014 | Honor Muadzin | beban | debit | LEGACY CANDIDATE |
| 40015 | Listrik & Air PDAM | beban | debit | LEGACY CANDIDATE |
| 40016 | Internet & Komunikasi | beban | debit | LEGACY CANDIDATE |
| 40017 | Penyusutan Aset | beban | debit | LEGACY CANDIDATE |
| 40018 | Biaya Operasional Distribusi | beban | debit | LEGACY CANDIDATE |
| 50001 | Saldo Awal Pembuka / Opening Balance Equity | ekuitas | kredit | LEGACY CANDIDATE |
| 50002 | Dana Operasional Masjid | ekuitas | kredit | LEGACY CANDIDATE |
| 50003 | Dana Abadi Masjid | ekuitas | kredit | LEGACY CANDIDATE |

Mapping notes requiring explicit approval: legacy labels mix liquidity locations, accounting classifications, and Fund-like labels; `Infaq`/`Infak` and `Shodaqoh`/`Shadaqah` require canonical naming; and legacy Fund-like/equity labels must not be silently made V2 Funds or Accounts.

---

## 4. Financial Account / Rekening Register

FinancialAccount is a custody/location master. It is not a Fund and it is not an Account. Do not include an unmasked account number in this document.

| Financial Account | Type | Custodian | Bank/Cash | Purpose | Currency | Status | Effective Date | Owner | Approval |
|---|---|---|---|---|---|---|---|---|---|
| [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] |

Policy references below are **not verified FinancialAccounts**. They require internal/masked identifier, account mapping, Fund–FinancialAccount matrix, reconciliation schedule, custody confirmation, and approval before activation.

| Policy reference candidate | Policy-described type/purpose | Current register status |
|---|---|---|
| Kas Operasional | Operational cash | PENDING verification |
| Rekening BNI ZIS | ZIS bank custody | PENDING verification |
| Rekening BSI | General/syariah bank custody | PENDING verification |
| Rekening Mandiri | Activity/partnership bank custody | PENDING verification |
| Kas Sosial & Kematian | Social/calamity cash custody | PENDING verification |
| Rekening Sewa Aula | Facility-rental bank custody | PENDING verification |
| Petty Cash | Limited cash custody | PENDING verification |
| Rekening Wakaf Khusus | Wakaf custody, if established | PENDING verification |

---

## 5. Fund Register

Fund classification below is a policy baseline from Manual Chapter 3, not a created Fund master. Fund Code, exact restriction class, permissible usage, effective date, policy version, owner, and approval must be completed per actual Fund.

| Fund Code | Fund Name / Policy Baseline | Fund Type / Restriction Class Supported by Policy | Purpose / Usage Boundary | Effective Date | Policy Version | Owner | Approval |
|---|---|---|---|---|---|---|---|
| [TO BE APPROVED] | Dana Operasional | unrestricted or internally designated; selection required | Routine masjid operations; not restricted donor/syariah funds | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] |
| [TO BE APPROVED] | Dana Zakat Maal | restricted/statutory; Syariah review required | Eligible Zakat distribution only under approved policy | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] |
| [TO BE APPROVED] | Dana Zakat Fitrah | restricted/statutory; Syariah review required | Eligible Zakat Fitrah distribution only | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] |
| [TO BE APPROVED] | Dana Infaq Umum | unrestricted unless donor message restricts it | General masjid/social use subject to donor message | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] |
| [TO BE APPROVED] | Dana Tromol | unrestricted or restricted according to collection label | Collection channel; label/restriction governs final Fund | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] |
| [TO BE APPROVED] | Dana Sedekah | unrestricted unless donor message restricts it | Social/ibadah/education/approved operations | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] |
| [TO BE APPROVED] | Dana Santunan Anak Yatim | donor restricted/designated | Eligible beneficiary welfare purpose only | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] |
| [TO BE APPROVED] | Dana Dhuafa | donor restricted/designated | Eligible verified beneficiary purpose only | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] |
| [TO BE APPROVED] | Dana Fidyah | restricted; Syariah review required | Approved Fidyah distribution only | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] |
| [TO BE APPROVED] | Dana Wakaf | perpetual restricted or restricted according to ikrar; Syariah/Nazhir review required | Ikrar/purpose governs; principal and benefits remain separated | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] |
| [TO BE APPROVED] | Dana Qurban | restricted; Syariah review required | Direct approved Qurban activities only | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] |
| [TO BE APPROVED] | Dana Pembangunan | donor restricted/designated | Approved project scope only | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] |
| [TO BE APPROVED] | Dana Sosial & Kematian | restricted/designated | Approved social, bereavement, and emergency purpose only | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] |
| [TO BE APPROVED] | Dana Sewa Aula | unrestricted or internally designated; selection required | Facility income/costs under approved policy | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] |
| [TO BE APPROVED] | Dana Titipan/Belum Teridentifikasi | custodial/suspense | Temporary investigation only; not spendable | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] |

For every approved restricted Fund, the completed register must state allowed use, prohibited use, no-negative-balance rule or authorised exception, policy-document reference, matrix version, and effective date. Any missing evaluated rule remains fail-closed; an explicit `prohibited` decision takes precedence.

---

## 6. Fund Policy Matrix

No executable policy rule is created by this template. The manual's Chapter 16 is a policy baseline; the owner must translate only the approved applicable decisions into versioned V2 rows with a policy reference.

| Fund | Transaction Type | Account / Category | Program | Allowed / Prohibited | Reason | Policy Reference | Effective Date | Version | Owner | Approval |
|---|---|---|---|---|---|---|---|---|---|---|
| [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] |

Required controls from the approved architecture/policy:

- `prohibited` overrides a matching `allowed` decision;
- for restricted, perpetual-restricted, custodial, and Syariah Funds, a missing evaluated rule is denied (fail closed);
- each change is versioned, effective dated, attributable to an owner, and prospective only; and
- a donor/ikrar restriction that is stricter than the matrix prevails.

---

## 7. Program Register

Program is a use/cost-centre dimension. **Program ≠ Fund**: it holds no Fund balance, has no FinancialAccount, and cannot replace a restriction decision.

| Program Code | Program Name | Purpose | Fund Compatibility | Active | Effective Date | Owner | Approval |
|---|---|---|---|---|---|---|---|
| [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] |

The policy's program examples (for example Ramadhan, Kajian, Santunan, Qurban, Pembangunan, Operasional, Listrik, and Air) are examples of use/cost-centre dimensions, not approved records in this register.

---

## 8. Category Register

Category is an operating classification and is neither a GL Account nor a Fund.

| Category Code | Category Name | Type | Account Mapping | Program Compatibility | Fund Compatibility | Active | Effective Date | Owner | Approval |
|---|---|---|---|---|---|---|---|---|---|
| [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] |

The eight labels in `KeuanganSeeder` are legacy/UI candidates only. A Category must have an approved transaction type and default posting rule/profile before it can be used in a posted transaction.

---

## 9. Posting Rule Catalogue

No debit/credit mapping is inferred here. Resolver values must point to approved V2 Accounts and dimensions; no hardcoded IDs are permitted.

| Rule Code | Rule Name | Transaction Type | Debit Resolver | Credit Resolver | Amount Resolver | Fund Resolver | Financial Account Resolver | Program Resolver | Effective Date | Version | Owner | Approval |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [TO BE APPROVED] | Receipt | RCV | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | Receiving FinancialAccount | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | Finance Controller | [TO BE APPROVED] |
| [TO BE APPROVED] | Payment | PAY | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | Paying FinancialAccount | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | Finance Controller | [TO BE APPROVED] |
| [TO BE APPROVED] | Treasury Transfer | TRF | Approved destination liquidity Account | Approved source liquidity Account | Approved transfer amount | Preserve source Fund composition | Source and destination FinancialAccounts | Not a transfer determinant | [TO BE APPROVED] | [TO BE APPROVED] | Finance Controller | [TO BE APPROVED] |
| [TO BE APPROVED] | Interfund Transfer | IFT | Approved transfer-in Account | Approved transfer-out Account | Approved transfer amount | Explicit source and destination Funds | NOT APPLICABLE — no custody movement | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | Finance Controller + Policy owner | [TO BE APPROVED] |
| [TO BE APPROVED] | Budget Allocation | ALLOC | NOT APPLICABLE — non-posting control | NOT APPLICABLE — non-posting control | Approved allocation version | Fund / Program / period scope | NOT APPLICABLE | Approved Program where applicable | [TO BE APPROVED] | [TO BE APPROVED] | Finance Controller | [TO BE APPROVED] |
| [TO BE APPROVED] | Fund Realization | REALIZATION | NOT APPLICABLE — actual derives from linked posted Payment | NOT APPLICABLE — no second Journal | Linked posted transaction amount | Derived from linked posted Payment | Derived from linked posted Payment | Derived from linked posted Payment | [TO BE APPROVED] | [TO BE APPROVED] | Finance Controller | [TO BE APPROVED] |
| [TO BE APPROVED] | Opening Balance | OPB | [TO BE APPROVED] | [TO BE APPROVED] | Approved, reconciled opening lines only | Approved mapped Fund | Approved mapped FinancialAccount when applicable | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | Finance Controller + financial authority | [TO BE APPROVED] |
| [TO BE APPROVED] | Adjustment | ADJ | [TO BE APPROVED] | [TO BE APPROVED] | Approved correction amount | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | Finance Controller + financial authority | [TO BE APPROVED] |
| [TO BE APPROVED] | Reversal | REV | Derived from approved original Journal | Derived from approved original Journal | Exact approved original effect | Derived from original | Derived from original | Derived from original | [TO BE APPROVED] | [TO BE APPROVED] | Finance Controller | [TO BE APPROVED] |

`ALLOC` and `REALIZATION` remain non-posting operational controls in this catalogue. They must not be used to create an independent Journal.

---

## 10. Voucher Sequence

The policy requires a unique, immutable, atomic sequence. `count()+1` is prohibited. The recommended conceptual pattern is `<TYPE>-<UNIT>-<YYYYMM>-<SEQUENCE>`; it does not select an actual unit, prefix, or reset rule.

| Sequence Scope | Format | Prefix | Period Component | Accounting Entity Component | Atomic Sequence | Reset Policy | Uniqueness | Owner | Approval |
|---|---|---|---|---|---|---|---|---|---|
| [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | Required | [TO BE APPROVED] | At least entity + transaction type + approved scope | Finance Controller | [TO BE APPROVED] |

The owner must also decide the official issuance stage (approval or posting), void/cancel treatment, gap reporting, and whether journal number equals voucher number. External bank/vendor references remain separate from internal vouchers.

---

## 11. Approval Matrix

No nominal threshold is inferred. The table must be completed from the authorised mandate and must preserve segregation of preparer, verifier, approving authority, treasury/custody, and independent review when required.

| Transaction Type | Threshold | Required Approver | Second Approval | Evidence Required | Exception Rule | Owner | Approval |
|---|---|---|---|---|---|---|---|
| RCV | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | Per approved evidence matrix | [TO BE APPROVED] | Financial authority | [TO BE APPROVED] |
| PAY | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | Per approved evidence matrix | [TO BE APPROVED] | Financial authority | [TO BE APPROVED] |
| TRF | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | Per approved evidence matrix | [TO BE APPROVED] | Financial authority | [TO BE APPROVED] |
| IFT | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | Policy basis, reason, and approval required | [TO BE APPROVED] | Policy owner + financial authority | [TO BE APPROVED] |
| ALLOC | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | Approved allocation/version evidence | [TO BE APPROVED] | Finance Controller | [TO BE APPROVED] |
| REALIZATION | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | Linked posted Payment evidence | [TO BE APPROVED] | Finance Controller | [TO BE APPROVED] |
| OPB | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | Opening evidence pack required | [TO BE APPROVED] | Finance Controller + financial authority | [TO BE APPROVED] |
| ADJ | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | Reason/reference/evidence required | [TO BE APPROVED] | Financial authority | [TO BE APPROVED] |
| REV | [TO BE APPROVED] | [TO BE APPROVED] | [TO BE APPROVED] | Original reference and reason required | [TO BE APPROVED] | Financial authority | [TO BE APPROVED] |

---

## 12. Evidence Policy

The policy baseline accepts PDF plus JPG/JPEG/PNG/WebP/HEIC evidence where appropriate, requires checksum/version lineage, and requires a new version rather than silent replacement after posting. Retention duration, access control detail, and per-type final mandatory/optional status still require owner approval.

| Transaction Type | Evidence Required | Evidence Type | Mandatory / Optional | Retention | Supersession | Hash Requirement | Owner | Approval |
|---|---|---|---|---|---|---|---|---|
| RCV | [TO BE APPROVED] | Bank reference/statement or receipt/count record | [TO BE APPROVED] | [TO BE APPROVED] | Versioned; no silent replacement | [TO BE APPROVED] | Policy owner | [TO BE APPROVED] |
| PAY | [TO BE APPROVED] | Invoice/receipt and payment proof | [TO BE APPROVED] | [TO BE APPROVED] | Versioned; no silent replacement | [TO BE APPROVED] | Policy owner | [TO BE APPROVED] |
| TRF | [TO BE APPROVED] | Outgoing/incoming transfer proof | [TO BE APPROVED] | [TO BE APPROVED] | Versioned; no silent replacement | [TO BE APPROVED] | Policy owner | [TO BE APPROVED] |
| IFT | [TO BE APPROVED] | Policy basis, reason, approval, supporting donor/decision document | [TO BE APPROVED] | [TO BE APPROVED] | Versioned; no silent replacement | [TO BE APPROVED] | Policy owner + Syariah reviewer where relevant | [TO BE APPROVED] |
| ALLOC | [TO BE APPROVED] | Approved allocation/version rationale | [TO BE APPROVED] | [TO BE APPROVED] | Versioned | [TO BE APPROVED] | Finance Controller | [TO BE APPROVED] |
| REALIZATION | [TO BE APPROVED] | Linked posted Payment evidence | [TO BE APPROVED] | [TO BE APPROVED] | Inherits linked-evidence lineage | [TO BE APPROVED] | Finance Controller | [TO BE APPROVED] |
| OPB | [TO BE APPROVED] | Reconciliation worksheet and external balance/cash-count evidence | Mandatory when future scope begins | [TO BE APPROVED] | Versioned; preserve mapping lineage | [TO BE APPROVED] | Finance Controller + financial authority | [TO BE APPROVED] |
| ADJ | [TO BE APPROVED] | Reason, issue/exception reference, and supporting evidence | Mandatory | [TO BE APPROVED] | Versioned; no silent replacement | [TO BE APPROVED] | Financial authority | [TO BE APPROVED] |
| REV | [TO BE APPROVED] | Original reference, reason, and approval evidence | Mandatory | [TO BE APPROVED] | Versioned; original retained | [TO BE APPROVED] | Financial authority | [TO BE APPROVED] |

No retention duration is supplied in this pack. The approved policy must comply with applicable regulation, privacy requirements, and any legal hold.

---

## 13. Opening Balance Prerequisites

**Opening Balance is not authorised or created by this pack.** It remains prohibited until all of the following have evidence and formal approval:

- Accounting Entity and accounting calendar are approved.
- CoA, AccountGroup, required dimension matrix, and applicable FinancialAccounts are approved and active.
- Funds, Fund restrictions, and effective Fund Policy versions are approved; Programs are approved where required.
- Opening mapping is approved/frozen and ambiguous legacy records are retained in an exception register rather than forced into a general Fund.
- Opening evidence exists: bank statement/cash count, Account–Fund–FinancialAccount schedule, trial-balance opening, and Fund/cash tie-out.
- Reconciliation method, tolerance/exception governance, and evidence package are approved.
- Finance Controller, authorised financial authority, and relevant Syariah reviewer approvals are present.

No opening number, mapping value, cutover date, or opening Journal is stated in this document.

---

## 14. Cutover Prerequisites

**Cutover is not authorised or scheduled by this pack.** It remains prohibited until:

- governance decisions in this pack are approved and master data is active;
- opening balance is separately approved and its reconciliation is complete;
- UAT, operational simulation, runbook, production-safety gate, and exception review are complete;
- no material unresolved exception lacks an owner and explicit decision;
- cutover date/time/timezone and stop conditions are formally approved in a cutover charter; and
- V2 is ready to become the sole official ledger, with legacy read-only and no dual-write.

No date is selected here.

---

## 15. Governance Gaps

Prioritisation follows the Blueprint gate dependency, not a new accounting rule: `Critical` blocks G1 master/policy readiness and safe posting; `High` blocks G2 operational controls; `Medium` is a later G3/G4 prerequisite or data-quality control; `Low` is not assigned where no lower-impact gap is evidenced.

| Priority | Gap | Reason / Source | Required Resolution |
|---|---|---|---|
| Critical | GMD-01 — Entity and calendar absent | Every master, period, sequence, and ledger is entity-scoped; Blueprint D-01–D-06 / G1 | Approve GOV-01. |
| Critical | GMD-02 — Signed V2 CoA and dimension mapping absent | CoA/normal balance and dimension requirements are G1 prerequisites; legacy CoA mixes dimensions | Approve GOV-05. |
| Critical | GMD-03 — Verified FinancialAccount register absent | Active FinancialAccount requires custody, liquidity Account mapping, and reconciliation policy | Approve GOV-04. |
| Critical | GMD-04 — Fund register and executable policy matrix absent | Restricted Fund must not be classified/spent by assumption; missing policy is fail-closed | Approve GOV-03. |
| High | GMD-05 — Program/Category/Counterparty registers absent | Required operating attribution cannot be safely resolved; Program must remain distinct from Fund | Approve GOV-06. |
| High | GMD-06 — Posting rules and sequence policy absent | Debit/credit mapping and atomic voucher scope must be approved before posting | Approve GOV-07 and GOV-08. |
| High | GMD-07 — Approval/segregation matrix absent | Mandate and thresholds are needed for approval/exception control | Approve GOV-09. |
| High | GMD-08 — Evidence retention/supersession matrix absent | Material evidence cannot be governed to final retention/access standard | Approve GOV-10. |
| Medium | Legacy candidate terminology/mapping conflict | `Infaq`/`Infak`, `Shodaqoh`/`Shadaqah`, and Fund-like account labels have no canonical mapping decision | Resolve in GOV-05/GOV-03 mapping register. |
| Medium | Future opening/cutover governance is intentionally incomplete | Blueprint D-11–D-16 are later G3/G4 prerequisites; current scope forbids their execution | Keep GOV-11/GOV-12 not applicable until master governance is approved. |
| Low | No evidenced low-priority governance gap | No supported basis exists to downgrade an unresolved decision to low impact. | None recorded. |

---

## 16. Approval Checklist

- [ ] Accounting Entity approved
- [ ] Calendar approved
- [ ] CoA approved
- [ ] Financial Accounts approved
- [ ] Funds approved
- [ ] Fund Policy approved
- [ ] Programs approved
- [ ] Categories approved
- [ ] Posting Rules approved
- [ ] Voucher Sequence approved
- [ ] Approval Matrix approved
- [ ] Evidence Policy approved

Complete each item with an owner, approval reference, effective date, and any applicable Syariah review. Checking an item authorises only its referenced governance decision; it does not authorise a transaction, opening balance, or cutover.

---

## 17. No-Code Gate

This Governance Pack:

- does **not** change the database;
- does **not** create, seed, or activate a master record;
- does **not** create a Journal, JournalLine, LedgerEntry, FinancialTransaction, Receipt, Payment, Transfer, Allocation actual, Realization actual, or Opening Balance;
- does **not** perform historical migration, reconciliation, or cutover;
- does **not** amend Financial Architecture V2, Accounting Policy, migrations, or Posting Engine; and
- does **not** select a cutover date or establish a dual-write route.

## Governance Readiness

**GOVERNANCE READINESS: READY FOR APPROVAL.**

The decision set, accountable owners, required evidence, working templates, and unresolved gaps are ready for the authorised governance process. No decision has been marked `APPROVED` by this document, and the system is **not ready for cutover**. Wait for completed owner approvals before any future master-data onboarding gate.

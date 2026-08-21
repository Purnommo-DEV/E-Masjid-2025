# Financial Phase 3 — STEP 1 Architecture Validation Report

**Status:** BLOCKED — decision required before STEP 2  
**Date:** 8 August 2026  
**Scope:** Entity, relationship, constraint, index, migration-dependency, and test-baseline audit before Financial ERP implementation  
**Baseline authority:** Financial Architecture V2, Accounting Policy & Financial Governance Manual, Financial Phase 2 Implementation Blueprint, and Financial Phase 2 Accounting Foundation Technical Design

---

## Executive decision

**Do not start STEP 2 (Database Foundation) yet.** The current financial implementation is a legacy cash/journal subsystem, not an implementation of the approved Accounting Foundation. Creating migrations directly on top of it without a ratified cutover boundary would create competing sources of balance, violate the immutable-ledger requirement, and risk corrupting audit history.

The required architecture decision is stated in [Section 7](#7-required-decision-before-step-2). No application code, migration, model, repository, or existing financial data was changed during this validation.

## Audit evidence collected

| Evidence | Result |
|---|---|
| Runtime | Laravel 11.48.0, PHP 8.3.31, MySQL 8.4.9; database connection is MySQL. |
| Database inventory | 79 tables; 41 `akun_keuangan`, 10 legacy `jurnal`, 2 `dana_terikat_penerimaan`, 1 `dana_alokasi`; legacy financial data exists. |
| Migration status | One finance migration is pending: `2026_06_26_143745_add_jenis_dana_to_dana_terikat_penerimaan_table`. |
| Actual schema inspection | `jurnal`, `dana_terikat_penerimaan`, `dana_alokasi`, `saldo_awal_details`, and `zakat_transaksi` inspected through Laravel schema tooling. |
| Source inspection | Existing finance migrations, models, repositories, and write controllers reviewed. |
| Test baseline | No financial test files exist. Full test command fails before feature tests complete due to `console_debug()` being redeclared in `routes/console.php`. |

---

# 1. Validation method and pass criteria

The current application was measured against the Phase 2 Accounting Foundation design. A component passes only when it preserves the following hierarchy:

```text
Approved policy and PostingRuleVersion
              ↓
Posting Engine (one atomic/idempotent route)
              ↓
Immutable Journal + JournalLine
              ↓
Posted General Ledger — single source of balances
              ↓
Rebuildable projections, reports, reconciliation, and dashboard
```

The audit evaluated five mandatory dimensions:

1. required entities and ownership;
2. relationships and dimensional model;
3. accounting, fund, and period constraints;
4. indexes and concurrency guarantees;
5. migration dependency, legacy data, and test readiness.

---

# 2. Entity audit

## 2.1 Entity coverage against the Accounting Foundation

| Foundation domain | Required target entities | Current evidence | Status | Finding |
|---|---|---|---|---|
| Accounting scope/calendar | AccountingEntity, AccountingCalendar, AccountingPeriod, ClosingRun | None. | **Missing** | No entity scope, accounting-period state, or close control exists. |
| CoA | AccountGroup, Account, AccountDimensionRule | `akun_keuangan` has code, type, normal balance, self `parent_id`, and flags. | **Partial / incompatible** | No AccountGroup entity/FK, effective dating, dimension rules, accounting entity scope, or post-history immutability. |
| Fund governance | FundType, FundRestriction, Fund, FundPolicyVersion | `dana_terikat_program` combines program with liability/asset accounts; `jenis_dana` migration is pending. | **Missing / conflated** | Fund is not independent of Program; restriction policy and version references do not exist. |
| Treasury | FinancialAccount, BankAccountDetail, CashAccountDetail | Cash/bank are hardcoded through account codes in repositories. | **Missing** | No Rekening master, bank/cash custody data, or Fund × Rekening distribution. |
| Attribution | CostCenter, Program, Category, Counterparty | `dana_terikat_program`, `kategori_keuangans`, free-text donor fields. | **Partial / incompatible** | Program is incorrectly used as Fund; no CostCenter or controlled Counterparty. |
| Transaction intake | FinancialTransaction, TransactionSplit, ApprovalDecision | Separate legacy tables (`penerimaan_pemasukans`, `pengeluaran_umums`, `alokasi_dana`, etc.). | **Missing** | No canonical transaction state, split, source reference, approval, or idempotency contract. |
| Numbering/evidence | DocumentSequence, Voucher, Attachment, AttachmentLink | `jurnal.no_jurnal` is an ordinary indexed string; generic media exists. | **Missing** | No atomic sequence, voucher lifecycle, or typed immutable attachment linkage. |
| Posting/ledger | PostingRule, PostingRuleVersion, PostingAttempt, Journal, JournalLine, LedgerEntry | `jurnal` stores one Account debit/credit row; model points to absent `jurnal_detail`. | **Critical failure** | No Journal header/line model, rule version, posting state, posting sequence, or immutable ledger. |
| Balance/report control | BalanceProjection, TrialBalanceSnapshot | Legacy repositories calculate directly from `transaksis`/opening balance records. | **Missing / contradictory** | Balances are not sourced solely from posted ledger. |
| Opening/cutover | MappingSet, LegacyMapping, OpeningBalanceBatch, OpeningBalanceLine | `saldo_awal_periodes` and `saldo_awal_details` only. | **Partial / incompatible** | No Fund/Rekening dimensions, evidence packet, mapping, batch approval, or cutover control. |
| Reconciliation/exception | Reconciliation, ReconciliationItem, ExceptionCase, ExceptionLog | None. | **Missing** | No reconciliation, difference, escalation, or exception lineage. |
| Audit/reliability | AuditEvent, IdempotencyKey, BusinessRule | Generic `activity_log` package present; no finance contract. | **Missing / partial** | No finance-specific audit correlation, idempotency, policy/rule reference, or event contract. |

## 2.2 Critical entity contradiction: legacy `jurnal`

The actual `jurnal` table has 12 columns and stores `tanggal`, `no_jurnal`, one `akun_id`, `debit`, `kredit`, a polymorphic reference, and creator. The repository creates one `jurnal` record for every debit/credit entry under the same number.

This cannot serve as the target foundation because it lacks:

- distinct Journal header and JournalLine composition;
- unique Journal/Voucher identity and posting sequence;
- Journal status (`Draft`, `Posting`, `Posted`, `Reversed`);
- accounting period, PostingRuleVersion, IdempotencyKey, PostingAttempt, and policy-version references;
- Fund, FinancialAccount, Program, CostCenter, Counterparty, Category, and split dimensions;
- immutability/audit transition controls;
- database balance constraint or one-debit/one-credit line constraint.

The `Jurnal` model declares a `details()` relation to `jurnal_detail`, but neither a migration nor an actual `jurnal_detail` table exists. This is a direct model-to-schema failure and confirms that the legacy table is not a valid target `Journal`/`JournalLine` implementation.

---

# 3. Relationship audit

## 3.1 Required relationship compared with current structure

| Required relationship | Approved design | Current implementation | Severity |
|---|---|---|---|
| Fund ↔ FinancialAccount | Many-to-many through posted JournalLine. | No FinancialAccount; program/account assumptions hardcode cash or bank codes. | **Critical** |
| Fund ↔ Program | Many-to-many through transaction/journal attribution; Program is not balance owner. | `dana_terikat_program` is effectively both Program and Fund/liability mapping. | **Critical** |
| Transaction → TransactionSplit | One-to-many; split total equals transaction total. | No canonical transaction or split. | **Critical** |
| Transaction → Journal | One-to-zero/many; official result created only by Posting Engine. | Multiple legacy tables invoke repository journal creation directly. | **Critical** |
| Journal → JournalLine | One-to-many composed relationship with at least two lines. | Each legacy `jurnal` row is one line; declared detail child table absent. | **Critical** |
| JournalLine → LedgerEntry | One-to-one derived immutable effect. | No LedgerEntry. | **Critical** |
| Fund → FundPolicyVersion | One-to-many effective-dated policy. | No Fund or versioned policy. | **High** |
| FinancialAccount → Bank/Cash detail | One-to-one subtype relationship. | No target entity. | **High** |
| OpeningBalanceBatch → OpeningBalanceLine | One-to-many, mapped/evidenced and posted as OPB. | Legacy opening tables only relate period to Account and optional counter-account. | **High** |
| ExceptionCase → ExceptionLog | One-to-many immutable resolution history. | No target entity. | **High** |
| Voucher → DocumentSequence | Atomic one-to-one issuance at posting. | `no_jurnal` based on monthly row count. | **Critical** |

## 3.2 Current direct-balance paths

The current code contains several direct financial paths outside a canonical Posting Engine:

```text
Legacy receipt/payment/fund tables
          ↓
Repository-specific calculation or Jurnal::create loop
          ↓
`jurnal` rows and/or separate balance calculations
          ↓
Reports calculated from `transaksis`, opening balances, or operational tables
```

For example, `KeuanganRepository` calculates balances from `SaldoAwal` and `Transaksi` rows. `JurnalRepository` creates journal rows directly for receipts, payments, zakat, transfers, opening balances, and corrections. This violates the approved rule that all official balances derive from posted General Ledger and that every financial effect travels through one Posting Engine.

---

# 4. Constraint, index, and migration-dependency audit

## 4.1 Constraint findings

| ID | Finding | Evidence | Impact | Severity |
|---|---|---|---|---|
| C-01 | `jurnal.no_jurnal` is indexed but not unique. | Actual schema lists ordinary `jurnal_no_jurnal_index`. | Duplicate voucher/journal numbers are possible. | **Critical** |
| C-02 | Journal number uses `count() + 1` without atomic sequence or idempotency. | `JurnalRepository::buatJurnal`. | Concurrent posts can generate duplicate numbers. | **Critical** |
| C-03 | No database/engine constraint ensures debit equals credit for a journal. | `jurnal` has line-level debit/kredit only; no header totals. | Unbalanced financial postings can exist. | **Critical** |
| C-04 | No constraint ensures exactly one positive debit/credit side per line. | Actual `jurnal` schema. | Zero/dual-sided/invalid lines possible. | **High** |
| C-05 | No PostingAttempt or IdempotencyKey exists. | Entity/schema absence. | Retry and double-submit can duplicate money movement. | **Critical** |
| C-06 | No Open/Closed accounting period validation exists. | Entity/schema absence. | Back-posting and period mutation cannot be controlled. | **Critical** |
| C-07 | No Fund restriction, Fund policy version, or negative-Fund constraint exists. | Entity/schema absence. | Restricted funds can be used incorrectly. | **Critical** |
| C-08 | Legacy `dana_alokasi` treats allocation as account movement. | Table fields and `JurnalRepository::alokasiDana`. | Budget allocation and interfund transfer are conflated; approved policy is violated. | **Critical** |
| C-09 | `saldo_awal_details` cascades on Account deletion. | Actual schema foreign-key delete action is `cascade`. | Historical opening evidence can disappear if master data is deleted. | **High** |
| C-10 | `dana_terikat_program` cascades to dependent operational records through legacy design. | Migration `create_dana_terikat_all_tables`. | Program/master deletion can remove history. | **High** |
| C-11 | No immutable posted state prevents update/delete of source financial records. | Legacy models are guarded only; no state/constraint/audit contract. | Audit trail cannot be relied upon. | **Critical** |
| C-12 | No typed attachment integrity/version contract exists for posted financial records. | Generic media usage only. | Evidence can be replaced without financial lineage. | **High** |

## 4.2 Index and query readiness

| Target access pattern | Required approved key | Current state | Result |
|---|---|---|---|
| Ledger by Account/date/sequence | Entity + Account + accounting date + posting sequence + line number. | `jurnal` only has date + account; no sequence/line. | **Fail** |
| Ledger by Fund | Entity + Fund + date + sequence. | No Fund dimension in journal. | **Fail** |
| Rekening × Fund balance | Entity + FinancialAccount + Fund + Account + date/sequence. | No FinancialAccount/Fund dimensions. | **Fail** |
| Voucher unique lookup | Entity + unique voucher number. | Non-unique journal number. | **Fail** |
| Idempotent posting lookup | Entity + scope + key/fingerprint. | No entity. | **Fail** |
| Period close lookup | Entity + Period + Journal status. | No Period/status. | **Fail** |
| Exception aging | Entity + status + severity + due date. | No ExceptionCase. | **Fail** |
| Legacy program monthly query | Program + year/month. | Some indexes exist. | **Partial, legacy only** |

## 4.3 Migration dependency findings

| ID | Finding | Consequence | Severity |
|---|---|---|---|
| M-01 | Migration `2026_06_26_143745_add_jenis_dana_to_dana_terikat_penerimaan_table` is pending, but the model/repository reads and writes `jenis_dana`. | Current production-schema path can fail when Dana Terikat receipt is saved. | **Critical** |
| M-02 | Migration `2025_11_24_055015_create_dana_terikat_all_tables` creates four tables but its `down()` drops only nonexistent `dana_terikat_all_tables`. | Rollback cannot safely reverse the migration; rollback leaves tables behind. | **High** |
| M-03 | Existing financial migrations model history as mutable operational rows, not immutable financial facts. | An additive migration that reuses these tables would violate target lifecycle/constraints. | **Critical** |
| M-04 | Existing primary keys are incremental integer IDs; foundation specifies immutable UUID-like identity for target entities. | In-place key conversion has broad FK and live-data risk. | **High** |
| M-05 | Existing data is already present in legacy financial tables and journal rows. | Destructive replacement or direct rename is not authorised and conflicts with cutover requirements. | **Critical** |
| M-06 | No migration dependency path exists for Journal header/line/ledger because current `jurnal` is line-shaped and `jurnal_detail` is absent. | Direct reuse creates ambiguous source of truth and cannot preserve a valid Journal model. | **Critical** |
| M-07 | Several legacy FKs use cascades for accounting-adjacent history. | New target foundation must not depend on cascade delete for posted facts. | **High** |

---

# 5. Current implementation defects relevant to Phase 3

These are observed contradictions in current finance code. They are recorded as evidence only; no repair was made.

| ID | Observed behaviour | Design contradiction | Severity |
|---|---|---|---|
| I-01 | `PenerimaanPemasukanController` calls `terimaDanaTerikat` with arguments in a different order from its declared signature. | A receipt can resolve an ID as amount/program incorrectly; no canonical transaction contract. | **Critical** |
| I-02 | `DanaTerikatPenerimaan` accessor resolves liability accounts by hardcoded account codes. | Fund policy/master data is hardcoded in application code rather than versioned master/rule data. | **High** |
| I-03 | `JurnalRepository` hardcodes liquidity accounts such as `10001`, `10003`, `10005`. | No FinancialAccount master; Rekening and Account are conflated. | **Critical** |
| I-04 | `alokasiDana` credits source and debits destination Account. | Budget allocation is wrongly treated as a financial movement; interfund/transfer/allocation are not separated. | **Critical** |
| I-05 | `buatJurnal` loops direct `Jurnal::create` with no transaction boundary in the method itself, no balance check, and no posting state. | Violates atomic Posting Engine, balance, idempotency, and audit requirements. | **Critical** |
| I-06 | `KeuanganRepository` computes running balance from `transaksis` and mutable opening balances. | Ledger is not single source of truth. | **Critical** |
| I-07 | Legacy Dana Terikat data uses `program_id` to represent fund/accounting identity. | Fund and Program must remain independent. | **Critical** |
| I-08 | Reversal and adjustment lineage is absent; corrections can be created as new operational rows. | Posted facts must be append-only and correction must reference source/reason/evidence. | **High** |
| I-09 | No financial tests exist in `tests/`; test suite fails on duplicate `console_debug()` declaration. | Acceptance criteria AC-001 through AC-120 have no executable verification baseline. | **High** |

---

# 6. Architecture review conclusion

## 6.1 Gate result

| Gate | Required outcome | Result |
|---|---|---|
| Entity audit | All target entities have a migration path with unambiguous ownership. | **Fail** |
| Relationship audit | Fund, Rekening, Program, Account, Transaction, Journal, and Ledger relationships match approved model. | **Fail** |
| Constraint audit | Balance, immutability, voucher, period, restriction, and idempotency controls are enforceable. | **Fail** |
| Index audit | Ledger/report/concurrency access paths are supported. | **Fail** |
| Migration dependency audit | Target can be introduced without replacing/dual-writing legacy truth. | **Fail** |
| Acceptance baseline | Finance tests cover the required accounting scenarios. | **Fail** |

### Conclusion

The existing financial subsystem must be treated as **legacy source and read-only archive after cutover**, as prescribed by the approved architecture. It cannot be incrementally reinterpreted as the approved Phase 2 Foundation.

Implementing STEP 2 by editing or extending legacy `jurnal`, `dana_terikat_program`, `dana_alokasi`, or separate receipt/payment tables as if they were target entities would conflict with the single-source-of-truth design. It would create duplicate accounting paths rather than replace the legacy path through controlled cutover.

---

# 7. Required decision before STEP 2

## Decision request: Target-schema introduction and cutover boundary

Choose one of the following architecture decisions. No implementation should proceed until one is approved.

| Option | Description | Alignment with approved design | Risk |
|---|---|---|---|
| **A — Parallel V2 foundation with governed cutover (recommended)** | Introduce all Foundation entities as new, clearly named V2 target structures with UUID identity. Keep legacy finance tables read-only as migration/archive sources. Migrate only approved opening position and required evidence; activate V2 as the sole official ledger at cutover. | **Fully aligned.** Matches Financial Architecture V2 and Blueprint legacy/cutover rules. | Requires a planned cutover and temporary read-only legacy archive. |
| B — In-place transformation of legacy tables | Alter/rename/reuse current journal, program, receipts, and allocations into target entities. | **Not recommended.** Existing data shapes and semantics conflict with Journal/JournalLine, Fund/Program, and ledger rules. | High data loss, broken FKs, ambiguous history, dual semantics. |
| C — Add V2 tables while continuing to post legacy tables indefinitely | Add new schema but retain both as active balance sources. | **Prohibited by approved design.** | Two sources of truth; reconciliation and audit failure. |

### Recommended decision wording

> Approve Option A. Financial V2 will be implemented as a separate, UUID-based Accounting Foundation. Existing finance tables and legacy journal entries are preserved as read-only migration/audit sources. After a governed opening-balance cutover, only the V2 Posting Engine and posted General Ledger may create official financial balances. No dual write is permitted.

## Mandatory remediation before or within STEP 2 under Option A

1. Approve Option A and a V2 naming/namespace convention.
2. Decide whether the pending legacy `jenis_dana` migration is applied as a contained legacy runtime stabilisation, or whether legacy Dana Terikat entry is frozen before V2 cutover. This is separate from target V2 implementation.
3. Confirm the legacy cutoff date and which tables are migration sources.
4. Approve the Fund master/policy register, CoA, FinancialAccount register, Program/CostCenter register, and opening-balance method from the Blueprint.
5. Create a migration map from each target entity to source/archive treatment; do not map Program balances to Fund by assumption.
6. Establish a non-destructive migration/rehearsal environment before any cutover action.
7. Repair or explicitly quarantine the non-financial test-suite blocker so the new financial test baseline can run.

---

# 8. STEP 1 acceptance verification

| STEP 1 requirement | Verification | Result |
|---|---|---|
| Audit entities | Foundation entity inventory compared to actual schema/models. | Completed; material gaps recorded. |
| Audit relationships | Required target cardinalities compared to legacy relationships. | Completed; critical contradictions recorded. |
| Audit constraints | Journal, Fund, period, voucher, opening, and audit controls inspected. | Completed; critical gaps recorded. |
| Audit indexes | Ledger, Fund, Rekening, Voucher, idempotency, and close paths inspected. | Completed; target paths absent. |
| Audit migration dependency | Status, actual schema, legacy data, rollback/down path, and target compatibility inspected. | Completed; target introduction requires decision. |
| Self review | Findings cross-checked against Architecture V2, Policy Manual, Blueprint, and Foundation. | Completed. |
| Architecture review | Decision gate evaluated. | **Blocked.** |
| Consistency check | Confirmed that continuing with legacy reuse would breach posted-GL single-source-of-truth design. | **Fail; stop required.** |
| Acceptance verification | STEP 1 may advance only if no material design conflict exists. | **Not satisfied.** |

---

## Final status

**STEP 1 is complete and has correctly triggered a stop.** The next permitted action is the user’s decision on Option A, B, or C in Section 7. The recommended and design-compliant decision is **Option A**.

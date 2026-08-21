# Financial Phase 6 — Opening Balance & Migration Rehearsal

Status: PASS — rehearsal and controls only. This document does **not** select a production cutover date or activate Financial V2 as the official production ledger.

## 1. Opening Balance Architecture

`financial_v2_opening_balance_batches` and `financial_v2_opening_balance_lines` represent an approved opening **position**, not reconstructed legacy transactions. A Phase 6 batch flows only through:

`OpeningBalanceService → PostingEngine → Journal → JournalLine → immutable Posted General Ledger`.

`PostingEngine` remains the sole creator of `Journal`, `JournalLine`, and `LedgerEntry`. Controllers only invoke the governed service. The existing Foundation `cutover_date` column is populated by a **rehearsal position date** in this phase; it is not a production cutover decision.

## 2. Opening Balance Workflow

1. Create a Draft batch for one Accounting Entity, Accounting Period, mapping set, rehearsal reference, source package, and position date.
2. Record each source position as a one-sided, exact decimal line with Account and optional Financial Account, Fund, and Program.
3. Attach active source evidence to every line.
4. Reconcile every line and grouped Account / Financial Account / Fund totals against the approved source position.
5. Review, then approve only when debit equals credit, every mapping is approved, all evidence is active, and every difference is zero.
6. Post through the `OPB` Transaction Type and `PostingEngine`.

Posted batches and lines are immutable. Corrections are made by a governed Adjustment or Reversal, never by editing a posted opening position.

## 3. Source Mapping

The V2 mapping framework uses `financial_v2_mapping_sets` and `financial_v2_legacy_mappings` as V2 governance metadata. It does not query, modify, or reinterpret any legacy financial table.

Every source reference is mapped independently per dimension:

- `Account`
- `FinancialAccount` / Rekening, when relevant
- `Fund` / Dana, when relevant
- `Program`, when relevant

This explicitly preserves `Account != FinancialAccount`, `Fund != FinancialAccount`, and `Program != Fund`.

## 4. Mapping Rules

Mapping outcomes are explicit:

- `MAPPED` → confirmed target
- `UNMAPPED` → draft / blocking
- `AMBIGUOUS` → exception / blocking
- `REJECTED` → out-of-scope archive with no target

The same source and dimension cannot be recorded twice in a mapping set. Neither ambiguous nor unmapped source values are selected automatically. A mapping set cannot be approved while it has draft, provisional, or exception mappings.

## 5. Evidence and Audit

Each opening line requires source reference, evidence reference, mapping reference, and an active `opening_balance_line` attachment before batch approval. Attachments and all workflow actions create audit records. Batch review and approval record actor and timestamp. Evidence can only be attached while the batch is Draft.

## 6. Reconciliation

Each line stores the approved source debit/credit, V2 opening debit/credit, exact reconciliation difference, and reconciliation status. Reconciliation uses `DecimalAmount`; no floats are used.

The Opening Balance Summary exposes:

- Account, Financial Account, Fund, optional Program
- source and evidence reference
- V2 opening position, approved source position, status, and difference
- grouped Account / Financial Account / Fund totals
- grand debit, credit, source debit, source credit, and difference

Any non-zero difference blocks review/approval; it is never hidden or posted as an implicit adjustment.

## 7. Migration Rehearsal

Automated rehearsal ran only with:

`APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_DATABASE=mrj_test_db`.

The fixture is an approved, synthetic source position. It performs the required clean process: migrate, seed V2 master context, map, import opening position, attach evidence, reconcile, approve, post, and report. It does not import historical transaction-by-transaction legacy data.

Two independent clean runs produced identical totals. A second post request for the same approved batch returns the same result through the posting idempotency key; it creates neither a second Journal nor additional Ledger entries.

## 8. Migration and Schema Verification

Migration added:

`2026_08_11_000015_add_financial_v2_opening_balance_rehearsal_controls`

Controls added without changing legacy tables:

- batch reviewer / review timestamp
- optional Program foreign key
- source reference and exact source debit/credit
- explicit reconciliation difference/status
- Financial Account, Program, and reconciliation-status indexes

Verification on `mrj_test_db`:

- migration status: Ran
- rollback rehearsal: successful, phase migration only
- re-apply rehearsal: successful
- schema test: Program attribution columns, FK coverage, and all three new indexes verified
- existing V2 opening-line one-sided debit/credit check remains in force

## 9. Idempotency

The batch reference is unique per entity. Posting uses a stable key scoped to the opening batch and a deterministic line fingerprint. Replaying the identical post returns the previously committed posting result. A changed payload for the same key is rejected by the existing idempotency control.

## 10. Reporting Integration

After posting, the existing reporting foundation reads the unchanged single source of truth: Posted V2 General Ledger. Phase 6 tests verify Account balance, Financial Account balance, Fund report, Financial Account report, Trial Balance, and Cash/Bank report data. No reporting query reads legacy balances.

## 11. Closing Integration

Opening posting requires an Open period and a position date inside that period. A Hard Closed period is rejected. There is no period-control bypass.

## 12. User Experience

The V2 `/admin/keuangan-v2/saldo-awal` area uses the language Saldo Awal, Rekening, Dana, Sumber Data, Bukti, Verifikasi, and Selisih. Debit/credit input is confined to a collapsible **Detail Akuntansi** section. The controller delegates all workflow work to the opening-balance and evidence services.

## 13. Test Results

Opening Balance rehearsal suite:

- 21 passed
- 56 assertions
- 0 failed
- 0 skipped

Coverage includes all requested opening balance, mapping, reconciliation, rehearsal, idempotency, reporting, closing, Program-dimension, schema/FK/index, reversal, and adjustment controls.

Full baseline after Phase 6:

- 92 passed
- 400 assertions
- 0 failed
- 0 skipped
- 1 pre-existing risky test

`git diff --check` passed. Canonical writer audit confirms the only V2 `Journal::create`, `JournalLine::create`, and `LedgerEntry::create` calls remain in `PostingEngine`.

## 14. Data Quality

No actual legacy historical data, production source statement, or made-up business opening balance was imported. All numerical positions used in the rehearsal are clearly synthetic test fixtures. The process rejects missing evidence, unbalanced positions, duplicate source mapping, unmapped/ambiguous mandatory dimensions, non-zero differences, and unsafe period states.

## 15. Governance Gaps and Risks

Still required before real cutover:

- approved source dataset and evidence package from the responsible business owners
- formal Account / Financial Account / Fund / Program mapping sign-off
- source-to-V2 reconciliation review and migration rehearsal evidence retained for the real dataset
- approved correction and exception handling plan for any real source differences
- explicit cutover date decision by authorized governance

No cutover date has been set by this implementation.

## 16. Cutover Prerequisites

Before UAT + Cutover Readiness can pass, governance must approve real source position, evidence, mapping, zero-difference reconciliation, cutover date, operational roles, and rollback/contingency procedures. Production migration, V2 activation as official ledger, legacy disablement, historical migration, and dual-write remain out of scope and have not occurred.

## Result

`OPENING BALANCE & MIGRATION REHEARSAL = PASS`

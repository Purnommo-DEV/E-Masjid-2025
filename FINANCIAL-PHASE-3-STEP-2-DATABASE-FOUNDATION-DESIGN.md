# Financial Phase 3 — STEP 2 Database Foundation Design Gate

**Status:** Approved design review for implementation  
**Date:** 8 August 2026  
**Architecture decision:** Option A — Parallel V2 Foundation with Governed Cutover  
**Scope:** V2 data schema only; no legacy-table reuse, mutation, or data cutover in this step

---

## Decision baseline

This document implements the approved Option A decision:

> Financial V2 is a separate UUID-based Accounting Foundation. Existing finance tables remain legacy migration sources and historical archives. V2 becomes the sole official financial ledger only after a separately approved governed cutover. No dual-write is permitted.

`cutover_date` is deliberately not set in this document or migration. It remains a governance decision and will be supplied only by a future approved Cutover Charter.

---

# A. Final V2 Entity-to-Table Mapping

| Foundation entity | V2 table | Classification | Notes |
|---|---|---|---|
| AccountingEntity | `financial_v2_accounting_entities` | Authoritative master | Independent accounting boundary; no legacy FK. |
| AccountingCalendar / AccountingPeriod / ClosingRun | `financial_v2_accounting_calendars`, `financial_v2_accounting_periods`, `financial_v2_closing_runs` | Master/control | Period lifecycle is reserved now; closing workflow activates later. |
| AccountGroup / Account / AccountDimensionRule | `financial_v2_account_groups`, `financial_v2_accounts`, `financial_v2_account_dimension_rules` | Authoritative master | V2 CoA is separate from `akun_keuangan`. |
| FundType / FundRestriction / Fund / FundPolicyVersion | `financial_v2_fund_types`, `financial_v2_fund_restrictions`, `financial_v2_funds`, `financial_v2_fund_policy_versions` | Authoritative master | Never maps Program as Fund by assumption. |
| Category / ReasonCode / TransactionType / BusinessRule | `financial_v2_categories`, `financial_v2_reason_codes`, `financial_v2_transaction_types`, `financial_v2_business_rules` | Authoritative master | Controlled rule/reference data. |
| FinancialAccount / BankAccountDetail / CashAccountDetail | `financial_v2_financial_accounts`, `financial_v2_bank_account_details`, `financial_v2_cash_account_details` | Authoritative master | Bank/cash physical custody separated from Fund and GL Account. |
| CostCenter / Program / Counterparty | `financial_v2_cost_centers`, `financial_v2_programs`, `financial_v2_counterparties` | Authoritative master | Program has no stored cash/Fund balance. |
| DocumentSequence / Voucher | `financial_v2_document_sequences`, `financial_v2_vouchers` | Authoritative control/fact | Voucher is separate from Journal. |
| FinancialTransaction / TransactionSplit / ApprovalDecision | `financial_v2_transactions`, `financial_v2_transaction_splits`, `financial_v2_approval_decisions` | Authoritative business fact | Transaction affects no balance until posted. |
| Attachment / AttachmentLink | `financial_v2_attachments`, `financial_v2_attachment_links` | Authoritative evidence fact | Typed polymorphic target avoids legacy media coupling. |
| PostingRule / PostingRuleVersion | `financial_v2_posting_rules`, `financial_v2_posting_rule_versions` | Authoritative master | Version references on posted Journal. |
| IdempotencyKey / PostingAttempt | `financial_v2_idempotency_keys`, `financial_v2_posting_attempts` | Authoritative control fact | Supports safe retry/recovery. |
| Journal / JournalLine | `financial_v2_journals`, `financial_v2_journal_lines` | Authoritative accounting fact | New V2 structures; not `jurnal` or `jurnal_detail`. |
| LedgerEntry | `financial_v2_ledger_entries` | Derived immutable materialisation | One-to-one with posted JournalLine; no independent input. |
| BalanceProjection / TrialBalanceSnapshot | `financial_v2_balance_projections`, `financial_v2_trial_balance_snapshots` | Derived/rebuildable | Never an official balance source. |
| MappingSet / LegacyMapping | `financial_v2_mapping_sets`, `financial_v2_legacy_mappings` | Cutover control | Holds source references/rationale only; no legacy FK required. |
| OpeningBalanceBatch / OpeningBalanceLine | `financial_v2_opening_balance_batches`, `financial_v2_opening_balance_lines` | Authoritative accounting fact | Only future governed opening posting uses this. |
| Reconciliation / ReconciliationItem | `financial_v2_reconciliations`, `financial_v2_reconciliation_items` | Reserved Phase 3 control | Schema-only until reconciliation feature is implemented. |
| ExceptionCase / ExceptionLog | `financial_v2_exception_cases`, `financial_v2_exception_logs` | Authoritative control fact | Exception cannot create an accounting effect itself. |
| AuditEvent | `financial_v2_audit_events` | Authoritative control fact | Immutable technical/financial trail. |

# B. V2 Naming / Namespace Convention

| Area | Convention |
|---|---|
| Table namespace | All V2 tables start with `financial_v2_` and use plural snake_case. No V2 migration alters `jurnal`, `akun_keuangan`, `dana_*`, `saldo_*`, `transaksis`, or other legacy finance table. |
| Primary key | `id` is a UUID stored as a 36-character UUID-compatible value. Human codes and voucher numbers are separate. |
| Foreign key | `<singular>_id`; all V2 financial FKs use the same UUID type as target key. User actor fields use explicit `*_user_id` and are optional foreign keys to existing identity master `users`. |
| Date/time | `business_date`, `accounting_date`, `effective_from`, `effective_to`; operational timestamps use `*_at`. |
| Amount | Decimal(19,2) with nonnegative fields unless an explicitly signed derived amount is required. |
| State | Lowercase snake_case enum values. Lifecycle status is never inferred from `deleted_at`. |
| Audit | `created_at`, `updated_at`, `created_by_user_id`, `updated_by_user_id` on mutable records. Posted/control facts add explicit posting/event actor/time fields. |
| Deletion | No soft-delete on posted accounting facts, vouchers, ledger, audit, mappings, or evidence links. Masters retire via status/effective date. |

# C. Migration Dependency Graph

```text
M1  AccountingEntity, Calendar, Period, CoA, Fund, rule/reference masters
 │
 ├── M2 Treasury, CostCenter, Program, Counterparty
 ├── M3 PostingRule, PostingRuleVersion, DocumentSequence
 │
 ├── M4 Transaction, Split, Approval, Attachment, Idempotency, PostingAttempt
 │       │
 │       └── M5 Journal, JournalLine, AuditEvent, Voucher result linkage
 │                 │
 │                 └── M6 LedgerEntry, BalanceProjection, TrialBalanceSnapshot
 │
 └── M7 MappingSet, LegacyMapping, OpeningBalanceBatch, OpeningBalanceLine
          │
          └── M8 ClosingRun, Reconciliation, ExceptionCase, ExceptionLog
```

Dependencies are one way. Journal and JournalLine depend on an approved transaction, rule version, period, and posting attempt. PostingAttempt does not require a Journal FK until the journal exists; its `journal_id` linkage is added after the journal migration, avoiding a cyclic creation dependency.

# D. Legacy-to-V2 Migration Boundary

| Legacy concern | Boundary decision |
|---|---|
| Legacy financial tables | Read-only source/archive only. No V2 FK points to legacy `jurnal`, `akun_keuangan`, `dana_*`, `saldo_*`, or `transaksis`. |
| Existing users | V2 may use optional actor FKs to `users`, because users are identity records, not legacy financial facts. This does not create a balance dependency. |
| Legacy identifiers | Stored only as immutable string `legacy_record_ref` plus source-system name in MappingSet/LegacyMapping and audit metadata. |
| Legacy receipt/payment history | Not automatically replicated as V2 posted transaction history. It is archived; approved cutover establishes opening position later. |
| `jenis_dana` pending migration | Quarantined as legacy issue. Foundation V2 neither reads nor writes it. |
| Program/Fund mapping | Must be approved record by record or by documented mapping policy. No default Program = Fund conversion exists. |

# E. Cutover Boundary

```text
Before approved cutover
  Legacy finance: current operational source
  V2 schema: empty/configured/rehearsal only
  Official balance: legacy only

At approved cutover (future decision)
  Freeze legacy transactions at approved time
  Reconcile bank/cash and Fund mapping evidence
  Post approved OpeningBalanceBatch through V2 Posting Engine
  Verify V2 trial balance and Rekening × Fund tie-out

After signed cutover
  Legacy finance: read-only historical archive
  V2 Posting Engine + posted V2 General Ledger: sole official balance source
  Dual-write: prohibited
```

No timestamp, date, or migration in STEP 2 performs the middle section. A future opening balance/cutover process requires rehearsal, reconciliation evidence, approval, and separate go/no-go authority.

# F. Data Preservation Strategy

1. V2 migrations are additive: they create only `financial_v2_*` objects.
2. No legacy rows are updated, deleted, renamed, copied, or reclassified in STEP 2.
3. Migration mapping captures legacy source identifiers and rationale without destructive transformation.
4. Opening balance will use MappingSet, LegacyMapping, OpeningBalanceBatch, and OpeningBalanceLine; it will not edit legacy records.
5. Rollback rehearsal is allowed only for a disposable, isolated schema/database containing V2 objects. It must never run against the shared production database while a cutover has occurred.
6. Once V2 contains a posted fact, migration rollback is operationally prohibited; forward-only corrective migrations are required.

# G. UUID Strategy

| Decision | Strategy |
|---|---|
| Identifier | All V2 primary keys use UUID-compatible 36-character values. |
| Generation | Generated by the application/domain layer at entity creation; migrations only declare storage and FK compatibility. |
| Immutability | IDs never change, including when a code, name, or policy version changes. |
| Legacy identity | Preserved as text in mapping/audit references; never converted into V2 primary keys. |
| Human-facing numbers | CoA code, Fund code, Voucher number, and external reference remain separate from `id`. |

# H. Foreign Key Strategy

- Every non-polymorphic V2 relationship receives an explicit FK and supporting index.
- Cross-AccountingEntity integrity is additionally enforced in the domain/Posting Engine; ordinary FKs cannot prove same-entity scope across all dimensions.
- Posted financial fact FKs use restrictive deletion. Master records retire by status, not deletion.
- Self-referential master hierarchies use nullable restrictive links and cycle detection at the application/domain layer.
- Typed polymorphic targets (`AttachmentLink`, `AuditEvent`, `ExceptionCase`, `LegacyMapping`) retain target type plus UUID/string identity and are validated by the domain layer; an SQL FK cannot span multiple target tables.
- `created_by_user_id`, `updated_by_user_id`, and approval/actor identifiers may reference existing `users` with `SET NULL` on deletion to preserve facts if an identity is removed.

# I. Index Strategy

| Access path | Index/key |
|---|---|
| Entity-scoped master lookup | Unique `(accounting_entity_id, code)` or `(accounting_entity_id, name)` as applicable. |
| Effective rule/policy lookup | Parent key plus `(effective_from, effective_to)`; overlap validation later in service/domain layer. |
| Ledger by Account | `(accounting_entity_id, account_id, accounting_date, posting_sequence, line_no)`. |
| Ledger by Fund | `(accounting_entity_id, fund_id, accounting_date, posting_sequence)`. |
| Rekening position | `(accounting_entity_id, financial_account_id, accounting_date, posting_sequence)`. |
| Program reporting | `(accounting_entity_id, program_id, accounting_date, posting_sequence)`. |
| Voucher inquiry | Unique `(accounting_entity_id, voucher_number)`. |
| Idempotent posting | Unique `(accounting_entity_id, scope_name, key_value)`. |
| Transaction source | Unique `(accounting_entity_id, transaction_type_id, source_reference)` only when source reference is present; implemented as nullable unique key. |
| Exception ageing | `(accounting_entity_id, status, severity, due_date)`. |
| Migration mapping | Unique `(mapping_set_id, legacy_record_ref)`. |

# J. Immutable Financial Fact Strategy

| Fact | Schema posture | Operational enforcement to be implemented later |
|---|---|---|
| Posted Journal / JournalLine | No soft delete; restrictive FKs; status and post sequence immutable by design. | Posting Engine is sole writer; direct update/delete paths prohibited and tested. |
| LedgerEntry | Derived, one-to-one source JournalLine; no editable source form. | Generated only after committed posting; rebuildable. |
| Voucher | Unique number; no soft delete; void has reason fields. | Sequence allocation and issuance are atomic with posting. |
| Opening balance | Batch/line lifecycle; no deletion after approval/posting. | Posted through OPB rule with evidence and sign-off. |
| AuditEvent / ExceptionLog | Append-only tables, no soft delete. | Emit controlled events with correlation IDs. |
| BalanceProjection | Derived/stale/current status only; not a posting target. | Rebuild/tie-out process verifies derived values. |

## Review conclusion

The A–J design is consistent with all four approved baseline documents and the Option A approval. It creates a separate Foundation without legacy mutation, no dual write, no implicit Program-to-Fund mapping, and no unilateral cutover date. STEP 2 migration generation may proceed.

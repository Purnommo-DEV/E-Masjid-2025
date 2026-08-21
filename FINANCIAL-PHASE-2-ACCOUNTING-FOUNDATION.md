# Financial Phase 2 — Accounting Foundation Technical Design

**Status:** Draft technical baseline — requires architecture and policy sign-off before implementation  
**Version:** 1.0  
**Date:** 5 August 2026  
**Scope:** Technical design for the Accounting Foundation of the Masjid financial module  
**Explicit exclusions:** Implementation code, database migration scripts, SQL, framework classes, repositories, services, controllers, APIs, and user-interface design

---

## Document authority and usage

This document is the technical design baseline for Phase 2. It must be read together with:

1. [Financial Architecture V2](FINANCIAL-ARCHITECTURE-V2.md) — business and accounting architecture;
2. [Accounting Policy & Financial Governance Manual](ACCOUNTING-POLICY-FINANCIAL-GOVERNANCE-MANUAL.md) — policy and financial governance;
3. [Financial Phase 2 Implementation Blueprint](FINANCIAL-PHASE-2-IMPLEMENTATION-BLUEPRINT.md) — delivery, cutover, and assurance plan.

The three preceding documents govern business decisions. This document turns those decisions into a durable technical design without changing them. Where a technical choice needs an explicit decision before build, it is marked **Architecture Recommendation (AR)**. An AR must be ratified, replaced, or recorded as deferred before the dependent work starts.

### Design invariants

- Posted General Ledger is the only authoritative source of financial balances.
- A Financial Account (Rekening), Fund, Program, Cost Center, and GL Account are independent dimensions and must never be substituted for each other.
- Posted journals and ledger entries are append-only. Corrections use Reversal or Adjustment with traceability.
- A business event can create at most one official posting for one idempotency scope.
- Every official posting is balanced, atomic, auditable, and associated with a valid accounting period.
- Transfers of cash location preserve Fund ownership. Budget allocation is not a financial posting.
- Balance projections and report summaries are rebuildable derived data, never a second source of truth.
- Legacy data is a migration source and audit archive, not a parallel balance engine after cutover.

---

# BAB 1 — Accounting Core

## 1.1 Core concepts

| Concept | Technical meaning | Authoritative record | Key rule |
|---|---|---|---|
| Chart of Accounts (CoA) | Controlled classification of assets, liabilities, net assets/equity, revenues, expenses, transfers, and control accounts. | Account and AccountGroup masters. | An Account has one normal balance and explicit dimensional requirements. |
| General Ledger (GL) | Immutable, ordered set of posted journal line effects used to calculate official balances. | Posted JournalLine / logical LedgerEntry. | No transaction changes an official balance outside GL posting. |
| Journal | Balanced accounting representation of one posting event. | Journal header and JournalLine set. | A Journal must have at least two lines and total debit equals total credit. |
| Journal Line | Atomic debit or credit effect, with Account and required accounting dimensions. | JournalLine. | A line carries the dimensions needed to explain the effect. |
| Posting | Irreversible business operation that validates and turns an approved source transaction into a posted Journal and Ledger effects. | PostingAttempt, Journal, AuditEvent. | Posting is all-or-nothing and idempotent. |
| Posting Engine | Domain component that coordinates validation, number allocation, rule resolution, journal creation, GL publication, audit, and recovery. | PostingAttempt and audit lineage. | It is the only permitted route to create official postings. |
| Ledger | Inquiry view of posted JournalLine in accounting date and posting sequence order. | Logical view or immutable materialization of JournalLine. | It must not be independently editable. |
| Running Balance | Cumulative amount for a selected Account and dimensional filter at an ordered point in the ledger. | Derived from LedgerEntry / projection. | Ordering uses accounting date plus immutable posting sequence, not only timestamp. |
| Opening Balance | Approved opening position at a cutover or fiscal start. | OpeningBalanceBatch, OpeningBalanceLine, posted OPB Journal. | It is not a substitute for unexplained corrections. |
| Adjustment | Transparent correction of an accounting position not adequately resolved by reversal. | ADJ transaction, Journal, ExceptionCase. | Requires reason, evidence, and approval policy. |
| Reversal | Exact, traceable opposite of a prior posted Journal or allowed part thereof. | REV transaction and related Journal. | It never edits or deletes the original Journal. |
| Trial Balance | Account-level debit, credit, and balance summary for a period/filter. | Derived from posted GL. | Total debit must equal total credit. |
| Closing | Controlled confirmation that a period is prepared for close and no further ordinary posting is allowed. | ClosingRun and Period status. | Full operational closing workflow is Phase 3; Phase 2 enforces period eligibility. |

## 1.2 Relationship of accounting concepts

```text
Policy + CoA + Fund rules + period rules
                    |
                    v
          Approved Financial Transaction
                    |
                    v
              Posting Engine
      +-------------+--------------+
      |                            |
      v                            v
Voucher / Audit trail      Balanced Journal + Lines
                                     |
                                     v
                   Immutable General Ledger effects
                                     |
            +------------------------+-----------------------+
            |                        |                       |
            v                        v                       v
   Account / Trial Balance   Rekening balance          Fund balance
   Program/Cost Center view  (cash location)           (resource purpose)
```

## 1.3 Accounting dimensions on a journal line

| Dimension | Required when | Purpose | Not a substitute for |
|---|---|---|---|
| Account | Always. | Identifies accounting classification and normal balance. | Category or transaction type. |
| Fund | When required by Account, transaction type, or policy. Mandatory for liquidity and restricted-accounting effects. | Identifies ownership/restriction of resources. | Rekening or Program. |
| Financial Account / Rekening | For cash, bank, e-wallet, petty cash, or other liquidity Account. | Identifies physical location/custody of liquid asset. | Fund. |
| Program | When policy requires attribution to activity. | Identifies activity/use of funds. | Fund ownership. |
| Cost Center | When management accounting requires organisational attribution. | Identifies responsible operating unit. | Program or Account. |
| Counterparty | When receipt, payment, payable/receivable, or policy requires it. | Identifies donor, supplier, beneficiary, or transfer institution. | Fund. |
| Category | When an operational category is selected. | Assists rule selection and reporting. | CoA. |

## 1.4 Architecture Recommendations

| ID | Recommendation | Rationale | Decision required before |
|---|---|---|---|
| AR-01 | Treat `LedgerEntry` as a logical append-only representation of posted `JournalLine`, not a separately editable financial source. A physical immutable materialization may be used only as a derived copy with a one-to-one lineage. | Prevents two competing financial truths while meeting the need for high-performance ledger inquiry. | Physical persistence design. |
| AR-02 | Use a generic `FinancialAccount` master with BankAccountDetail and CashAccountDetail extensions. | Keeps reporting and journal dimensions uniform while retaining bank/cash-specific attributes. | Master-data implementation. |
| AR-03 | Use UUID-like immutable identifiers for entity identity and human-readable codes/numbers separately. | Internal identity must remain stable when operational names or numbering conventions change. | Database design. |
| AR-04 | Use accounting-date plus monotonic posting sequence as the canonical ledger order. | Time of entry alone cannot give stable running balances for back-dated eligible transactions. | Posting Engine implementation. |
| AR-05 | Make balance projections explicitly non-authoritative and rebuildable. | Supports performance without weakening auditability. | Reporting/performance implementation. |
| AR-06 | Version PostingRule and Fund policy references on every official posting. | Historical outcomes remain explainable when policy/rules change. | Rule catalogue implementation. |
| AR-07 | Reserve voucher number only within the atomic posting attempt, with a separately explainable void/gap state. | Prevents duplicates and unexplained sequence gaps under concurrency. | Numbering implementation. |

---

# BAB 2 — Physical Data Model

## 2.1 Model boundary

The following is a physical-data-model specification at entity and field level. It intentionally does not prescribe tables, scripts, storage engine syntax, or framework implementation. “Physical” here means that each entity has stable identity, data type, lifecycle, constraints, ownership, and relationship rules that can be implemented consistently.

## 2.2 Entity domains

| Domain | Entities |
|---|---|
| Organisation and calendar | AccountingEntity, AccountingCalendar, AccountingPeriod, ClosingRun |
| Accounting master | AccountGroup, Account, AccountDimensionRule, FundType, FundRestriction, Fund, FundPolicyVersion, Category, ReasonCode, TransactionType |
| Treasury and attribution master | FinancialAccount, BankAccountDetail, CashAccountDetail, CostCenter, Program, Counterparty |
| Controlled numbering and documents | DocumentSequence, Voucher, Attachment, AttachmentLink |
| Business transaction and approvals | FinancialTransaction, TransactionSplit, ApprovalDecision |
| Posting and ledger | PostingRule, PostingRuleVersion, PostingAttempt, Journal, JournalLine, LedgerEntry, BalanceProjection, TrialBalanceSnapshot |
| Migration and opening | MappingSet, LegacyMapping, OpeningBalanceBatch, OpeningBalanceLine |
| Reconciliation and exception | Reconciliation, ReconciliationItem, ExceptionCase, ExceptionLog |
| Audit and reliability | AuditEvent, IdempotencyKey |
| Policy registry | BusinessRule |

## 2.3 Source-of-truth classification

| Classification | Entities | Rule |
|---|---|---|
| Authoritative master | Account, Fund, FinancialAccount, Program, Category, Period, PostingRuleVersion, BusinessRule. | Changes are controlled, effective-dated where material, and audited. |
| Authoritative business fact | FinancialTransaction, TransactionSplit, Voucher, AttachmentLink, ApprovalDecision. | A posted source fact is immutable in financial meaning. |
| Authoritative accounting fact | Journal, JournalLine, OpeningBalanceBatch/Line, ClosingRun. | Posted Journal and line data are append-only. |
| Authoritative control fact | PostingAttempt, IdempotencyKey, ExceptionCase/Log, AuditEvent. | Retained for traceability and recovery. |
| Derived/rebuildable | LedgerEntry, BalanceProjection, TrialBalanceSnapshot. | May be regenerated only from authoritative accounting facts. |
| Phase 3 activation | Reconciliation and ReconciliationItem. | Entity contract is reserved now; operational workflow is activated later. |

## 2.4 High-level ownership and dependency model

```text
AccountingEntity
  ├─ AccountingCalendar ──< AccountingPeriod ──< ClosingRun
  ├─ AccountGroup ──< Account ──< AccountDimensionRule
  ├─ FundType ──< FundRestriction ──< Fund ──< FundPolicyVersion
  ├─ FinancialAccount ──< BankAccountDetail | CashAccountDetail
  ├─ CostCenter ──< Program
  ├─ FinancialTransaction ──< TransactionSplit ──< ApprovalDecision
  │       ├─< AttachmentLink >─ Attachment
  │       ├─ Voucher
  │       ├─ PostingAttempt ──< Journal ──< JournalLine ──> LedgerEntry*
  │       └─< AuditEvent
  ├─ MappingSet ──< LegacyMapping ──< OpeningBalanceLine
  ├─ OpeningBalanceBatch ──< OpeningBalanceLine
  ├─ Reconciliation ──< ReconciliationItem
  └─ ExceptionCase ──< ExceptionLog

* LedgerEntry is a logical or derived one-to-one representation of a posted JournalLine.
```

## 2.5 Cardinality summary

| Parent | Child | Cardinality | Meaning |
|---|---|---:|---|
| AccountingEntity | AccountingPeriod | 1 : many | One entity has many financial periods. |
| AccountGroup | Account | 1 : many | An Account belongs to one AccountGroup. |
| Account | AccountDimensionRule | 1 : many | An Account may require several dimensions. |
| FundType | Fund | 1 : many | A Fund has one type. |
| FundRestriction | Fund | 1 : many | A Fund has one current restriction classification. |
| Fund | FundPolicyVersion | 1 : many | Policy changes must preserve historical versions. |
| FinancialAccount | BankAccountDetail/CashAccountDetail | 1 : 0..1 each | Exactly one relevant subtype detail is expected. |
| CostCenter | Program | 1 : many | A Program may be attributed to one Cost Center. |
| FinancialTransaction | TransactionSplit | 1 : many | A transaction has one or more accounting allocations. |
| FinancialTransaction | Journal | 1 : 0..many | Usually one official Journal; more than one only for explicit linked correction events. |
| Journal | JournalLine | 1 : many | A posted Journal has at least two lines. |
| JournalLine | LedgerEntry | 1 : 0..1 | Derived only after posting. |
| FinancialTransaction | Voucher | 1 : 0..1 | Voucher issued when an official posting is completed. |
| FinancialTransaction | AttachmentLink | 1 : many | Multiple evidence files can support a transaction. |
| ExceptionCase | ExceptionLog | 1 : many | An exception is resolved through an immutable event history. |
| OpeningBalanceBatch | OpeningBalanceLine | 1 : many | Batch groups the approved opening positions. |

---

# BAB 3 — Entity Specification

For all entities, `Purpose` explains why it exists; `Business responsibility` identifies the accountable financial function; `Lifecycle` gives permitted state progression; `Dependencies` lists records that must exist first; and `Rules` gives non-negotiable behaviour.

## 3.1 Organisation, calendar, and master entities

| Entity | Purpose and description | Business responsibility | Lifecycle | Dependencies | Core business rules |
|---|---|---|---|---|---|
| AccountingEntity | Boundary for one Masjid/legal accounting unit, its policies, periods, and ledger. | Policy owner. | Active → Suspended → Archived. | None. | Financial facts never move between AccountingEntity boundaries. |
| AccountingCalendar | Defines fiscal calendar convention for an AccountingEntity. | Finance Controller. | Draft → Active → Retired. | AccountingEntity. | One active calendar applies to a date; historical periods retain their calendar context. |
| AccountingPeriod | Defines posting eligibility for a fiscal month/period. | Finance Controller. | Future → Open → SoftClosed → HardClosed → Reopened (controlled). | AccountingCalendar. | Normal posting requires Open; Phase 2 enforces Open/closed eligibility. |
| ClosingRun | Records an attempted or completed period closing process. | Finance Controller. | Planned → InProgress → Blocked/Completed → Reopened. | AccountingPeriod. | A completed close requires all mandatory controls/evidence. |
| AccountGroup | Hierarchical reporting group for Accounts. | Finance Controller. | Active → Inactive. | AccountingEntity. | Group hierarchy cannot form a cycle; group is not a posting target. |
| Account | GL classification with normal balance and reporting meaning. | Finance Controller. | Draft → Active → Inactive → Retired. | AccountGroup. | Posted Account cannot be deleted; inactive Account cannot receive future postings. |
| AccountDimensionRule | Declares which dimensions an Account requires, permits, or forbids. | Finance Controller. | Draft → Active → Superseded. | Account. | A rule change is effective-dated and cannot invalidate historic lines. |
| FundType | Classifies Funds, such as unrestricted, restricted, syariah, or custodial type. | Policy owner. | Active → Inactive. | AccountingEntity. | FundType guides policy but does not itself determine a journal. |
| FundRestriction | Controlled restriction classification and governance severity. | Policy owner / syariah adviser as relevant. | Draft → Active → Retired. | FundType. | Restricted classifications require explicit policy basis. |
| Fund | Resource ownership/purpose unit with separately accountable balance. | Policy owner. | Draft → Active → Suspended → Closed. | FundType, FundRestriction. | Fund is never a child of a FinancialAccount or Program. |
| FundPolicyVersion | Effective-dated policy matrix/reference for a Fund. | Policy owner. | Draft → Effective → Superseded. | Fund. | Posted line retains policy version used at posting. |
| Category | Operational classification used for intake, rule selection, and reporting. | Finance Controller. | Active → Inactive. | AccountingEntity; optional TransactionType. | Category is not a substitute for Account or Fund. |
| ReasonCode | Controlled rationale for reversal, adjustment, exception, void, or override. | Finance Controller. | Active → Inactive. | AccountingEntity. | Mandatory where correction/exception policy demands it. |
| TransactionType | Controlled type such as RCV, PAY, TRF, OPB, REV, ADJ, CLS. | Finance Controller. | Draft → Active → Retired. | AccountingEntity. | Type determines eligible PostingRule versions and required data. |

## 3.2 Treasury, attribution, and counterparty entities

| Entity | Purpose and description | Business responsibility | Lifecycle | Dependencies | Core business rules |
|---|---|---|---|---|---|
| FinancialAccount | Represents a physical/custodial liquidity location: bank, cash, petty cash, or approved e-wallet. | Treasury custodian. | Draft → Active → Suspended → Closed. | AccountingEntity; linked liquidity Account. | One FinancialAccount can hold many Funds; its balance is ledger-derived. |
| BankAccountDetail | Bank-specific attributes of a FinancialAccount. | Treasury custodian. | Active with parent → Closed. | FinancialAccount type Bank. | Account identifier is protected; closure date forbids future posting. |
| CashAccountDetail | Cash custody attributes of a FinancialAccount. | Cash custodian. | Active with parent → Closed. | FinancialAccount type Cash/PettyCash. | Custodian and cash-count requirement are mandatory. |
| CostCenter | Management accounting responsibility unit. | Finance Controller. | Draft → Active → Inactive. | AccountingEntity. | It does not own cash or Fund balance. |
| Program | Activity/cost-centre dimension for use of resources. | Program owner + Finance Controller. | Draft → Active → Suspended → Closed. | CostCenter optional. | Program never owns a FinancialAccount or creates a Fund balance. |
| Counterparty | Donor, supplier, beneficiary, bank, or other external/internal party reference. | Finance operations. | Active → Inactive/Archived. | AccountingEntity. | Personal data is minimised; historic financial references are preserved. |

## 3.3 Transaction, document, and control entities

| Entity | Purpose and description | Business responsibility | Lifecycle | Dependencies | Core business rules |
|---|---|---|---|---|---|
| FinancialTransaction | Business fact submitted for accounting treatment. | Transaction preparer and verifier. | Draft → Submitted → Verified → Approved → Posting → Posted; or Rejected/Cancelled; Posted may be Reversed. | TransactionType, Period, required masters. | It has no official balance impact until Posted. |
| TransactionSplit | Allocation of a FinancialTransaction amount across Fund/Account/Program/other permitted dimensions. | Transaction preparer, verified by finance. | Draft with parent → Locked at Posted. | FinancialTransaction; required masters. | Sum of active splits equals transaction total; dimensions must obey rules. |
| ApprovalDecision | Immutable decision event at an approval step. | Designated financial authority. | Pending → Approved/Rejected/Expired/Superseded. | FinancialTransaction. | An approver cannot approve their own restricted action where segregation policy forbids it. |
| DocumentSequence | Controlled numbering counter and scope for voucher/document families. | Finance Controller. | Draft → Active → Retired. | AccountingEntity; TransactionType. | Allocation is atomic; sequence scope is unique. |
| Voucher | Human-readable accounting document identity issued for a posted transaction. | Posting custodian. | Reserved → Issued → Voided/Referenced. | DocumentSequence, FinancialTransaction. | Voucher uniqueness is mandatory; issued voucher is never reassigned. |
| Attachment | Metadata and integrity reference for stored evidence content. | Transaction preparer / document custodian. | PendingScan → Active → Superseded/Archived. | AccountingEntity. | A file is not silently overwritten; integrity hash is retained. |
| AttachmentLink | Relates an Attachment to a transaction, journal, exception, or opening balance line. | Finance operations. | Active → Superseded/RemovedWithAudit. | Attachment and target entity. | Posted evidence changes always create audit events. |
| IdempotencyKey | Records an authoritative request/event identity to prevent duplicate posting. | Posting Engine. | Reserved → Completed/Failed/Expired. | AccountingEntity, transaction source. | Same active scope/key/fingerprint must not create two official postings. |
| PostingAttempt | Records lifecycle and recovery state of one posting operation. | Posting Engine / Finance Controller for exceptions. | Started → Validated → Committed; or Failed/RecoveryRequired. | FinancialTransaction, IdempotencyKey. | One committed attempt has one official posting result. |

## 3.4 Posting, ledger, migration, and assurance entities

| Entity | Purpose and description | Business responsibility | Lifecycle | Dependencies | Core business rules |
|---|---|---|---|---|---|
| PostingRule | Named business-accounting rule family for a TransactionType. | Finance Controller. | Draft → Active → Retired. | TransactionType. | A rule is selected by type and effective date, never hardcoded by screen name. |
| PostingRuleVersion | Effective-dated rule definition and required-dimension contract. | Finance Controller. | Draft → Effective → Superseded. | PostingRule, policy references. | A posted Journal references the exact version used. |
| Journal | Header for a balanced accounting posting. | Posting Engine. | Draft → Posting → Posted; Posted → Reversed (by linked journal). | FinancialTransaction, Period, PostingRuleVersion. | Posted journal cannot be edited, deleted, or re-posted. |
| JournalLine | Atomic debit/credit accounting effect and dimensional attribution. | Posting Engine. | Draft with Journal → Posted/Immutable. | Journal, Account, required dimensions. | Exactly one debit or credit positive amount; total journal balances. |
| LedgerEntry | Ordered general-ledger representation of a posted JournalLine. | System-controlled. | Generated → Immutable; may be rebuilt as derived. | Posted JournalLine. | Not independently entered, edited, or treated as an extra source. |
| BalanceProjection | Rebuildable balance aggregate for query performance. | System-controlled; reviewed by Finance Controller. | Building → Current → Stale → Rebuilt. | Posted LedgerEntry/JournalLine. | Never accepts manual balance adjustment. |
| TrialBalanceSnapshot | Reproducible report snapshot for a period/filter/version. | Finance Controller. | Generated → Certified/Obsolete. | Posted GL and report parameters. | Must tie out to underlying GL. |
| MappingSet | Controlled collection of legacy-to-target mapping decisions. | Migration lead. | Draft → Reviewed → Approved → Frozen. | AccountingEntity. | Mapping changes after freeze require a new version or exception. |
| LegacyMapping | One legacy source/field/value mapped to target dimension or exception. | Migration lead. | Draft → Confirmed/Provisional/Exception → Frozen. | MappingSet; target master optional for exception. | Ambiguous data cannot be silently mapped to unrestricted Fund. |
| OpeningBalanceBatch | Governed opening position for a cutover/fiscal start. | Finance Controller. | Draft → Reviewed → Approved → Posted → SupersededByCorrection. | Period, MappingSet, evidence. | Only one active posted batch per scope/cutover. |
| OpeningBalanceLine | Account–Fund–FinancialAccount dimensional amount within an opening batch. | Migration lead / Finance Controller. | Draft → Locked at batch posting. | OpeningBalanceBatch, required masters. | Lines must balance through the OPB Journal and tie to evidence. |
| Reconciliation | Future controlled comparison of ledger balance to bank/cash external evidence. | Reconciliator. | Draft → InProgress → Reviewed → Completed/Exception. | FinancialAccount, Period. | Full workflow becomes operational in Phase 3. |
| ReconciliationItem | Individual statement/cash/ledger item or match in a reconciliation. | Reconciliator. | Unmatched → Matched/Excluded/Exception. | Reconciliation. | Unmatched material items become exceptions. |
| ExceptionCase | A controlled issue that blocks, qualifies, or explains a financial process. | Assigned owner; Finance Controller oversight. | Open → Investigating → PendingApproval → Resolved/AcceptedRisk/Cancelled. | ReasonCode; linked entity. | Exception never changes financial data by itself. |
| ExceptionLog | Immutable timeline event for an ExceptionCase. | Case owner. | Append-only. | ExceptionCase. | Every resolution decision has actor, time, reason, and evidence reference. |
| AuditEvent | Immutable record of material action or state transition. | System-controlled. | Append-only. | Any target entity. | Events retain before/after summary where permitted and correlation identity. |
| BusinessRule | Registered, versioned rule such as BR-001 through BR-122. | Policy owner / Finance Controller. | Draft → Effective → Superseded. | Policy/manual references. | Each technical validation traces to one or more BusinessRule records. |

---

# BAB 4 — Field Specification

## 4.1 Notation and common field profiles

The following profiles are part of every listed entity. Entity-specific tables below add all fields that are not already in the applicable profile.

| Profile | Fields (name — type — nullability — uniqueness — default/validation) |
|---|---|
| P-ID | `id` — UUID — not nullable — unique — immutable system identity. |
| P-SCOPE | `accounting_entity_id` — UUID — not nullable — indexed — valid active AccountingEntity; immutable for financial facts. |
| P-MASTER | `code` — string(40) — not nullable — unique within entity scope — uppercase/normalised; `name` — string(160) — not nullable — unique where policy requires; `status` — enum — not nullable — default Draft/Active by lifecycle; `valid_from` — date — nullable; `valid_to` — date — nullable — must be on/after valid_from; `created_at`, `updated_at` — datetime — not nullable; `created_by`, `updated_by` — UUID/reference — nullable only for approved system import. |
| P-FACT | `source_reference` — string(160) — nullable/required by transaction type — unique within idempotency scope when provided; `business_date` — date — not nullable; `accounting_date` — date — not nullable — must belong to an eligible Period; `description` — string(1000) — nullable/required by rule; `created_at`, `created_by` — datetime/UUID — not nullable; `correlation_id` — UUID/string — not nullable — links related actions. |
| P-EFFECTIVE | `version_no` — integer — not nullable — unique within parent; `effective_from` — date — not nullable; `effective_to` — date — nullable — no overlapping effective ranges within parent; `approved_at`, `approved_by` — datetime/UUID — nullable until approval. |
| P-AUDIT | `is_deleted` is forbidden for posted financial facts. Master soft-retirement uses lifecycle status, not deletion. `change_reason_code_id` and `change_note` are required when policy marks a change material. |

Unless explicitly stated otherwise, UUID references must resolve inside the same AccountingEntity and refer to an active/effective master at `accounting_date`.

## 4.2 Organisation and calendar fields

### AccountingEntity

| Field | Data type | Nullable | Unique | Default / validation / business rule |
|---|---|---:|---:|---|
| P-ID, P-MASTER | As profile | No | As profile | Profile applies. |
| legal_name | string(240) | No | No | Official Masjid/yayasan name. |
| functional_currency | char(3) | No | No | Default `IDR`; valid ISO currency code. |
| timezone | string(64) | No | No | Default policy timezone; used only for operational timestamps. |
| fiscal_year_start_month | small integer | No | No | 1–12; governs calendar creation. |

### AccountingCalendar and AccountingPeriod

| Entity / Field | Data type | Nullable | Unique | Default / validation / business rule |
|---|---|---:|---:|---|
| AccountingCalendar: P-ID, P-SCOPE, P-MASTER | As profile | No | As profile | Profile applies. |
| fiscal_year_label | string(20) | No | Unique within entity | Example `2026`; no duplicate fiscal calendar. |
| start_date, end_date | date | No | No | end_date >= start_date. |
| AccountingPeriod: P-ID, P-SCOPE | As profile | No | As profile | Profile applies. |
| calendar_id | UUID | No | No | Valid parent calendar. |
| period_no | small integer | No | Unique with calendar | 1–13; 13 only if approved adjustment period exists. |
| period_name | string(40) | No | No | Human-readable period. |
| start_date, end_date | date | No | No | Fits inside calendar and cannot overlap sibling periods. |
| status | enum(Future,Open,SoftClosed,HardClosed,Reopened) | No | No | Default Future; transition only through state machine. |
| closed_at, closed_by | datetime, UUID | Yes | No | Mandatory when SoftClosed/HardClosed. |
| reopen_reason_code_id, reopen_note | UUID, string(1000) | Yes | No | Mandatory when Reopened. |

### ClosingRun

| Field | Data type | Nullable | Unique | Default / validation / business rule |
|---|---|---:|---:|---|
| P-ID, P-SCOPE, P-FACT | As profile | No | As profile | `accounting_date` represents close request date. |
| period_id | UUID | No | Unique for active run | One non-terminal active run per Period. |
| run_type | enum(SoftClose,HardClose,Reopen) | No | No | Determines permitted transition. |
| status | enum(Planned,InProgress,Blocked,Completed,Cancelled) | No | No | State-machine controlled. |
| checklist_version | string(40) | No | No | References approved closing checklist. |
| result_summary | string(4000) | Yes | No | Required on Completed/Blocked. |
| completed_at, completed_by | datetime, UUID | Yes | No | Required on Completed. |

## 4.3 Accounting master fields

### AccountGroup, Account, and AccountDimensionRule

| Entity / Field | Data type | Nullable | Unique | Default / validation / business rule |
|---|---|---:|---:|---|
| AccountGroup: P-ID, P-SCOPE, P-MASTER | As profile | No | As profile | Profile applies. |
| parent_group_id | UUID | Yes | No | Cannot reference self or form a hierarchy cycle. |
| group_class | enum(Asset,Liability,NetAsset,Revenue,Expense,Transfer,Control) | No | No | Must be compatible with parent group. |
| display_order | integer | No | No | Non-negative. |
| Account: P-ID, P-SCOPE, P-MASTER | As profile | No | As profile | Profile applies. |
| account_group_id | UUID | No | No | Active parent AccountGroup. |
| account_class | enum as group_class | No | No | Compatible with AccountGroup. |
| normal_balance | enum(Debit,Credit) | No | No | Required; immutable after first posting except controlled supersession. |
| is_posting_account | boolean | No | No | Default true; false accounts cannot appear on JournalLine. |
| is_liquidity_account | boolean | No | No | Default false; true requires FinancialAccount on relevant lines. |
| is_control_account | boolean | No | No | Default false; direct posting only by approved rule. |
| allow_manual_posting | boolean | No | No | Default false for control accounts. |
| AccountDimensionRule: P-ID, P-SCOPE, P-EFFECTIVE | As profile | No | As profile | Profile applies. |
| account_id | UUID | No | No | Parent Account. |
| dimension_name | enum(Fund,FinancialAccount,Program,CostCenter,Counterparty,Category) | No | Unique with account/effective range | One requirement per dimension/date range. |
| requirement | enum(Required,Optional,Forbidden) | No | No | Enforced by Posting Engine. |
| applies_to_debit, applies_to_credit | boolean, boolean | No | No | At least one must be true. |

### FundType, FundRestriction, Fund, and FundPolicyVersion

| Entity / Field | Data type | Nullable | Unique | Default / validation / business rule |
|---|---|---:|---:|---|
| FundType: P-ID, P-SCOPE, P-MASTER | As profile | No | As profile | Profile applies. |
| classification | enum(Unrestricted,Designated,Restricted,PerpetualRestricted,Custodial,Syariah) | No | No | Controlled taxonomy. |
| FundRestriction: P-ID, P-SCOPE, P-MASTER | As profile | No | As profile | Profile applies. |
| severity | enum(Low,Medium,High,Critical) | No | No | Drives approval/exception rules. |
| policy_basis | string(1000) | No | No | Donor, mandate, akad/ikrar, or governance basis. |
| Fund: P-ID, P-SCOPE, P-MASTER | As profile | No | As profile | Profile applies. |
| fund_type_id, fund_restriction_id | UUID, UUID | No | No | Both must be active and compatible. |
| purpose_statement | string(2000) | No | No | Official allowed purpose. |
| prohibited_use_statement | string(2000) | Yes | No | Mandatory for High/Critical restriction where policy requires. |
| minimum_balance_policy | decimal(19,2) | Yes | No | Must be >= 0; does not itself create a ledger balance. |
| allow_negative_balance | boolean | No | No | Default false; true only with approved policy basis. |
| FundPolicyVersion: P-ID, P-SCOPE, P-EFFECTIVE | As profile | No | As profile | Profile applies. |
| fund_id | UUID | No | No | Parent Fund. |
| policy_document_ref | string(500) | No | No | Reference to governance decision/version. |
| allowed_matrix_ref | string(500) | No | No | Required for restricted Fund. |
| exception_approval_level | string(80) | No | No | Must map to approved authority matrix. |

### Category, ReasonCode, TransactionType, BusinessRule

| Entity / Field | Data type | Nullable | Unique | Default / validation / business rule |
|---|---|---:|---:|---|
| Category: P-ID, P-SCOPE, P-MASTER | As profile | No | As profile | Profile applies. |
| transaction_type_id | UUID | Yes | No | Optional default transaction family. |
| default_posting_rule_id | UUID | Yes | No | Must be active/effective if populated. |
| ReasonCode: P-ID, P-SCOPE, P-MASTER | As profile | No | As profile | Profile applies. |
| reason_class | enum(Reversal,Adjustment,Exception,Void,Override,Reopen,Migration) | No | No | Controls where it can be used. |
| requires_note, requires_attachment | boolean, boolean | No | No | Enforced on referencing event. |
| TransactionType: P-ID, P-SCOPE, P-MASTER | As profile | No | As profile | Code is RCV/PAY/TRF/OPB/REV/ADJ/CLS etc. |
| voucher_prefix | string(10) | No | No | Must be unique by active sequence scope. |
| has_financial_impact | boolean | No | No | Phase 2 types RCV/PAY/TRF/OPB/REV/ADJ true. |
| BusinessRule: P-ID, P-SCOPE, P-EFFECTIVE | As profile | No | As profile | Profile applies. |
| rule_code | string(20) | No | Unique within entity | Example BR-066. |
| rule_text | string(4000) | No | No | Controlled policy statement. |
| rule_domain | enum(Master,Fund,Rekening,Program,Transaction,Posting,Closing,Reconciliation,Voucher,Audit,Reporting) | No | No | Used for traceability. |
| severity | enum(Block,Warning,Review) | No | No | Technical validation must honor severity. |

## 4.4 Treasury, attribution, and counterparty fields

| Entity / Field | Data type | Nullable | Unique | Default / validation / business rule |
|---|---|---:|---:|---|
| FinancialAccount: P-ID, P-SCOPE, P-MASTER | As profile | No | As profile | Profile applies. |
| account_id | UUID | No | No | Active liquidity Account with matching class. |
| account_type | enum(Bank,Cash,PettyCash,EWallet) | No | No | Determines permitted detail record. |
| custodian_reference | UUID/string(100) | No | No | Mandatory for Cash/PettyCash. |
| currency_code | char(3) | No | No | Default entity functional currency; foreign currency requires approved policy. |
| opening_date, closing_date | date, date | No, Yes | No | closing_date >= opening_date. |
| BankAccountDetail: P-ID | As profile | No | As profile | Parent `financial_account_id` unique. |
| financial_account_id | UUID | No | Unique | Parent type must be Bank. |
| bank_name | string(160) | No | No | Required. |
| branch_name | string(160) | Yes | No | Optional. |
| account_number_masked | string(80) | No | No | Display-safe identifier. |
| account_number_protected_ref | string(500) | Yes | Unique where available | Sensitive storage reference; never shown in ordinary reports. |
| CashAccountDetail: P-ID | As profile | No | As profile | Parent `financial_account_id` unique. |
| financial_account_id | UUID | No | Unique | Parent type Cash/PettyCash. |
| cash_location | string(240) | No | No | Physical location/custody point. |
| cash_count_frequency | enum(Daily,Weekly,Monthly,AdHoc) | No | No | Policy-controlled. |
| petty_cash_limit | decimal(19,2) | Yes | No | >= 0; mandatory for PettyCash. |
| CostCenter: P-ID, P-SCOPE, P-MASTER | As profile | No | As profile | Profile applies. |
| parent_cost_center_id | UUID | Yes | No | No hierarchy cycle. |
| manager_reference | UUID/string(100) | Yes | No | Responsible function reference. |
| Program: P-ID, P-SCOPE, P-MASTER | As profile | No | As profile | Profile applies. |
| cost_center_id | UUID | Yes | No | Optional active parent. |
| start_date, end_date | date, date | Yes, Yes | No | end_date >= start_date. |
| program_owner_reference | UUID/string(100) | Yes | No | Does not confer Fund ownership. |
| Counterparty: P-ID, P-SCOPE, P-MASTER | As profile | No | As profile | Profile applies. |
| party_type | enum(Donor,Supplier,Beneficiary,Bank,Institution,Other) | No | No | Controlled taxonomy. |
| display_name | string(240) | No | No | Privacy-aware display name. |
| external_reference | string(160) | Yes | No | Unique only if policy demands. |
| contact_reference | string(500) | Yes | No | Protected/minimised personal data. |

## 4.5 Transaction, document, and evidence fields

### FinancialTransaction and TransactionSplit

| Entity / Field | Data type | Nullable | Unique | Default / validation / business rule |
|---|---|---:|---:|---|
| FinancialTransaction: P-ID, P-SCOPE, P-FACT | As profile | No | As profile | Profile applies. |
| transaction_type_id | UUID | No | No | Active/effective TransactionType. |
| status | enum(Draft,Submitted,Verified,Approved,Posting,Posted,Rejected,Cancelled,Reversed) | No | No | Default Draft; state-machine controlled. |
| currency_code | char(3) | No | No | Default entity currency. |
| gross_amount | decimal(19,2) | No | No | > 0; scale conforms currency policy. |
| primary_financial_account_id | UUID | Yes | No | Required for RCV/PAY/TRF/OPB where liquidity is affected. |
| counterparty_id | UUID | Yes | No | Required by type/policy. |
| category_id | UUID | Yes | No | Must be eligible for TransactionType. |
| reason_code_id | UUID | Yes | No | Mandatory for REV/ADJ/exception workflow. |
| related_transaction_id | UUID | Yes | No | Mandatory for REV; cannot self-reference. |
| idempotency_key | string(160) | No | Unique by entity/type/scope | Stable request/event identity. |
| policy_version_ref | string(80) | Yes | No | Required for restricted Fund effects. |
| TransactionSplit: P-ID, P-SCOPE | As profile | No | As profile | Parent transaction scope applies. |
| transaction_id | UUID | No | No | Parent FinancialTransaction. |
| line_no | integer | No | Unique with transaction | Starts at 1; no duplicate active line. |
| split_amount | decimal(19,2) | No | No | > 0; sum equals gross amount. |
| account_id | UUID | No | No | Required posting Account or template-resolved account. |
| fund_id | UUID | Yes | No | Required/forbidden by AccountDimensionRule and policy. |
| financial_account_id | UUID | Yes | No | Required for liquidity effects; must be compatible Account. |
| program_id, cost_center_id | UUID, UUID | Yes | No | Required/forbidden by dimension rules. |
| counterparty_id, category_id | UUID, UUID | Yes | No | May override header only if rule allows. |
| purpose_note | string(1000) | Yes | No | Required for policy exception or certain Fund. |

### ApprovalDecision, DocumentSequence, and Voucher

| Entity / Field | Data type | Nullable | Unique | Default / validation / business rule |
|---|---|---:|---:|---|
| ApprovalDecision: P-ID, P-SCOPE | As profile | No | As profile | Profile applies. |
| transaction_id | UUID | No | No | Parent transaction. |
| step_no | small integer | No | Unique with transaction | Approval ordering. |
| decision | enum(Pending,Approved,Rejected,Expired,Superseded) | No | No | Default Pending. |
| decision_at | datetime | Yes | No | Required for terminal decision. |
| approver_reference | UUID/string(100) | No | No | Must be authorised per matrix. |
| comment | string(2000) | Yes | No | Mandatory on rejection/override. |
| DocumentSequence: P-ID, P-SCOPE, P-MASTER | As profile | No | As profile | Profile applies. |
| transaction_type_id | UUID | No | No | Active type. |
| prefix | string(10) | No | No | Complies with voucher policy. |
| scope_key | string(120) | No | Unique with prefix/type/entity | Defines annual/monthly/entity sequence scope. |
| next_value | bigint | No | No | >= 1; changed atomically only. |
| reset_rule | enum(Never,Yearly,Monthly) | No | No | Must be compatible with scope_key. |
| Voucher: P-ID, P-SCOPE | As profile | No | As profile | Profile applies. |
| transaction_id | UUID | No | Unique | Parent posted transaction. |
| document_sequence_id | UUID | No | No | Source sequence. |
| voucher_number | string(80) | No | Unique within entity | Issued human identifier. |
| status | enum(Reserved,Issued,Voided,Referenced) | No | No | Issued only with committed posting. |
| issued_at | datetime | Yes | No | Mandatory when Issued. |
| void_reason_code_id, void_note | UUID, string(1000) | Yes | No | Mandatory when Voided. |

### Attachment and AttachmentLink

| Entity / Field | Data type | Nullable | Unique | Default / validation / business rule |
|---|---|---:|---:|---|
| Attachment: P-ID, P-SCOPE | As profile | No | As profile | Profile applies. |
| original_filename | string(255) | No | No | Stored for evidence display only. |
| media_type | string(120) | No | No | Must be permitted evidence format. |
| byte_size | bigint | No | No | > 0; bounded by policy. |
| content_hash | string(128) | No | Unique within entity/content | Integrity fingerprint. |
| storage_reference | string(700) | No | Unique | Immutable content location/reference. |
| status | enum(PendingScan,Active,Superseded,Archived,Rejected) | No | No | Only Active may satisfy mandatory evidence rule. |
| received_at | datetime | No | No | Evidence reception time. |
| AttachmentLink: P-ID, P-SCOPE | As profile | No | As profile | Profile applies. |
| attachment_id | UUID | No | No | Active/approved Attachment where required. |
| target_type | enum(Transaction,Journal,OpeningBalanceLine,Exception,Reconciliation) | No | No | Controlled target set. |
| target_id | UUID | No | No | Must exist in same entity scope. |
| evidence_type | enum(Receipt,Invoice,TransferProof,Statement,CashCount,Approval,Policy,Other) | No | No | Determines completeness checks. |
| status | enum(Active,Superseded,RemovedWithAudit) | No | No | Removal after posting requires reason/audit. |

## 4.6 Posting, ledger, and balance fields

### PostingRule, PostingRuleVersion, IdempotencyKey, and PostingAttempt

| Entity / Field | Data type | Nullable | Unique | Default / validation / business rule |
|---|---|---:|---:|---|
| PostingRule: P-ID, P-SCOPE, P-MASTER | As profile | No | As profile | Profile applies. |
| transaction_type_id | UUID | No | No | Parent TransactionType. |
| rule_family | string(80) | No | Unique with type | Business-readable rule identity. |
| PostingRuleVersion: P-ID, P-SCOPE, P-EFFECTIVE | As profile | No | As profile | Profile applies. |
| posting_rule_id | UUID | No | No | Parent PostingRule. |
| input_contract_ref | string(500) | No | No | References approved input/validation catalogue. |
| journal_template_ref | string(500) | No | No | References controlled rule definition, not executable code. |
| business_rule_refs | string(2000) | No | No | List of BusinessRule codes. |
| IdempotencyKey: P-ID, P-SCOPE | As profile | No | As profile | Profile applies. |
| scope_name | string(80) | No | No | Example transaction-posting. |
| key_value | string(160) | No | Unique with scope/entity | Client/source idempotency identity. |
| request_fingerprint | string(128) | No | No | Same key with different payload must be rejected. |
| status | enum(Reserved,Completed,Failed,Expired) | No | No | State machine controlled. |
| result_reference | UUID/string(160) | Yes | No | Official transaction/journal when Completed. |
| expires_at | datetime | Yes | No | Expiry cannot remove completed evidence. |
| PostingAttempt: P-ID, P-SCOPE | As profile | No | As profile | Profile applies. |
| transaction_id | UUID | No | No | Parent FinancialTransaction. |
| idempotency_record_id | UUID | No | Unique for committed result | Key record. |
| status | enum(Started,Validated,Committed,Failed,RecoveryRequired) | No | No | State-machine controlled. |
| attempt_no | integer | No | Unique with transaction | >= 1. |
| requested_at, completed_at | datetime, datetime | No, Yes | No | completed_at required terminal. |
| failure_code, failure_detail | string(80), string(4000) | Yes, Yes | No | Mandatory Failed/RecoveryRequired. |
| journal_id | UUID | Yes | Unique when committed | Official result. |

### Journal, JournalLine, LedgerEntry, BalanceProjection, and TrialBalanceSnapshot

| Entity / Field | Data type | Nullable | Unique | Default / validation / business rule |
|---|---|---:|---:|---|
| Journal: P-ID, P-SCOPE, P-FACT | As profile | No | As profile | Profile applies. |
| transaction_id | UUID | No | No | Source FinancialTransaction. |
| posting_attempt_id | UUID | No | Unique | Source committed attempt. |
| posting_rule_version_id | UUID | No | No | Exact effective rule used. |
| period_id | UUID | No | No | Eligible Period for accounting_date. |
| journal_status | enum(Draft,Posting,Posted,Reversed) | No | No | Posted is immutable. |
| posting_sequence | bigint | Yes | Unique within entity | Required when Posted; canonical ledger ordering. |
| total_debit, total_credit | decimal(19,2), decimal(19,2) | No | No | Both >= 0 and exactly equal when Posted. |
| reversal_of_journal_id | UUID | Yes | Unique only where full reversal policy | Required for REV. |
| JournalLine: P-ID, P-SCOPE | As profile | No | As profile | Parent Journal scope applies. |
| journal_id | UUID | No | No | Parent Journal. |
| line_no | integer | No | Unique with journal | Starts at 1. |
| account_id | UUID | No | No | Active posting Account. |
| debit_amount, credit_amount | decimal(19,2), decimal(19,2) | No | No | Exactly one > 0; other = 0. |
| fund_id, financial_account_id | UUID, UUID | Yes, Yes | No | Required/forbidden by AccountDimensionRule and rule contract. |
| program_id, cost_center_id | UUID, UUID | Yes, Yes | No | Required/forbidden by AccountDimensionRule. |
| counterparty_id, category_id | UUID, UUID | Yes, Yes | No | Required where contract demands. |
| policy_version_ref | string(80) | Yes | No | Mandatory for restricted Fund effect. |
| line_description | string(1000) | Yes | No | Required for ADJ/OPB and rule-specific cases. |
| LedgerEntry: P-ID, P-SCOPE | As profile | No | As profile | Derived record/view. |
| journal_line_id | UUID | No | Unique | Exactly one source posted JournalLine. |
| accounting_date | date | No | No | Copied from posted Journal; immutable. |
| posting_sequence, line_no | bigint, integer | No | Unique composite order | Canonical stable ordering. |
| signed_amount | decimal(19,2) | No | No | Derived using Account normal balance; never manually set. |
| BalanceProjection: P-ID, P-SCOPE | As profile | No | As profile | Derived/rebuildable. |
| projection_type | enum(Account,FinancialAccount,Fund,AccountFundFinancialAccount,Program) | No | No | Defines aggregation grain. |
| dimension_key | string(500) | No | Unique with type/as_of | Canonical dimension combination. |
| as_of_accounting_date | date | No | No | Projection cutoff. |
| through_posting_sequence | bigint | No | No | Rebuild watermark. |
| debit_total, credit_total, balance | decimal(19,2) x3 | No | No | Derived only; no direct business update. |
| projection_status | enum(Building,Current,Stale,Failed) | No | No | Current only after tie-out. |
| TrialBalanceSnapshot: P-ID, P-SCOPE | As profile | No | As profile | Snapshot metadata. |
| period_id | UUID | No | No | Target period. |
| as_of_posting_sequence | bigint | No | No | Reproducibility watermark. |
| filter_signature | string(500) | No | No | Canonical report parameter identity. |
| total_debit, total_credit | decimal(19,2), decimal(19,2) | No | No | Must equal. |
| certification_status | enum(Generated,Reviewed,Certified,Obsolete) | No | No | Certification requires Finance Controller review. |

## 4.7 Migration, reconciliation, exception, and audit fields

| Entity / Field | Data type | Nullable | Unique | Default / validation / business rule |
|---|---|---:|---:|---|
| MappingSet: P-ID, P-SCOPE, P-MASTER | As profile | No | As profile | Profile applies. |
| source_system_name | string(160) | No | No | Legacy source identity. |
| cutover_date | date | No | No | Matches approved cutover charter. |
| mapping_status | enum(Draft,Reviewed,Approved,Frozen) | No | No | State-machine controlled. |
| LegacyMapping: P-ID, P-SCOPE | As profile | No | As profile | Profile applies. |
| mapping_set_id | UUID | No | No | Parent MappingSet. |
| legacy_record_ref | string(240) | No | Unique within mapping set | Stable legacy reference. |
| legacy_value | string(4000) | Yes | No | Preserved source value/context. |
| target_entity_type, target_entity_id | string(80), UUID | Yes, Yes | No | Both required for Confirmed/Provisional mapping. |
| mapping_status | enum(Draft,Confirmed,Provisional,Exception,OutOfScopeArchive,Frozen) | No | No | Exception requires ExceptionCase. |
| rationale | string(2000) | No | No | Mapping basis. |
| OpeningBalanceBatch: P-ID, P-SCOPE, P-FACT | As profile | No | As profile | Profile applies. |
| cutover_date | date | No | Unique active batch scope | Approved date. |
| period_id, mapping_set_id | UUID, UUID | No | No | Must be approved/frozen. |
| status | enum(Draft,Reviewed,Approved,Posting,Posted,SupersededByCorrection) | No | No | State-machine controlled. |
| evidence_package_ref | string(700) | No | No | Required before approval. |
| OpeningBalanceLine: P-ID, P-SCOPE | As profile | No | As profile | Profile applies. |
| batch_id | UUID | No | No | Parent batch. |
| line_no | integer | No | Unique with batch | Starts at 1. |
| account_id, fund_id, financial_account_id | UUID x3 | No, Yes, Yes | No | Requirement follows AccountDimensionRule. |
| debit_amount, credit_amount | decimal(19,2), decimal(19,2) | No | No | Exactly one positive; batch must balance. |
| evidence_ref, mapping_ref | string(700), string(240) | No, Yes | No | Evidence mandatory. |
| Reconciliation: P-ID, P-SCOPE, P-FACT | As profile | No | As profile | Reserved Phase 3 workflow. |
| financial_account_id, period_id | UUID, UUID | No | Unique active scope | Target account and period. |
| statement_balance, ledger_balance | decimal(19,2), decimal(19,2) | No | No | From evidence/GL; difference derived. |
| status | enum(Draft,InProgress,Reviewed,Completed,Exception) | No | No | State-machine controlled. |
| ReconciliationItem: P-ID, P-SCOPE | As profile | No | As profile | Profile applies. |
| reconciliation_id | UUID | No | No | Parent reconciliation. |
| item_source | enum(Statement,CashCount,Ledger,Adjustment) | No | No | Controlled origin. |
| external_reference | string(240) | Yes | No | Required for statement item where available. |
| amount | decimal(19,2) | No | No | Non-zero. |
| match_status | enum(Unmatched,Matched,Excluded,Exception) | No | No | Material unmatched item requires ExceptionCase. |
| ExceptionCase: P-ID, P-SCOPE, P-FACT | As profile | No | As profile | Profile applies. |
| exception_code | string(40) | No | Unique within entity | Human/reference identifier. |
| reason_code_id | UUID | No | No | Active compatible ReasonCode. |
| severity | enum(Critical,High,Medium,Low) | No | No | Drives escalation. |
| status | enum(Open,Investigating,PendingApproval,Resolved,AcceptedRisk,Cancelled) | No | No | State-machine controlled. |
| target_type, target_id | string(80), UUID | No, No | No | Affected entity reference. |
| owner_reference | UUID/string(100) | No | No | Responsible resolver. |
| due_date | date | Yes | No | Mandatory Critical/High unless resolved. |
| ExceptionLog: P-ID, P-SCOPE | As profile | No | As profile | Profile applies. |
| exception_case_id | UUID | No | No | Parent case. |
| event_type | enum(Created,Commented,EvidenceAdded,Escalated,Decision,Resolved,Reopened) | No | No | Append-only. |
| event_note | string(4000) | Yes | No | Mandatory for decision/resolution. |
| AuditEvent: P-ID, P-SCOPE | As profile | No | As profile | Profile applies. |
| event_at | datetime | No | No | Immutable event time. |
| event_type | string(80) | No | No | Controlled event taxonomy. |
| target_type, target_id | string(80), UUID | No, No | No | Target identity. |
| actor_reference | UUID/string(100) | Yes | No | Null only approved system operation. |
| correlation_id | UUID/string(100) | No | No | Links transaction/attempt chain. |
| before_summary, after_summary | string(4000), string(4000) | Yes, Yes | No | Privacy-safe metadata; required where material mutable master change. |
| integrity_hash | string(128) | Yes | No | Recommended for tamper-evidence. |

---

# BAB 5 — Relationship Design

## 5.1 Relationship types

| Relationship type | Use in this model | Rule |
|---|---|---|
| One-to-one | FinancialAccount ↔ relevant subtype detail; JournalLine ↔ LedgerEntry. | Child cannot exist without parent; ledger representation remains derived. |
| One-to-many | Journal → JournalLine; Transaction → Split; Fund → PolicyVersion; Period → ClosingRun. | Child preserves parent identity and scope. |
| Many-to-many through facts | Fund ↔ FinancialAccount through JournalLine; Fund ↔ Program through TransactionSplit/JournalLine. | Never model as a hard parent-child master link. |
| Composition | JournalLine is composed by Journal; OpeningBalanceLine by OpeningBalanceBatch. | Parent deletion is prohibited once any accounting state is posted. |
| Aggregation | AccountGroup → Account; CostCenter → Program. | Child retains its own lifecycle; retirement checks dependent use. |
| Reference association | Transaction ↔ Counterparty/Category/ReasonCode; Journal ↔ PostingRuleVersion. | Reference is validated effective on accounting date. |

## 5.2 Canonical dimensional relationships

```text
                 Fund
                  ^
                  |
Account <---- JournalLine ----> FinancialAccount
   ^              |                   ^
   |              |                   |
AccountGroup       +----> Program <---- CostCenter
                  |
                  +----> Counterparty / Category

One JournalLine represents one accounting effect.
Many JournalLines, over time, create the many-to-many Fund × FinancialAccount position.
```

## 5.3 Ownership matrix

| Child entity | Owner/composite parent | Parent action implication |
|---|---|---|
| AccountingPeriod | AccountingCalendar | Calendar cannot be retired while active/open periods exist. |
| AccountDimensionRule | Account | Rule is superseded, never retrospectively deleted. |
| FundPolicyVersion | Fund | Fund history retains prior effective policy. |
| BankAccountDetail/CashAccountDetail | FinancialAccount | Parent type determines exactly one allowed detail. |
| TransactionSplit | FinancialTransaction | Split locks at posting. |
| ApprovalDecision | FinancialTransaction | Approval history is retained. |
| Voucher | FinancialTransaction | Issued voucher remains attached to historical transaction. |
| JournalLine | Journal | Posted Journal owns immutable line set. |
| LedgerEntry | JournalLine | Derived effect is regenerated only from posted source line. |
| OpeningBalanceLine | OpeningBalanceBatch | Batch approval/posted status locks all lines. |
| ReconciliationItem | Reconciliation | Match history retained even if reconciliation reopens. |
| ExceptionLog | ExceptionCase | Event log append-only. |

## 5.4 Referential-integrity rules

1. Every financial fact belongs to exactly one AccountingEntity.
2. A Journal, its Transaction, Voucher, Period, PostingAttempt, and all JournalLines must share that AccountingEntity.
3. JournalLine references must be active/effective on accounting date, unless an explicit historical reference is permitted for reversal.
4. A transaction cannot reference a Program or Fund from another AccountingEntity.
5. A Fund cannot be deleted after it appears in any JournalLine; it can only be closed for future use.
6. A FinancialAccount closure prevents new postings after closing date but never removes past ledger effects.
7. A Voucher has exactly one source Transaction and no Voucher number can be reassigned.
8. A derived LedgerEntry cannot reference a JournalLine that is not Posted.
9. An OpeningBalanceLine must use dimensions valid on cutover date.
10. An ExceptionCase must reference an existing target and does not itself authorise a financial posting.

---

# BAB 6 — State Machines

## 6.1 FinancialTransaction

```text
Draft → Submitted → Verified → Approved → Posting → Posted
  |         |           |           |          |        |
  |         v           v           v          v        v
  +----> Cancelled   Rejected    Rejected    Failed   Reversed*

* Reversed is a derived terminal relationship after a linked REV posting;
  it does not erase the original Posted transaction.
```

| From | To | Trigger / guard |
|---|---|---|
| Draft | Submitted | Required data, splits, and preliminary evidence complete. |
| Submitted | Verified | Verifier confirms classification and evidence. |
| Verified | Approved | Required approval decisions are Approved. |
| Approved | Posting | Posting Engine reserves idempotency scope and validates current rules. |
| Posting | Posted | Atomic commit creates Journal, Voucher, audit event, and ledger effect. |
| Posting | Failed | No official financial result committed; failure recorded. |
| Any pre-posted | Rejected/Cancelled | Reason recorded; no Journal exists. |
| Posted | Reversed | Linked reversal Journal is Posted. |

## 6.2 Journal and Voucher

```text
Journal: Draft → Posting → Posted → [immutable]
                              |
                              v
                         Reversed by linked Journal

Voucher: Reserved → Issued → Referenced
                     |
                     +→ Voided (only if no official posting used it, with reason)
```

Rules: a Posted Journal never returns to Draft; an Issued Voucher cannot be reused; voided Voucher numbers remain visible in gap reports.

## 6.3 AccountingPeriod and ClosingRun

```text
Period: Future → Open → SoftClosed → HardClosed
                         |              |
                         +-- Reopened --+

ClosingRun: Planned → InProgress → Completed
                            |
                            +→ Blocked → InProgress
```

Phase 2 technically enforces that ordinary posting must target an Open Period. SoftClose/HardClose/reopen workflow and reconciliation gates are expanded in Phase 3, but the state values and audit contract are reserved now.

## 6.4 Fund, FinancialAccount, and OpeningBalanceBatch

```text
Fund: Draft → Active → Suspended → Closed
                    ↘ (policy version changes through effective dating)

FinancialAccount: Draft → Active → Suspended → Closed

OpeningBalanceBatch: Draft → Reviewed → Approved → Posting → Posted
                                       |              |
                                       v              v
                                    Rejected    SupersededByCorrection
```

No closed Fund or FinancialAccount is eligible for new ordinary transactions. Past posted effects remain reportable. A posted opening batch can only be addressed by a governed correction, never edited.

## 6.5 ExceptionCase and PostingAttempt

```text
ExceptionCase: Open → Investigating → PendingApproval → Resolved
                   |                     |                  |
                   +---------------------+→ AcceptedRisk    +→ Reopened

PostingAttempt: Started → Validated → Committed
                    |          |
                    +----------+→ Failed → RecoveryRequired → Resolved/Retried
```

---

# BAB 7 — Posting Engine

## 7.1 Responsibility

The Posting Engine is the sole business boundary that creates official accounting impact. It does not decide policy independently; it executes the approved PostingRuleVersion, BusinessRule, Fund policy version, and period control for the transaction.

| Responsibility | Required behaviour |
|---|---|
| Validation | Resolves type/rule versions; validates master status, dimensions, splits, evidence, approvals, period eligibility, restriction rules, and balancing. |
| Idempotency | Reserves and completes one key/fingerprint scope so duplicate submit or safe retry returns the same official result rather than a second posting. |
| Numbering | Allocates Voucher only as part of an atomic successful posting path. |
| Journal construction | Creates one Journal header and all required JournalLines from approved rule inputs. |
| Ledger publication | Marks journal as posted and exposes immutable JournalLine effects as LedgerEntry; no separate manual ledger write exists. |
| Audit | Emits correlated, immutable events for validation result, posting, numbering, and any exception. |
| Exception handling | Fails safely with code/evidence; creates or links ExceptionCase when human governance is required. |
| Concurrency | Applies appropriate locking/isolation to sequence, idempotency, transaction state, period state, and balance-sensitive controls. |
| Rollback | If any mandatory action fails before commit, no Journal, Voucher, balance effect, or partial audit claim of successful posting remains. |
| Recovery | A timed-out/unknown attempt is reconciled by idempotency record and committed result before any retry is allowed. |

## 7.2 Canonical posting sequence

```text
1. Receive request with transaction identity and idempotency key
2. Lock/read source transaction and confirm Approved state
3. Reserve/read idempotency record; return existing committed result if present
4. Resolve accounting period, PostingRuleVersion, Fund policy, and BusinessRules by accounting date
5. Validate source data, splits, evidence, approval, restrictions, and dimensions
6. Construct proposed Journal and validate debit = credit
7. Allocate posting sequence and reserve Voucher within same atomic boundary
8. Persist Journal + JournalLines + Voucher + audit events
9. Mark Journal/Transaction/Attempt/Idempotency as Posted/Committed
10. Publish or make available derived LedgerEntry and projections
11. Return one immutable posting result and trace references
```

## 7.3 Atomicity boundary

The following actions belong to a single all-or-nothing accounting commitment:

- source transaction transition from Posting to Posted;
- final idempotency reservation/result;
- Journal and all JournalLines;
- unique Voucher issuance;
- immutable posting sequence allocation;
- audit event that proves posting;
- linkage to PostingRuleVersion, Fund policy version, and approved source transaction.

Balance projections, notifications, search indexing, and non-financial report caching may be updated after commit only if they are rebuildable. They must never decide whether accounting posting succeeded.

## 7.4 Posting Engine decision table

| Condition | Result | Financial effect | Required trace |
|---|---|---|---|
| Existing completed idempotency key with same fingerprint | Return prior result. | None additional. | Existing Transaction/Journal/Voucher IDs. |
| Same key, different fingerprint | Reject and create audit/security event. | None. | Conflict reason. |
| Transaction not Approved | Reject. | None. | State/approval failure. |
| Period not Open | Reject. | None. | Period status. |
| Required evidence absent | Reject or route to governed exception if policy permits. | None before approval. | Evidence rule. |
| Fund restriction fails | Reject; exception only through authorised process. | None. | Fund/policy version and reason. |
| Journal proposed unbalanced | Reject. | None. | Debit/credit calculation. |
| Voucher allocation conflict | Retry within safe atomic process or fail recoverably. | None until successful. | Sequence/attempt record. |
| Storage/commit failure | Roll back accounting commitment. | None. | Failed attempt and recovery state. |
| Commit succeeds | Mark Posted and publish ledger. | Exactly one official effect. | Journal, Voucher, AuditEvent. |

---

# BAB 8 — Posting Rule Catalogue

## 8.1 Rule conventions

- Rule catalogue is business-accounting metadata, not executable code.
- Every outcome is expressed as a balanced Journal using the selected CoA and required dimensions.
- Exact Account choices are controlled by approved CoA and PostingRuleVersion, not embedded in forms.
- Fund restriction is checked before journal commit; Program attribution never overrides a Fund prohibition.

## 8.2 Core Phase 2 transaction rules

| Type | Input | Validation | Posting result / journal impact | Ledger and Fund impact | Restriction checking |
|---|---|---|---|---|---|
| RCV — Receipt | Date, amount, receiving FinancialAccount, Fund/split, source/counterparty, category, evidence. | Amount > 0; valid account/Fund; evidence; duplicate check; Open Period. | Debit liquidity/receivable asset; credit revenue/net asset/liability as policy defines. | Receiving account balance rises; assigned Fund rises or is credited per policy. | Identify donor intent; restricted policy version is recorded. |
| PAY — Payment | Date, amount, paying FinancialAccount, Fund/split, purpose, counterparty, evidence, Program if required. | Amount/splits; sufficient permitted Fund position/exception; allowed Fund use; approval; Open Period. | Debit expense/asset/distribution; credit liquidity/payable according to rule. | Paying account falls; Fund use is reflected by its net accounting effect. | Fund × purpose/program rule must allow; restricted use cannot be bypassed. |
| TRF — Physical transfer | Source and destination FinancialAccount, amount, Fund composition/split, transfer proof. | Source ≠ destination; same currency/policy; total Fund composition equal; evidence; Open Period. | Debit destination liquidity Account; credit source liquidity Account. | Total liquidity and each Fund’s total remain unchanged; only location changes. | No ownership transfer; restricted Fund identity preserved. |
| OPB — Opening balance | Cutover date, account/Fund/Rekening dimensions, amount, evidence, mapping, batch approval. | Approved batch; unique scope; external tie-out; balanced batch; eligible period. | Balanced opening Journal using approved opening/control counterpart as policy defines. | Establishes initial ledger position; not current-period revenue/expense. | Fund classification must be evidenced; ambiguity becomes exception. |
| REV — Reversal | Original journal/transaction reference, reason, approval/evidence. | Original posted; not already fully reversed unless partial policy; period eligibility; exact source lineage. | Creates opposite JournalLines referencing original. | Reverses original account/Fund/Rekening impact without deletion. | Inherits original Fund dimensions; no reclassification through reversal. |
| ADJ — Adjustment | Reason code, issue/exception, evidence, dimensions, approval. | No adequate simple reversal; policy/approval threshold; Open Period; journal balances. | Creates transparent correcting JournalLines. | Changes balances only as explicitly documented. | Restricted Fund adjustment requires policy-specific review. |
| CLS — Closing marker | Period, checklist, control evidence. | Full Phase 3 process; Phase 2 reserves schema/state only. | No ordinary financial journal in Phase 2 unless approved closing policy later requires one. | No direct Fund/account movement by status change. | N/A; blocks future ordinary posting by Period state. |

## 8.3 Non-posting and deferred rule families

| Type | Why not an ordinary Phase 2 posting | Treatment |
|---|---|---|
| BGT — Budget allocation | It is a plan, not asset movement or Fund ownership change. | Reserve entity/rule references only; no GL effect. |
| IFT — Interfund transfer/reclassification | Changes Fund ownership and needs complete restriction/approval matrix. | Do not activate as routine Phase 2 capability; design/defer to Phase 3. |
| REC — Reconciliation adjustment | Requires statement matching and governed closing/reopen workflow. | Reserve reconciliation entities; activation Phase 3. |
| ACC — Accrual/deferral | Requires agreed accrual policy and close mechanics. | Defer to Phase 3/4 unless separately approved. |

## 8.4 Rule-to-entity dependencies

| Rule type | Required masters | Required facts | Required controls |
|---|---|---|---|
| RCV | Account, Fund, FinancialAccount, Category, Counterparty optional. | Transaction + split + evidence. | Period, idempotency, voucher, posting rule. |
| PAY | Account, Fund, FinancialAccount, Program/CostCenter if required, Counterparty. | Transaction + split + evidence + approval. | Restriction policy, period, idempotency, voucher. |
| TRF | Two FinancialAccounts, liquidity Account, Fund. | Transaction + split + transfer proof. | Period, idempotency, voucher, balance/fund tie-out. |
| OPB | Account, Fund, FinancialAccount, Period. | Opening batch/lines + mapping/evidence. | Approved MappingSet, sequence, cutover charter. |
| REV | Original Journal/lines and all referenced dimensions. | Reversal transaction + reason/evidence. | Original-line lineage, idempotency, period. |
| ADJ | Account, Fund/FinancialAccount as applicable, ReasonCode. | Adjustment transaction + ExceptionCase/evidence. | Approval matrix, period, audit. |

---

# BAB 9 — General Ledger Design

## 9.1 How the ledger works

The General Ledger is the ordered financial consequence of posted JournalLines. The Ledger query includes only JournalLines whose Journal is Posted, never Draft, rejected, failed, or cancelled source records. Every Ledger entry retains the original accounting date, immutable posting sequence, journal line number, source transaction, voucher, rule version, and all available dimensions.

## 9.2 Ledger ordering

```text
Primary order: Accounting date ascending
Secondary order: Immutable posting sequence ascending
Tertiary order: Journal line number ascending

This ordering makes running balance deterministic even when an eligible back-dated
transaction is posted later than another transaction on the same or later date.
```

Back-dated posting must still obey period policy. A newly posted older accounting date changes subsequent derived running balances; therefore every report and projection stores an `as_of_posting_sequence` watermark for reproducibility.

## 9.3 Running balance

For a selected Account and optional dimensional filter, running balance is derived by cumulatively applying each line using the Account normal balance:

| Account normal balance | Increase effect | Signed amount concept |
|---|---|---|
| Debit | Debit increases; Credit decreases. | debit amount minus credit amount. |
| Credit | Credit increases; Debit decreases. | credit amount minus debit amount. |

The running balance is not a manually maintained field on Account, Fund, Program, or FinancialAccount. A derived BalanceProjection may cache the result through a known posting-sequence watermark and must be rebuildable.

## 9.4 Rekening balance calculation

1. Select posted JournalLines whose Account is liquidity-classified and whose `financial_account_id` equals the target Rekening.
2. Apply debit/credit using the normal balance of the liquidity Account.
3. Include all applicable Funds, because a single Rekening may contain many Funds.
4. Tie out the Rekening total to the sum of the same ledger lines grouped by Fund.
5. Compare to bank statement/cash count during reconciliation; statement difference is not silently written into the ledger.

## 9.5 Fund balance calculation

Fund balance is calculated from every posted JournalLine carrying the Fund dimension across all Accounts, according to the approved accounting policy and Account normal balance. It is not calculated by reading one BankAccount or by subtracting Program budget. Fund reports must be able to show both:

- Fund net position; and
- Fund liquidity distribution by FinancialAccount.

## 9.6 Trial balance calculation

```text
Posted JournalLines in selected period/filter
             ↓ group by Account
Debit total, Credit total, normal-balance closing amount
             ↓
sum all Accounts
Total debit must equal total credit
             ↓
drill down to JournalLine, Journal, Transaction, Voucher, Evidence
```

The TrialBalanceSnapshot stores filter signature and posting-sequence watermark, not independent financial totals that can be manually changed.

## 9.7 Reporting design

| Report | Grain | Source | Mandatory drill-down |
|---|---|---|---|
| General ledger detail | JournalLine | Posted GL | Journal, transaction, voucher, attachment/evidence. |
| Trial balance | Account | Posted GL grouped by Account | Account → journal lines. |
| Rekening position | FinancialAccount × Fund | Posted liquidity lines | Fund composition → journal lines. |
| Fund position | Fund × Account/FinancialAccount | Posted GL | Account/rekening distribution → journal lines. |
| Transaction register | Transaction | FinancialTransaction + Journal link | Split, approval, voucher, journal, evidence. |
| Exception register | ExceptionCase | Exception and linked target | Event history, evidence, decision. |

---

# BAB 10 — Constraint Catalogue

## 10.1 Identity, master, and referential constraints

| ID | Constraint |
|---|---|
| C-001 | Every entity has immutable unique identity and AccountingEntity scope. |
| C-002 | Master codes are unique inside their defined scope and never silently reused. |
| C-003 | AccountGroup and CostCenter hierarchies cannot cycle. |
| C-004 | Account normal balance is mandatory and cannot be changed after posting without controlled supersession. |
| C-005 | Account marked non-posting cannot appear on JournalLine. |
| C-006 | A liquidity Account requires FinancialAccount dimension on relevant JournalLine. |
| C-007 | A Fund, Account, Program, FinancialAccount, or Category must be active/effective on accounting date. |
| C-008 | FinancialAccount type requires exactly one compatible detail record. |
| C-009 | Fund and Program do not own stored balances. |
| C-010 | A dimension requirement is enforced as Required, Optional, or Forbidden per active AccountDimensionRule. |

## 10.2 Transaction, voucher, and evidence constraints

| ID | Constraint |
|---|---|
| C-011 | FinancialTransaction gross amount is positive. |
| C-012 | Active TransactionSplits total exactly to FinancialTransaction gross amount. |
| C-013 | Split line numbering is unique within a transaction. |
| C-014 | Transaction cannot self-reference as reversal target. |
| C-015 | RCV/PAY/TRF/OPB/REV/ADJ must possess the fields and evidence mandated by its rule. |
| C-016 | A TransactionType with financial impact cannot be Posted without a committed PostingAttempt. |
| C-017 | Voucher number is unique within its approved scope. |
| C-018 | Issued Voucher is immutable and not reassigned. |
| C-019 | Void/gap in voucher numbering retains reason and audit evidence. |
| C-020 | Attachment content hash and storage reference protect evidence integrity. |

## 10.3 Journal and ledger constraints

| ID | Constraint |
|---|---|
| C-021 | Posted Journal contains at least two JournalLines. |
| C-022 | A JournalLine has exactly one positive side: debit or credit. |
| C-023 | Sum debit equals sum credit for every Posted Journal. |
| C-024 | JournalLine numbers are unique within a Journal. |
| C-025 | Posted Journal cannot be edited, deleted, or posted again. |
| C-026 | Posted JournalLine dimensions and amounts are immutable. |
| C-027 | LedgerEntry has exactly one Posted JournalLine source. |
| C-028 | LedgerEntry cannot be entered or changed independently. |
| C-029 | Posting sequence is unique and immutable within AccountingEntity. |
| C-030 | BalanceProjection can only be derived/rebuilt from Posted GL and never receives manual adjustments. |

## 10.4 Fund, transfer, period, and correction constraints

| ID | Constraint |
|---|---|
| C-031 | Fund restriction policy must be valid on accounting date for restricted Fund effects. |
| C-032 | Fund negative balance is blocked unless the Fund policy and authorised exception explicitly permit it. |
| C-033 | TRF source and destination FinancialAccount cannot be the same. |
| C-034 | TRF must preserve total organisation liquidity and each transferred Fund composition. |
| C-035 | TRF cannot be classified as ordinary revenue or expense. |
| C-036 | OPB requires approved batch, evidence package, and one active scope at cutover. |
| C-037 | REV requires original posted journal lineage and cannot delete original evidence. |
| C-038 | ADJ requires reason code, explanation, evidence, and approval threshold. |
| C-039 | Ordinary Phase 2 posting requires an Open Period. |
| C-040 | HardClosed Period prohibits ordinary posting; Reopen requires explicit closing governance. |

## 10.5 Audit, exception, and projection constraints

| ID | Constraint |
|---|---|
| C-041 | All material transaction, posting, voucher, evidence, master, and exception transitions emit AuditEvent. |
| C-042 | AuditEvent is append-only and correlated to request/transaction/posting identity. |
| C-043 | ExceptionCase does not grant posting permission by itself. |
| C-044 | Critical/High ExceptionCase requires owner and due date unless resolved. |
| C-045 | Failed posting cannot leave Journal, Voucher, or Ledger effect committed. |
| C-046 | Recovered/retried posting first resolves IdempotencyKey status. |
| C-047 | TrialBalanceSnapshot must store source watermark and tie out to GL. |
| C-048 | Derived data failure marks projection stale/failed but does not change accounting facts. |

---

# BAB 11 — Validation Catalogue

## 11.1 Business validation

| ID | Validation | Failure response |
|---|---|---|
| V-B01 | TransactionType is active and eligible. | Reject submission/posting. |
| V-B02 | Required narrative, counterparty, category, purpose, and source reference are present by rule. | Return actionable validation error. |
| V-B03 | Required attachments are Active and correctly linked. | Block unless documented exception policy permits workflow. |
| V-B04 | Approval decisions meet the active limit/matrix. | Block posting. |
| V-B05 | ReasonCode is valid for REV/ADJ/void/reopen/exception action. | Block action. |
| V-B06 | Historical correction references existing original transaction/journal. | Block action. |

## 11.2 Accounting validation

| ID | Validation | Failure response |
|---|---|---|
| V-A01 | Every required Account and normal balance is valid. | Reject posting. |
| V-A02 | Exactly one debit or credit amount appears per JournalLine. | Reject posting. |
| V-A03 | Journal debit total equals credit total exactly to currency precision. | Reject posting. |
| V-A04 | TransactionSplit total equals gross amount exactly. | Reject submission/posting. |
| V-A05 | Proposed Journal uses only posting Accounts. | Reject posting. |
| V-A06 | Rule version is effective on accounting date. | Reject posting; require rule resolution. |
| V-A07 | All Journal dimensions satisfy AccountDimensionRule. | Reject posting. |
| V-A08 | Posting date belongs to an Open Period. | Reject posting. |

## 11.3 Fund and Program validation

| ID | Validation | Failure response |
|---|---|---|
| V-F01 | Fund exists, is Active, and is effective on accounting date. | Reject posting. |
| V-F02 | Fund restriction/policy version permits transaction purpose and type. | Reject or create authorised review requirement. |
| V-F03 | Fund negative balance policy is not breached after proposed effect. | Block or route to governed exception. |
| V-F04 | Restricted Fund policy reference is saved on affected JournalLine. | Reject posting. |
| V-P01 | Program is active if supplied/required. | Reject posting. |
| V-P02 | Program does not substitute for absent required Fund. | Reject posting. |
| V-P03 | CostCenter is compatible with Program where a relationship exists. | Reject posting. |

## 11.4 Transfer, closing, and recovery validation

| ID | Validation | Failure response |
|---|---|---|
| V-T01 | TRF source and destination FinancialAccount differ and are active. | Reject posting. |
| V-T02 | TRF Fund composition is equal before and after physical movement. | Reject posting. |
| V-T03 | TRF has no revenue/expense account effect unless a separately approved transaction exists. | Reject posting. |
| V-C01 | Period status permits requested transition/posting. | Reject action. |
| V-C02 | Opening batch is approved and has required evidence before OPB posting. | Reject posting. |
| V-C03 | No unresolved critical close blocker exists before hard-close status. | Block closing. |
| V-R01 | Idempotency key/fingerprint either represents this exact request or an existing result is returned. | Return conflict or prior result. |
| V-R02 | PostingAttempt is not already committed by another worker. | Return existing result/wait/recover. |

---

# BAB 12 — Concurrency Strategy

## 12.1 Concurrency risks

| Risk | Example | Required prevention |
|---|---|---|
| Double submit | Operator clicks Post twice or network retries request. | IdempotencyKey with request fingerprint and unique scope. |
| Concurrent approval/post | Approval is withdrawn while posting starts. | Lock/check source state and approval version at commit. |
| Voucher race | Two postings request the same next number. | Atomic sequence allocation inside posting commitment. |
| Period-close race | A posting starts while a Period becomes closed. | Lock/check Period status at commit boundary. |
| Fund balance race | Two payments each pass pre-check but together breach policy. | Serialize/lock balance-sensitive scope or use a controlled atomic conditional check. |
| Reversal race | Two users reverse the same original Journal. | Lock/recheck reversal relationship and unique reversal constraint. |
| Recovery race | Timed-out client retries while original attempt may commit. | Query IdempotencyKey/PostingAttempt before doing anything new. |
| Projection race | Projection rebuild happens while new journals post. | Build to a known posting-sequence watermark; mark stale then catch up/rebuild. |

## 12.2 Locking strategy by resource

| Resource | Strategy | Reason |
|---|---|---|
| FinancialTransaction state | Optimistic version check plus short exclusive lock at posting start. | Prevent competing state transitions without long contention. |
| IdempotencyKey | Unique reservation and exclusive read/write of that key. | Guarantees one official result per identity. |
| DocumentSequence | Short pessimistic/atomic increment on one sequence scope. | Eliminates duplicate voucher allocation. |
| AccountingPeriod | Read and revalidate under posting commit; stronger lock during close transition. | Prevents posting into newly closed period. |
| Fund balance-sensitive scope | Pessimistic or serialised scope only when policy enforces no negative balance. | Avoids overspend due concurrent checks. |
| Original Journal for REV | Lock/reference uniqueness check. | Prevents unintended double reversal. |
| BalanceProjection | No lock required for financial truth; use watermark/version compare. | Projection is derived and rebuildable. |

## 12.3 Isolation and retry policy

- The posting commitment must provide read consistency for the source transaction, approvals, period, rule version, sequence, and idempotency record.
- A retry is safe only when it reuses the same idempotency key and exact request fingerprint.
- A retry encountering a live attempt waits a bounded time or returns a recoverable “in progress” result; it must not start a second financial posting.
- A deadlock or transient conflict can be retried internally only before an official result is committed, with bounded attempts and audit correlation retained.
- A commit outcome that is unknown to the caller is resolved by reading the IdempotencyKey and PostingAttempt. The system never assumes failure merely because a client timed out.
- Long-running evidence scans, report projection, notification, and file processing are outside the accounting commitment. Their failure cannot duplicate or roll back a committed Journal.

## 12.4 Sequence diagram — safe posting

```text
Client        Transaction Store      Posting Engine      Sequence / GL          Audit
  | submit(key,fingerprint) |              |                   |                 |
  |------------------------>|              |                   |                 |
  |                         | lock/read    |                   |                 |
  |                         |------------->| reserve key       |                 |
  |                         |              |------------------>|                 |
  |                         |              | validate/rules    |                 |
  |                         |              | allocate number   |                 |
  |                         |              |------------------>|                 |
  |                         |              | commit journal    |                 |
  |                         |              |------------------>| emit event      |
  |                         |              |----------------------------------->|
  |<------------------------| posted result + voucher + journal reference       |
```

---

# BAB 13 — Exception Handling

## 13.1 Handling principles

1. Reject safely before financial commit whenever a rule is not satisfied.
2. Preserve a clear machine-readable failure code and human-readable explanation.
3. Separate technical failure from financial exception requiring governance.
4. Never resolve an exception by editing posted data or manually changing a balance projection.
5. Keep retry/recovery correlation and evidence so an uncertain outcome can be proven.

## 13.2 Exception decision table

| Condition | Classification | Immediate behaviour | Can it post? | Resolution path |
|---|---|---|---:|---|
| Insufficient permitted Fund balance | Business/Fund exception. | Block transaction and calculate affected Fund/amount. | No, unless policy-approved exception exists. | ExceptionCase + authorised approval; otherwise change funding/amount. |
| Invalid or inactive Fund | Master data validation. | Reject. | No. | Correct selection or activate valid Fund through governance. |
| Restricted Fund purpose prohibited | Policy breach. | Reject and record policy reference. | No. | Select permitted Fund/purpose; exceptional override only if policy explicitly allows. |
| Period closed | Period control breach. | Reject. | No. | Use correct Open Period or controlled reopen process. |
| Voucher duplicate | Concurrency/control breach. | Return existing result for same key; reject conflicting request. | No duplicate effect. | Resolve sequence/idempotency record. |
| Posting failure before commit | Technical/recoverable. | Roll back accounting commitment; mark attempt Failed. | No. | Fix cause and retry with same key. |
| Unknown commit outcome | Recovery exception. | Do not retry blindly. | Not until result resolved. | Inspect IdempotencyKey and PostingAttempt. |
| Ledger imbalance | Critical accounting defect. | Reject/contain posting path. | No. | Investigate rule/data; no go-live with open defect. |
| Transfer imbalance | Critical accounting/Fund defect. | Reject. | No. | Correct split/source/destination. |
| Reversal fails | Correction/recovery exception. | Preserve original; no partial reversal. | No partial effect. | Resolve original status and retry governed reversal. |
| Evidence missing after posting | Governance exception. | Do not alter Journal. | Already posted remains. | ExceptionCase, evidence remediation, audit trail. |

## 13.3 Failure codes minimum set

| Code | Meaning | Severity |
|---|---|---|
| E-PERIOD-CLOSED | Accounting period is not eligible. | Block |
| E-IDEMPOTENCY-CONFLICT | Same key was supplied with different request data. | Block/security review |
| E-DUPLICATE-POSTING | An official posting already exists for source identity. | Block/return result |
| E-JOURNAL-UNBALANCED | Proposed debit and credit totals differ. | Critical block |
| E-SPLIT-UNBALANCED | Transaction splits do not equal gross amount. | Block |
| E-FUND-RESTRICTED | Fund policy forbids the intended use. | Block |
| E-FUND-INSUFFICIENT | Proposed impact breaches Fund balance policy. | Block/review |
| E-DIMENSION-REQUIRED | Required Account dimension is absent. | Block |
| E-MASTER-INACTIVE | Referenced master is inactive/not effective. | Block |
| E-VOUCHER-CONFLICT | Voucher allocation conflict occurred. | Recoverable block |
| E-POSTING-RECOVERY | Attempt outcome needs recovery before retry. | Hold |
| E-REVERSAL-CONFLICT | Original Journal has incompatible reversal status. | Block |
| E-OPENING-EVIDENCE | Opening position lacks approved evidence/mapping. | Critical block |

---

# BAB 14 — Performance Strategy

## 14.1 Principle

Performance mechanisms must be derived from and verifiable against posted GL. No cache, summary, dashboard aggregate, or running-balance store may become a mutable financial source.

## 14.2 Query and indexing strategy

| Access pattern | Required logical indexes / access keys | Notes |
|---|---|---|
| Journal lookup | AccountingEntity + Journal ID; Transaction ID; Voucher number. | Supports drill-down and audit. |
| Ledger by Account/date | AccountingEntity + Account + accounting date + posting sequence + line number. | Core ledger/running balance order. |
| Ledger by Fund | AccountingEntity + Fund + accounting date + posting sequence. | Supports Fund reporting and restriction review. |
| Rekening balance | AccountingEntity + FinancialAccount + Account + date/sequence. | Supports Fund composition within Rekening. |
| Program/CostCenter report | AccountingEntity + Program/CostCenter + Account + date/sequence. | Management reporting only; not Fund balance substitute. |
| Period close | AccountingEntity + Period + Journal status; accounting date. | Fast eligibility/close checks. |
| Idempotency/recovery | AccountingEntity + scope + key; status; transaction. | Unique active scope is mandatory. |
| Voucher | AccountingEntity + voucher number; sequence scope. | Supports duplicate prevention and inquiry. |
| Exception aging | AccountingEntity + status + severity + due date. | Supports governance SLA. |

## 14.3 Projection and caching strategy

| Derived object | Grain | Refresh rule | Verification |
|---|---|---|---|
| BalanceProjection — Account | Account / date / sequence watermark. | Incremental after commit or rebuild. | Tie to grouped GL. |
| BalanceProjection — FinancialAccount × Fund | Rekening × Fund / date / watermark. | Incremental or rebuild. | Sum Funds = Rekening GL total. |
| BalanceProjection — Fund | Fund / date / watermark. | Incremental or rebuild. | Tie to Fund GL query. |
| TrialBalanceSnapshot | Period/filter/watermark. | On demand or certified close. | Debit = credit and line drill-down. |
| Report cache | Report parameters + watermark. | Invalidate/stale when newer posting sequence exists. | Regenerate from GL. |

## 14.4 Running-balance strategy

- Use deterministic ledger order, not report display order alone.
- Query a bounded date range with carry-forward opening balance derived from a prior verified projection or direct GL aggregate.
- Persist only the watermark and derived result necessary for performance; never permit a user to “correct” it.
- Back-dated posting makes affected later projections stale. Rebuild from the earliest affected accounting date or an earlier verified checkpoint.
- Large reports should paginate detail and use aggregate query/projection for summary, with drill-down returning source JournalLines.

## 14.5 Scalability and retention

- Partition/archive strategy must preserve AccountingEntity, accounting date, posting sequence, and all audit lineage.
- Attachments are retained through content references and integrity hash; reporting does not load file content unless requested.
- Data retention may archive old operational drafts, but posted Journals, Ledger lineage, voucher, audit, evidence links, and policy references remain retrievable under policy.
- Additional Funds, FinancialAccounts, Programs, and historical years must increase data volume without changing the balance definition or requiring a new posting rule code path for each master record.

---

# BAB 15 — Acceptance Criteria

The following 120 acceptance criteria form the minimum basis for unit-level rule tests, integration tests, UAT, migration rehearsal, and regression protection. “Must” means the criterion is non-optional for Phase 2 scope.

## 15.1 Master, calendar, and dimension criteria (AC-001 to AC-012)

| ID | Acceptance criterion |
|---|---|
| AC-001 | The system must scope every financial entity to exactly one AccountingEntity. |
| AC-002 | An AccountGroup hierarchy must reject any cyclic parent relationship. |
| AC-003 | An Account must require a valid normal balance before activation. |
| AC-004 | An Account that has posted history must not be deleted. |
| AC-005 | An inactive Account must be rejected for a new JournalLine. |
| AC-006 | A non-posting Account must be rejected for a JournalLine. |
| AC-007 | A liquidity Account must require FinancialAccount dimension when AccountDimensionRule says Required. |
| AC-008 | A Fund must not be activated without FundType, FundRestriction, and purpose statement. |
| AC-009 | A high/critical restriction Fund must retain an effective FundPolicyVersion before it can be used. |
| AC-010 | A Program must not create or store a Fund balance. |
| AC-011 | A closed FinancialAccount must reject accounting dates after its closing date. |
| AC-012 | A date must belong to at most one active AccountingPeriod for an AccountingEntity. |

## 15.2 Transaction and split criteria (AC-013 to AC-024)

| ID | Acceptance criterion |
|---|---|
| AC-013 | A Draft transaction must have no Journal, Voucher, LedgerEntry, or official balance effect. |
| AC-014 | A transaction must reject a non-positive gross amount. |
| AC-015 | A transaction split must reject a non-positive split amount. |
| AC-016 | Sum of active TransactionSplits must equal gross amount exactly before submission. |
| AC-017 | Split line numbers must be unique within a transaction. |
| AC-018 | A required Fund must be present on a split when the resolved AccountDimensionRule requires it. |
| AC-019 | A forbidden Program/Counterparty/FinancialAccount dimension must be rejected when rule marks it Forbidden. |
| AC-020 | A transaction cannot reference itself as related reversal transaction. |
| AC-021 | RCV must require receiving FinancialAccount and evidence defined by its active rule. |
| AC-022 | PAY must require purpose, Fund, payment FinancialAccount, and counterparty/evidence when the rule requires them. |
| AC-023 | TRF must require distinct source and destination FinancialAccounts. |
| AC-024 | OPB must require an approved OpeningBalanceBatch and evidence reference. |

## 15.3 Approval, evidence, voucher, and audit criteria (AC-025 to AC-036)

| ID | Acceptance criterion |
|---|---|
| AC-025 | A transaction must not transition to Approved until all required approval steps are Approved. |
| AC-026 | A rejected approval must prevent posting until a new valid workflow is completed. |
| AC-027 | A required approval comment must be present for rejection or override. |
| AC-028 | An attachment must retain content hash, storage reference, media type, and byte size. |
| AC-029 | An attachment marked Rejected or Superseded must not satisfy mandatory-evidence validation. |
| AC-030 | A posted transaction must be traceable to all mandatory AttachmentLinks. |
| AC-031 | An issued Voucher must have exactly one source FinancialTransaction. |
| AC-032 | Voucher number must be unique in its approved sequence scope. |
| AC-033 | A voided Voucher must retain void reason and audit evidence. |
| AC-034 | Every posting must create correlated audit events for initiation and completion/failure. |
| AC-035 | A posted evidence-link change must create an audit event and preserve prior linkage status. |
| AC-036 | An AuditEvent must not be editable or deletable through normal operations. |

## 15.4 Posting and journal criteria (AC-037 to AC-048)

| ID | Acceptance criterion |
|---|---|
| AC-037 | Only an Approved transaction may enter Posting. |
| AC-038 | Posting must resolve one effective PostingRuleVersion by transaction type and accounting date. |
| AC-039 | Posting must reject a transaction whose accounting date is outside an Open Period. |
| AC-040 | A proposed Journal must contain at least two JournalLines. |
| AC-041 | Every JournalLine must contain exactly one positive amount side: debit or credit. |
| AC-042 | A Journal must be rejected if total debit and credit differ at currency precision. |
| AC-043 | A successful posting must atomically create Journal, JournalLines, Voucher, PostingAttempt result, and audit event. |
| AC-044 | A failed posting before commit must create no official Journal, Voucher, LedgerEntry, or balance effect. |
| AC-045 | A Posted Journal must have unique immutable posting sequence. |
| AC-046 | A Posted Journal must retain the exact PostingRuleVersion used. |
| AC-047 | A Posted Journal must be immutable to direct amount, date, Account, Fund, and FinancialAccount changes. |
| AC-048 | A Posted Journal must not be deleted. |

## 15.5 Ledger, running balance, and reports criteria (AC-049 to AC-060)

| ID | Acceptance criterion |
|---|---|
| AC-049 | A LedgerEntry must be produced only from one Posted JournalLine. |
| AC-050 | A LedgerEntry must not be independently created, modified, or deleted. |
| AC-051 | Ledger query must use accounting date, posting sequence, and line number for deterministic order. |
| AC-052 | Debit-normal Account running balance must increase on debit and decrease on credit. |
| AC-053 | Credit-normal Account running balance must increase on credit and decrease on debit. |
| AC-054 | Rekening balance must be calculated only from posted liquidity lines for that FinancialAccount. |
| AC-055 | Sum of Fund composition within a FinancialAccount must tie to its ledger balance. |
| AC-056 | Fund balance must be derived from all posted Fund-attributed lines, not from one Rekening or Program. |
| AC-057 | Trial balance total debit must equal total credit for every report watermark. |
| AC-058 | Trial balance must drill down from Account to JournalLines. |
| AC-059 | A report cache/projection must show source posting-sequence watermark. |
| AC-060 | Rebuilding a projection from GL must reproduce the same result for the same watermark. |

## 15.6 Receipt, payment, and Fund criteria (AC-061 to AC-072)

| ID | Acceptance criterion |
|---|---|
| AC-061 | A valid RCV must increase the receiving liquidity position and affect only the resolved Fund allocation. |
| AC-062 | A receipt with two Funds must create a balanced split whose sum equals the receipt amount. |
| AC-063 | A receipt with unclear donor intent must follow the approved unidentified-receipt policy and must not be spent before resolution. |
| AC-064 | A valid PAY must decrease the paying liquidity position and create its approved expense/asset/distribution effect. |
| AC-065 | PAY must be rejected when selected Fund policy prohibits its purpose or Program. |
| AC-066 | Program attribution must not permit a transaction that Fund policy prohibits. |
| AC-067 | A required restricted-Fund policy version must be retained on the affected posting. |
| AC-068 | A Fund balance policy breach must block posting unless an approved exception exists. |
| AC-069 | A valid exception must retain its reason, approver, evidence, and expiry/decision conditions. |
| AC-070 | Fund must not become negative when its policy disallows negative balance. |
| AC-071 | An inactive or closed Fund must be rejected for new ordinary posting. |
| AC-072 | Fund balance report must distinguish net Fund position from Fund liquidity distribution. |

## 15.7 Transfer, opening balance, reversal, and adjustment criteria (AC-073 to AC-084)

| ID | Acceptance criterion |
|---|---|
| AC-073 | TRF must not create ordinary revenue or expense effects. |
| AC-074 | TRF must preserve total organisational liquidity across source and destination. |
| AC-075 | TRF must preserve each transferred Fund composition. |
| AC-076 | TRF must reject identical source and destination FinancialAccounts. |
| AC-077 | OpeningBalanceBatch must reject posting until MappingSet is approved/frozen and evidence package is attached. |
| AC-078 | OPB lines must balance as a batch Journal. |
| AC-079 | Opening balance must tie to approved bank statement/cash-count and Fund composition evidence. |
| AC-080 | A posted OPB batch must not be edited. |
| AC-081 | REV must reference an existing Posted original Journal. |
| AC-082 | REV must create opposite effect without deleting original Journal or evidence. |
| AC-083 | Duplicate full reversal must be rejected unless policy explicitly supports a distinct partial-reversal case. |
| AC-084 | ADJ must require ReasonCode, explanation, evidence, and policy-level approval. |

## 15.8 Period, closing, and reconciliation-reserved criteria (AC-085 to AC-096)

| ID | Acceptance criterion |
|---|---|
| AC-085 | A Future Period must reject ordinary posting. |
| AC-086 | An Open Period must allow eligible ordinary posting. |
| AC-087 | SoftClosed and HardClosed Periods must reject ordinary Phase 2 posting. |
| AC-088 | Reopen transition must require ReasonCode, note, actor, and audit evidence. |
| AC-089 | One active ClosingRun must exist at most once for a Period. |
| AC-090 | A ClosingRun cannot Complete when a declared mandatory checklist item is unresolved. |
| AC-091 | Reconciliation must link to one FinancialAccount and one Period. |
| AC-092 | ReconciliationItem must preserve source, amount, and match status. |
| AC-093 | A material unmatched reconciliation item must create or link an ExceptionCase. |
| AC-094 | Reconciliation entity activation must not alter ledger balances by itself. |
| AC-095 | Closing/reconciliation reports must use posted GL watermark. |
| AC-096 | Full automated reconciliation and hard-close workflow must remain feature-gated until Phase 3 acceptance. |

## 15.9 Concurrency, idempotency, and recovery criteria (AC-097 to AC-108)

| ID | Acceptance criterion |
|---|---|
| AC-097 | Same idempotency key and same fingerprint must return the existing committed result without a new posting. |
| AC-098 | Same idempotency key and different fingerprint must be rejected as conflict. |
| AC-099 | Concurrent attempts to post one transaction must yield at most one Posted Journal. |
| AC-100 | Concurrent Voucher allocation must yield no duplicate voucher number. |
| AC-101 | A retry after client timeout must resolve PostingAttempt/IdempotencyKey before creating any new effect. |
| AC-102 | A failed attempt must retain diagnostic failure code and correlation identity. |
| AC-103 | An unknown result status must enter RecoveryRequired rather than assume failure. |
| AC-104 | A period-close race must recheck Period eligibility at posting commit boundary. |
| AC-105 | A Fund negative-balance race must not permit two concurrent payments to bypass policy. |
| AC-106 | A reversal race must not create two full reversals of the same original Journal. |
| AC-107 | Projection rebuild concurrent with posting must not alter authoritative Journal/ledger facts. |
| AC-108 | Every retry and recovery event must remain auditable under the original correlation identity. |

## 15.10 Migration, exception, performance, and regression criteria (AC-109 to AC-120)

| ID | Acceptance criterion |
|---|---|
| AC-109 | Each legacy source record used in opening position must have mapping status and rationale. |
| AC-110 | Ambiguous legacy Fund classification must become an ExceptionCase rather than silently map to general Fund. |
| AC-111 | A Confirmed mapping must point to valid target master records. |
| AC-112 | Opening cutover must reject unresolved material exceptions without explicit go/no-go approval. |
| AC-113 | Legacy archive references must remain available for audit after V2 go-live. |
| AC-114 | ExceptionCase must retain owner, severity, state, target reference, and history. |
| AC-115 | An ExceptionCase alone must not create a Journal or change a balance. |
| AC-116 | Account/Fund/Rekening ledger queries must return results in deterministic order at scale. |
| AC-117 | A stale projection must be identifiable and must not be represented as certified balance. |
| AC-118 | Incremental projection result must tie to full rebuild for the same watermark. |
| AC-119 | Regression tests must re-run all AC-001 through AC-118 for changes to rule, master, posting, projection, or migration behaviour. |
| AC-120 | No Phase 2 release is accepted if an alternate legacy or summary source can modify an official reported balance. |

---

# BAB 16 — Dependency Matrix

## 16.1 Build dependency order

```text
AccountingEntity / Calendar / Period
              + AccountGroup / Account / Dimension Rules
              + FundType / Restriction / Fund / Policy Version
              + FinancialAccount / Program / CostCenter / Category / Reason
                                      ↓
            TransactionType / BusinessRule / PostingRule / Sequence
                                      ↓
           FinancialTransaction / Split / Attachment / Approval
                                      ↓
           IdempotencyKey / PostingAttempt / Voucher allocation
                                      ↓
                   Journal / JournalLine / AuditEvent
                                      ↓
          LedgerEntry / BalanceProjection / TrialBalanceSnapshot
                                      ↓
      MappingSet / OpeningBalance / Reconciliation reserve / Cutover
```

## 16.2 Entity dependency matrix

| Entity/capability | Must exist first | Enables | Cannot be accepted without |
|---|---|---|---|
| AccountingEntity | None | Every scoped entity. | Functional currency and calendar policy. |
| Calendar and Period | AccountingEntity. | Accounting-date validation. | Non-overlap and lifecycle controls. |
| AccountGroup/Account | AccountingEntity. | JournalLine classification/reporting. | Normal balance and posting eligibility. |
| AccountDimensionRule | Account. | Dimension validation. | Effective-date compatibility. |
| Fund master/policy | FundType, FundRestriction. | Restricted accounting/control. | Policy version and purpose. |
| FinancialAccount details | FinancialAccount + liquidity Account. | Cash/bank receipt, payment, transfer. | Valid subtype and custodian. |
| Program/CostCenter | AccountingEntity. | Management attribution. | Non-substitution for Fund. |
| TransactionType/BusinessRule | AccountingEntity. | Rule catalogue. | Effective statuses and traceability. |
| PostingRuleVersion | TransactionType, BusinessRule, CoA/policy. | Journal construction. | Input/dimension/rule references. |
| FinancialTransaction/Split | Masters, Period, TransactionType. | Approval/posting. | Balanced split and data/evidence validation. |
| Approval/Attachment | Transaction. | Policy-compliant posting. | Required-evidence/approval status. |
| Idempotency/PostingAttempt | Transaction. | Safe posting/retry. | Conflict/recovery controls. |
| DocumentSequence/Voucher | TransactionType, AccountingEntity. | Human traceability. | Atomic uniqueness. |
| Journal/JournalLine | Approved transaction, rule, period, posting attempt. | Official accounting effects. | Balanced immutable facts. |
| Ledger/projections | Posted JournalLine. | Reports, balances, performance. | Rebuild/tie-out proof. |
| Mapping/OpeningBalance | Masters, Period, Journal, evidence. | Cutover position. | Approved evidence/reconciliation. |
| Closing/Reconciliation reserve | Period, FinancialAccount, GL. | Phase 3 controls. | No premature financial impact. |

## 16.3 Implementation sequence by work package

| Package | Scope | Predecessor | Exit gate |
|---|---|---|---|
| WP-01 | Entity scope, calendar, period, CoA, Fund, FinancialAccount masters. | Policy decisions D-01 to D-06. | Master readiness. |
| WP-02 | Rule registry, dimension rules, transaction types, reason codes, sequence design. | WP-01. | Rule readiness. |
| WP-03 | Transaction/split/evidence/approval contracts. | WP-01, WP-02. | Intake validation scenarios pass. |
| WP-04 | Idempotency, PostingAttempt, voucher allocation, posting state control. | WP-03. | Concurrency design tests pass. |
| WP-05 | Journal/JournalLine/AuditEvent and core posting. | WP-02, WP-04. | Accounting invariants pass. |
| WP-06 | Ledger entry, balances, trial balance, inquiry/report contracts. | WP-05. | GL tie-outs pass. |
| WP-07 | REV/ADJ/OPB and exception integration. | WP-05, WP-06. | Correction/opening scenarios pass. |
| WP-08 | Mapping, opening balance rehearsal, archive trace. | WP-01 to WP-07. | Cutover rehearsal pass. |
| WP-09 | UAT, performance verification, runbook, go-live readiness. | WP-01 to WP-08. | G4 decision. |

---

# BAB 17 — Implementation Readiness

## 17.1 Readiness checklist

The checklist below is a gate to begin implementation work. “Ready” requires evidence, not verbal confirmation.

| Area | Ready when | Evidence |
|---|---|---|
| Policy baseline | Manual, architecture, and blueprint are versioned; all relevant business decisions are ratified. | Approved decision log. |
| Architecture recommendations | AR-01 through AR-07 are approved, replaced, or explicitly deferred with scope impact. | Architecture decision record. |
| Database design | Every entity, field, key, relationship, lifecycle, and constraint in this document has an approved physical implementation mapping. | Reviewed data model and constraint traceability. |
| Migration design | No migration is written until mapping from this field specification to implementation artefacts is reviewed. | Migration design review checklist. |
| Reference/master data | CoA, Fund, FinancialAccount, Period, Program, Category, ReasonCode, and TransactionType data are complete enough for test scenarios. | Master-data workbook and sign-off. |
| Seed/reference setup | Controlled initial records have source, owner, effective date, and reconciliation rules. | Approved reference-data package. |
| Domain models | Model boundaries mirror entity ownership and source-of-truth classification. | Domain mapping review. |
| Persistence/repository layer | Persistence interfaces preserve atomicity, immutable facts, and scope isolation. | Data-access design review. |
| Posting Engine | Input contract, rule resolution, atomic boundary, locking, idempotency, recovery, and audit design are reviewed. | Posting Engine sequence/test design sign-off. |
| Application services | Orchestration boundaries do not bypass Posting Engine for financial effects. | Service-boundary review. |
| Controller/API boundary | External commands use idempotency, validation feedback, and do not expose direct balance mutation. | Interface contract review. |
| User interface | UI captures all required dimensions/evidence but does not calculate or override official balances. | UX/process validation review. |
| Test automation | AC-001 to AC-120 mapped to test levels, owners, data, and evidence. | Test traceability matrix. |
| Performance | Ledger access, projection watermarks, rebuild, and reporting queries have measurable acceptance plan. | Performance test plan. |
| Security/audit | Evidence privacy, audit retention, actor/correlation identity, and protected identifiers are designed. | Security and audit review. |
| Cutover | OpeningBalanceBatch, MappingSet, evidence packet, exception register, rehearsal, and go/no-go are planned. | Cutover readiness pack. |

## 17.2 No-code design exit criteria

Phase 2 Accounting Foundation is ready to enter implementation only if:

- all 17 chapters have been reviewed by the financial and architecture owners;
- no unresolved business ambiguity remains in RCV, PAY, TRF, OPB, REV, or ADJ treatment;
- every required entity and field has a named owner, lifecycle, relationship, and validation source;
- AR-01 through AR-07 have recorded decisions;
- all constraints and AC-001 through AC-120 are traceable to planned tests;
- no design permits a posted balance change outside the Posting Engine and posted GL;
- cutover/opening-balance approach is compatible with the approved Implementation Blueprint;
- deferred Phase 3/4 features are technically reserved without being activated as Phase 2 financial behaviour.

## 17.3 Implementation hand-off package

Before implementation begins, the delivery team must receive:

1. approved versions of the three baseline documents and this technical design;
2. architecture-decision record for every AR item;
3. master-data workbook and controlled code lists;
4. posting rule catalogue with Account/Fund/dimension mapping approved by Finance Controller;
5. field-to-implementation traceability matrix;
6. validation, exception, concurrency, and recovery test catalogue;
7. migration mapping template, opening-balance evidence template, and cutover runbook skeleton;
8. acceptance-criteria traceability matrix for unit, integration, UAT, regression, and performance tests.

---

This document is intentionally a design contract. It does not authorize business-policy changes, direct data updates, or implementation shortcuts. The first implementation artefact must preserve its source-of-truth hierarchy: approved policy → approved posting rule → Posting Engine → immutable Journal/General Ledger → rebuildable reporting projection.

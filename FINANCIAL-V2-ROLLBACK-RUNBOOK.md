# Financial V2 Rollback Runbook

**Status:** controlled procedure only — **NOT EXECUTED**  
**Rollback window:** `[TBD — requires separate authorization]`  
**Decision owner:** `[CUTOVER_AUTHORITY]`

This runbook does not claim that a database restore is available. Any database recovery step is **REQUIRES VERIFIED BACKUP/SNAPSHOT** and a tested restore procedure. It never permits deletion of legacy data, direct alteration of immutable posted V2 facts, or an ungoverned legacy resume.

## Rollback triggers

- P0 financial-integrity/data-corruption event, Ledger imbalance, or unreconcilable opening position.
- Duplicate official Journal/Voucher/Ledger facts, half-posted transaction, or material idempotency failure.
- Material schema/build mismatch or missing/invalid backup evidence.
- Unresolved Fund restriction or liquidity breach.
- Critical report/reconciliation discrepancy that cannot be contained through a governed correction.
- Cutover authority decision within the approved rollback window.

## Decision and containment

1. Detect and record incident `[INCIDENT_REFERENCE]`, correlation/idempotency references, affected scope, and timestamp.
2. Contain V2 financial input according to the approved incident authority; preserve databases, logs, audit events, reports, and legacy snapshot evidence.
3. Cutover authority, finance owner, technical owner, and independent verifier determine whether to correct with approved governed events or rollback.
4. Do not resume legacy financial input automatically. Do not erase V2 evidence.

## Application rollback

1. Confirm target build/reference `[PREVIOUS_BUILD]` and compatibility with the approved database state.
2. Deploy/revert only through the approved release process.
3. Verify application health without creating, posting, or modifying financial facts.
4. Preserve the failed-build logs and release evidence.

## Database recovery

1. **REQUIRES VERIFIED BACKUP/SNAPSHOT:** confirm snapshot identity, completion timestamp, encryption/access controls, retention, and tested restore result.
2. Obtain explicit rollback authorization before any restore.
3. Restore only through the approved database recovery procedure to the defined target; do not assume point-in-time recovery exists.
4. Record restored database identifier, time, operator, command/change ticket, and integrity verification.
5. If verified restore capability is absent, rollback readiness is **NOT READY**; stop and retain evidence rather than claiming recovery.

## Reconciliation and legacy decision

1. Reconcile restored V2 Journal/JournalLine/Posted General Ledger, vouchers, Financial Accounts, Funds, Accounts, Programs, and reports to the preserved snapshots.
2. Compare post-restore facts with final legacy snapshot and approved opening reconciliation; every difference must be explicit and approved.
3. Legacy resume, if required, needs a new written decision: define source of truth, the exact resumption point, operational owner, and audit trail. No automatic dual-write or automatic legacy unfreeze is permitted.
4. Keep legacy tables/data intact as historical archive.

## Post-rollback verification and communication

- Independent verifier confirms Trial Balance, ledger integrity, Financial Account balances, Fund positions, and reports.
- Technical owner confirms build/schema status and that no production migration/cutover action remains running.
- Finance owner signs reconciliation outcome.
- Cutover authority communicates status, residual risk, next decision point, and any required remediation to stakeholders.
- Retain the incident package, backup/restore evidence, reconciliation, and decision record.

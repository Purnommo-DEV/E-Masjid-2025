# Financial V2 Governed Master Data Register

**Status:** `PASS WITH GOVERNANCE GAPS`  
**Date:** 2026-08-09  
**Mode:** inventory and validation; no business master seeding.

## Register outcome

No actual Financial V2 business master has been onboarded. The local development database contains the V2 Foundation but no V2 entity, calendar, CoA, FinancialAccount, Fund, Program, Category, Posting Rule, Document Sequence, approval configuration, or evidence matrix.

This is a governed result, not a technical failure. The project contains legacy candidates and policy/design material, but it does not contain the approved master-data package required to make those candidates operational. `GOVERNED-MASTER-DATA-INVENTORY.md` records each gap and source boundary.

## Registers intentionally left empty

| Register | Rows created | Reason |
|---|---:|---|
| Accounting Entity / Calendar / Period | 0 | No approved entity or fiscal-calendar charter. |
| Account Group / Chart of Accounts / dimension rules | 0 | Legacy CoA has no approved V2 mapping. |
| FinancialAccount / custody details | 0 | No verified, approved account register. |
| Fund Type / Restriction / Fund / Fund Policy Version | 0 | No approved Fund register or executable policy matrix. |
| Program / CostCenter / Category / Counterparty | 0 | No approved operational registers; legacy candidates must not be reinterpreted. |
| Transaction Type / Posting Rule / Posting Rule Version | 0 | No approved rule catalogue or versioned resolver specification. |
| Document Sequence | 0 | No approved numbering/scope policy. |
| Approval and Evidence requirements | 0 | Thresholds, retention, and owner approval are missing. |

## Control posture retained

- Master uniqueness, lifecycle, effective-date, restricted-Fund fail-closed behavior, prohibited-rule precedence, posting-rule resolution, voucher uniqueness, approval requirements, and evidence requirements are covered by Financial V2 tests.
- Runtime master governance creates audit records but does not create Journal, JournalLine, LedgerEntry, Receipt, Payment, Transfer, Allocation actual, Realization actual, or Opening Balance.
- The posting engine remains the only official-fact writer. No cutover date, historical migration, or dual-write route has been introduced.

## Governance gaps that block actual onboarding

| ID | Gap | Required owner / decision |
|---|---|---|
| GMD-01 | AccountingEntity and fiscal calendar absent. | Policy owner / Finance Controller |
| GMD-02 | Signed V2 CoA and AccountGroup mapping absent. | Finance Controller |
| GMD-03 | Verified FinancialAccount register absent. | Treasury / authorised financial officer |
| GMD-04 | Fund classification and policy matrix absent. | Policy owner, Finance Controller, Syariah reviewer where applicable |
| GMD-05 | Program/CostCenter and Category master registers absent. | Operational owner / Finance Controller |
| GMD-06 | Posting-rule catalogue and sequence policy absent. | Finance Controller |
| GMD-07 | Approval thresholds/segregation matrix absent. | Financial authority |
| GMD-08 | Evidence retention and supersession matrix absent. | Policy owner |

## Next gate

Provide the approved reference-data package named in the inventory. The next run may onboard only the unambiguous, approved values and must re-run master, schema, and no-financial-fact verification. Opening balance, cutover, reporting, and real transactions remain explicitly out of scope.

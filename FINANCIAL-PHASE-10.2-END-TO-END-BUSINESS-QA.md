# Financial V2 Phase 10.2 — End-to-End Business QA

Date: 2026-08-15
Environment: local development and `mrj_test_db` only
Production, cutover, legacy disablement, dual-write, real-business seeding, and data import: **not performed**.

## Status

**FINANCIAL V2 BUSINESS WORKFLOW = NOT READY**

The Posting Engine, immutable Journal/JournalLine/Posted Ledger, Fund restriction, allocation/realization, and the full automated baseline passed. The release gate is blocked by a material Fund-reporting inconsistency: the business-facing **Saldo akhir** displayed by the Fund report is not the Fund ending balance defined by the Accounting Policy and required by the Zakat scenario.

No formula for Fund balance was changed in this phase. The current Phase 4 reporting contract and its regression expectation conflict with the current business acceptance scenario; this needs a governed decision before an implementation change.

## 1. Scenario Matrix

| Scenario | Result | Evidence |
|---|---|---|
| Operational Jumat: receipt Rp5,000,000; payments Rp500,000 and Rp300,000 | PASS | Friday report closing cash Rp4,200,000; one receipt row and two payment rows from Posted V2 Ledger |
| Bank Operasional → Kas Operasional Rp2,000,000 | PASS | TRF changes source/destination Financial Account, carries Fund, and has no revenue/expense JournalLine |
| Santunan Anak Yatim Rp10,000,000 | PASS | Allocation and approval create no facts; one realization creates one PAYMENT Journal with two balanced lines |
| Santunan January–April | PASS, recipient-detail gap documented | Four approved allocations and four posted realizations: allocation Rp40,000,000, actual Rp40,000,000, remainder Rp0.00; ledger report filters provide per-month activity |
| Zakat Maal Rp20,000,000 receipt / Rp5,000,000 use | **FAIL — Fund reporting** | Liquidity attributable to Zakat is Rp15,000,000, but displayed Fund `closing_net_position` is Rp40,000,000 |
| Restricted Fund violation | PASS | Prohibited Zakat-to-Operasional use fails closed; Journal and Ledger fact counts do not change |
| Qurban | PASS | Receipt, BNI Qurban → Kas Qurban TRF, then actual PAYMENT; transfer has no income/expense |
| Ramadhan Models A and B | PASS | Separate Dana Ramadhan + Iftar Program and Dana Operasional + Operasional Ramadhan Program both post without date-based inference |
| Social/Kematian | PASS | Separate Fund and Program attribution is retained in JournalLine and Ledger |
| Sewa Aula | PASS | Receipt is attributed to a separate Fund, Program, and Financial Account fixture |

## 2. Expected Result

- Rekening remains the place where money is held; Dana remains the source/restriction; Program remains the activity/cost centre.
- Friday should show `opening + receipts - payments = closing`, and transfers must not be income or expense.
- Allocation and allocation approval are non-financial plans; only a posted realization is an actual expense.
- A Zakat receipt of Rp20,000,000 followed by permitted use of Rp5,000,000 must result in a Fund balance of Rp15,000,000.
- A prohibited restricted-Fund transaction must not create a Journal, JournalLine, LedgerEntry, voucher, or changed balance.

## 3. Actual Result

All operational posting paths above meet their expected outcome. The explicit Zakat QA fixture produced the following Posted V2 Ledger-derived values:

| Measure | Value |
|---|---:|
| Receipt | Rp20,000,000.00 |
| Usage | Rp5,000,000.00 |
| Liquidity distribution for Zakat | Rp15,000,000.00 |
| Current Fund report `closing_net_position` / UI `Saldo akhir` | Rp40,000,000.00 |

The last value is the sum of normal-balance amounts on all Fund-attributed lines: cash Rp15,000,000 + revenue Rp20,000,000 + expense Rp5,000,000. It is neither the available liquidity nor the required Fund ending balance.

## 4. Accounting Effect

- Receipt and payment each create one balanced, immutable Journal, two JournalLines, and corresponding Posted Ledger facts through `PostingEngine`.
- TRF creates only liquidity lines for source and destination Financial Accounts. It never creates revenue or expense lines.
- Allocation and allocation approval create governance records and audit events only. They never write Journal, JournalLine, LedgerEntry, or a balance.
- A realization creates one PAYMENT accounting effect for its total actual amount; it does not produce one Journal per recipient.
- The Phase 10.2 fixtures use only synthetic master, transaction, evidence, and period data in the test database.

## 5. Ledger Validation

- Friday: Journal debit = credit and the Posted Ledger-derived cash report closes at Rp4,200,000.00.
- Bank-to-cash and Qurban transfer: both JournalLines retain the selected Fund; neither transfer contains the revenue or expense Account.
- Santunan: the exact Rp10,000,000 realization posts one Journal and two JournalLines, with `FundRealization` recorded.
- The Financial V2 suite including the performance rehearsal passed 85 tests / 558 assertions. Its performance UAT posted 1,100 transactions and 12,000 Ledger lines through the canonical engine.

## 6. Fund Validation

- Separate ZISWAF, Qurban, Ramadhan, Social/Kematian, Hall Rental, and Operational Funds remain separate dimensions; no Program is used as a Fund and no Financial Account is used as a Fund.
- Restricted Fund policy is server-side and fail-closed. The existing UX test confirms the user-facing wording: `Penggunaan dana ini tidak diperbolehkan untuk kategori atau program tersebut.`
- The Zakat liquidity amount reconciles to Rp15,000,000.00, but the current Fund summary/formula does not provide a valid Fund ending balance. This is the blocking defect.

## 7. Program Validation

- Program attribution is retained on JournalLine and Ledger facts independently of Fund and Financial Account.
- Both Ramadhan models are supported as explicit master-data choices; no date/name magic was added.
- Four monthly Santunan allocations and posted realizations can be reported by `accounting_date`, with total allocation, actual, and remainder derived from governed allocations and posted realization facts.

## 8. Report Validation

- Friday, account balance, program, transaction history, Fund, and ZISWAF queries use `financial_v2_posted_general_ledger` via the existing posted-ledger query boundary.
- The Friday detail query contained a narrow SQL defect: it passed `COALESCE(original_transaction_type.code, transaction_type.code)` to `whereIn()` as a quoted column name. It is repaired by a parameterized `whereRaw(... IN (...))` expression in `FinancialReportService`; this changes no accounting fact, rule, or balance formula.
- Legacy financial tables are not read by the changed reporting service or the Phase 10.2 tests.
- **Blocking report discrepancy:** `FinancialReportService::fundBalances()` sums every Fund-attributed normal-balance Ledger amount into `closing_net_position`, while the Fund screen labels it `Saldo saat ini` and the Fund report labels it `Saldo akhir`.

The approved policy defines Saldo Dana as a net Fund position, and BR-118 requires opening, receipt, expense, transfer-in, transfer-out, adjustment, and ending balance. The existing Phase 4 report implementation instead documented and tested an all-line aggregate. These are not equivalent.

## 9. Negative Test

The passing Financial V2 suite covers and passed:

- allocation overspend and negative Fund-liquidity prevention;
- prohibited restricted Fund, inactive Fund, inactive Program, and invalid/inactive Financial Account rejection;
- idempotency-key reuse, duplicate source identity, and concurrent/double posting protection;
- same-source/destination Treasury Transfer rejection;
- non-positive allocation amount rejection;
- required evidence and approval rejection before accounting facts are committed; and
- closed-period posting rejection.

These controls fail closed without half-posting, orphan Ledger facts, duplicate voucher, or duplicate Journal.

## 10. UX Findings

- Financial V2 transaction forms and tests expose business choices—Rekening/Kas, Dana, Program, Category, amount, description, and evidence—rather than debit, credit, Journal, Ledger, CoA, or Posting Rule.
- The user-facing restricted-Fund rejection is business-friendly and does not expose the internal policy exception code.
- The public page, login page, and authentication redirect for `/admin/keuangan-v2` were live-checked through the local browser at `http://127.0.0.1:8000`.
- Desktop/tablet/mobile authenticated sidebar validation could not be completed because no authorised local QA credential was provided. No account was created and no credential was guessed or inspected.
- The local QA server was stopped after the check.

## 11. Remaining Gaps

1. **Critical — Fund ending balance definition and implementation:** decide and implement the governed report contract for `Saldo Dana` / `Saldo akhir`. It must distinguish net Fund balance, available liquidity, and Fund-by-Rekening composition, then have a ledger tie-out. Do not simply relabel the existing Rp40,000,000 aggregate as a balance.
2. **Recipient Register domain:** the Foundation has no recipient, recipient-count, or nominal-per-recipient field. The current supported audit evidence is a permitted attachment (for example, an approved `other` evidence record), while four monthly total realizations are traceable through allocation/realization and ledger dates. A governance decision is needed on whether that is sufficient or a dedicated Recipient Register domain is required.
3. **Live responsive QA:** an authorised local QA account is required to inspect desktop, tablet, and mobile sidebar/menu states and authenticated forms.
4. The full baseline retains one unrelated risky `ExampleTest` output-buffer warning. It has no failure and was not changed.
5. Existing test setup emits unrelated `Berita ID ... tidak ditemukan` diagnostics despite passing tests; this is outside Financial V2 and was not changed.

## 12. Recommendation

**Do not proceed to a business readiness/cutover gate until a governed decision resolves the Fund ending-balance contract.**

Required decision:

1. Confirm the authoritative calculation/tie-out for Fund ending balance under the Accounting Policy, including treatment of receipts, expenses/distributions, interfund transfers, adjustments, liabilities, and Fund control/net-asset accounts.
2. Confirm how the UI must separately label and display available liquidity by Financial Account.
3. Approve the corresponding narrow reporting change and a regression expectation for the Zakat Rp20,000,000/Rp5,000,000 → Rp15,000,000 scenario.

After that decision, implement the approved reporting correction, run the entire suite again, and complete authenticated responsive browser QA. No posting-engine, journal, ledger, legacy, cutover, or production change is recommended by this report.

## Validation Record

| Check | Result |
|---|---|
| New Phase 10.2 scenario suite | PASS — 6 tests, 47 assertions |
| Financial V2 feature + unit suite | PASS — 85 tests, 558 assertions, including 1,100 transaction / 12,000 Ledger-line performance UAT |
| Full application baseline | PASS — 109 tests, 619 assertions; 1 pre-existing risky test; 0 failed; 0 skipped |
| PHP lint | PASS — changed PHP files |
| Pint | PASS — changed PHP files |
| Blade cache | PASS |
| `git diff --check` | PASS |
| Canonical writer audit | PASS — only `PostingEngine` calls `Journal::create`, `JournalLine::create`, and `LedgerEntry::create` |
| Legacy isolation audit | PASS — changed Financial V2 report/test code has no legacy financial-table read or write |
| Authenticated responsive browser QA | BLOCKED — no authorised local QA credential |

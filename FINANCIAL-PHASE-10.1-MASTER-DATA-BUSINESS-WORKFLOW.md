# Financial Phase 10.1 — Master Data & Business Workflow

## Status

**IMPLEMENTATION COMPLETE — FINAL FULL-BASELINE / VISUAL GATE INCONCLUSIVE**

This phase adds governed Financial V2 master-data operations and business-facing navigation without changing the Posting Engine, Journal, JournalLine, Posted General Ledger, Fund-accounting model, closing, reconciliation, or opening-balance architecture. No legacy financial table is read or written by the additions in this phase.

No real business master, opening balance, account, transaction, or cutover data was seeded.

## 1. Sidebar Integration

The admin sidebar now contains a separate **Keuangan V2** group. It exposes the business-facing V2 navigation:

- Dashboard, Penerimaan, Pengeluaran, Transfer, Dana, Alokasi Dana, Riwayat Transaksi, Laporan, and Kontrol.
- A **Master Keuangan** section containing Rekening / Kas, Dana, Program, Kategori Transaksi, and Aturan Dana.

The V2 group is isolated from the legacy *Manajemen Keuangan* group. It deliberately does not expose Financial V2 Journal, Journal Line, Ledger, Posting Engine, or CoA menus. Accounting detail remains reachable only through existing transaction/report drill-downs. The existing responsive sidebar remains the desktop/tablet/mobile navigation mechanism, including its collapsible state.

## 2. Master Rekening / Kas

`FinancialMasterDataService` and the V2 master page provide governed create, edit, activation, and deactivation for Financial Accounts. The page uses business names and codes, not UUIDs, and captures the existing Foundation fields:

- name, code, type, linked liquidity Account, currency, opening/closing date, and custodian reference;
- bank details (institution, masked account reference, protected reference); or
- cash/petty-cash details (location, count frequency, optional limit).

Creation is draft-only. Activation is delegated to `MasterDataGovernanceService`, which requires a compatible active liquidity Account and custody detail. There is no hard-delete route. A referenced account cannot have its identity/accounting mapping changed; deactivation preserves history.

## 3. Master Dana

The Dana page separates Fund type, Fund restriction, and Fund. It supports governed configuration of:

- Fund type classification and lifecycle;
- restriction code, severity, policy basis, and lifecycle; and
- Fund name/code, purpose statement, prohibited-use statement, balance policy, validity dates, and lifecycle.

A Fund is draft-only when created. Its activation is delegated to governance, including the existing requirement for an effective policy matrix on restricted classifications. Creating a Fund creates no balance, Journal, JournalLine, or LedgerEntry.

## 4. Master Program

The Program page supports code, name, optional dates, cost center, and owner reference. Programs are draft-only when created and use the existing governance service for activation. The UI explicitly states that a Program is a purpose/activity and not a Dana, Rekening/Kas, or balance. Inactive Programs remain unavailable to the existing transaction lifecycle.

## 5. Master Category

The Category page supports code, name, optional transaction-type scope, optional default posting-rule reference, validity dates, and active/inactive lifecycle. It does not reveal debit/credit or account mapping to the operator. Used Categories are deactivated rather than deleted.

## 6. Fund Policy

The **Aturan Dana** page manages versioned Fund Policy records and their allowed/prohibited rules. Rules may be scoped by existing transaction type, Account, Category, and Program dimensions. No Program=Fund mapping was introduced.

Draft policies and rules are editable. Making a policy effective is performed only by `MasterDataGovernanceService`; effective versions are immutable. Existing transaction-time policy enforcement remains server-side and continues to produce the business-facing rejection: “Penggunaan dana ini tidak diperbolehkan untuk kategori atau program tersebut.”

## 7. Friday Workflow

Friday remains a Program/period use case, not an automatic Fund. The existing generic master configuration supports the approved model:

- configure a Fund such as operational funds only when formally approved;
- configure a Program for Friday operations when needed; and
- record actual receipts/payments with their selected Fund, Program, Category, and Financial Account.

No “Dana Jumat” is seeded or inferred.

## 8. Friday Report

The report selector now labels the report **Laporan Keuangan Jumat**. It retains the existing Posted General Ledger cash-flow calculation and now shows business-event detail tables for Pemasukan and Pengeluaran. The report contains saldo awal, detailed receipts and total, detailed payments and total, saldo akhir, and transfer-information disclosure.

The detail query groups at the posted Journal/business-event level rather than exposing double-entry JournalLines. Internal transfers are not classified as income or expense. The source remains `financial_v2_posted_general_ledger` and never reads legacy transactions.

## 9. Ramadhan Workflow

No date-based Ramadhan Fund is created. The master workflow supports both approved configurations without interpretation:

- a separately governed Ramadhan Fund; or
- an operational Fund combined with a Ramadhan Program.

The choice remains configured master data and policy, never an automatic rule based on a name or calendar date.

## 10. Qurban Workflow

The same separate dimensions support a governed Dana Qurban and Qurban Program when formally configured. Existing V2 transfer workflow continues to model bank-to-cash movement as a `TRF` treasury transfer, with no income or expense impact. Actual animal, operating, or distribution costs remain `PAY` expenses through the existing Posting Engine.

## 11. ZISWAF Workflow

Funds remain individual master records. The implementation does not collapse Zakat Maal, Infaq, Sodaqoh, Fidyah, Dhuafa, and Yatim into a forced “ZISWAF Fund.” Existing ZISWAF reporting is an aggregate presentation over configured Fund dimensions and does not infer classification from Fund names or codes.

## 12. Santunan Workflow

The existing governed workflow remains:

`Alokasi → Ajukan → Setujui → Realisasi`

The Program and Fund master pages make the required dimensions selectable and distinct. Allocation continues to represent plan/peruntukan, and actual disbursement remains a payment realization through the existing engine.

## 13. Allocation

No allocation behavior was changed. Existing allocation create, submit, and approval lifecycle remains non-accounting: it must not create Journal, JournalLine, LedgerEntry, or a balance. The master implementation also creates no financial facts.

## 14. Realization

No realization behavior was changed. The existing realization workflow creates one payment accounting effect for the actual total, derives availability from the approved allocation, and rejects overspend. It does not create one Journal per beneficiary.

## 15. Bank-to-Cash Transfer

No transfer behavior was changed. Existing `TRF` remains the only business path for Financial Account to Financial Account movement. It moves the source/destination account dimensions while preserving Fund attribution and avoiding revenue/expense impact.

## 16. Reporting Integration

Existing reports remain V2 posted-ledger read models. The master additions supply the configured Account, Fund, Program, and Category dimensions that the existing report filters and dimensions consume. No report writes a snapshot, fact, balance, or legacy value.

## 17. UX Improvements

- Business terms replace UUID/technical labels on master pages.
- Mobile-safe Tailwind/DaisyUI card, table, form, and collapsible patterns follow the existing Financial V2 design system.
- Master pages clarify the distinctions Rekening/Kas, Dana, Program, Alokasi, Realisasi, and Transfer.
- Create is deliberately draft-first where the Foundation requires governance.
- No new UI framework, controller for accounting facts, or separate audit system was introduced.

## 18. Tests and Verification

Added `tests/Feature/FinancialV2/FinancialMasterDataWorkflowTest.php` covering:

- business sidebar/master navigation without V2 Journal/Ledger menus;
- Financial Account create, update, activation, deactivation, audit event, and no hard-delete endpoint;
- Fund, Program, and Category separation, lifecycle, audit, and no financial fact creation; and
- draft Fund Policy rule configuration plus immutable effective-policy behavior.

Existing V2 suites already cover policy fail-closed behavior, Program lifecycle validation, Friday ledger reporting, Qurban-style treasury transfer, allocation/no-Journal behavior, realization/overspend, ZISWAF Fund separation, and ledger-only reporting.

Completed checks:

- PHP lint: service, controller, reporting service, routes, and new test — PASS.
- Pint on changed PHP files — PASS.
- `git diff --check` — PASS.
- Blade compiler `compileString` — PASS for all six new master views.
- `php artisan view:cache` — PASS.
- Dedicated Financial Master Data suite — **4 passed, 49 assertions**.
- Canonical-writer design review — PASS: phase additions do not create/update Journal, JournalLine, LedgerEntry, transaction, opening-balance, allocation, or legacy financial facts.
- Legacy-isolation design review — PASS: no legacy financial-table query or write was introduced.

Runtime checks could not be completed:

- `php artisan route:list --name=financial-v2.masters` fails before route validation because Composer cannot load `App\Http\Controllers\User\DokumentasiEvaluasiController` from the expected filename.
- The complete `php artisan test` run was allowed 304 seconds but did not finish or emit a final summary. It therefore cannot be declared PASS, FAILED, or counted. An earlier stop-on-failure run reached **21 passed, 1 risky, 1 failed, 81 pending**; the sole failure was then corrected in this phase's own sidebar test and the dedicated suite passed.
- Browser responsive QA could not load the local development route because `php artisan serve --host=127.0.0.1 --port=8010` could not listen and the browser received `ERR_CONNECTION_REFUSED`. No browser/mobile result is claimed.

## 19. Remaining Limitations

1. The existing unrelated controller filename/class autoload mismatch blocks `route:list` validation. It is outside Financial V2 and was not changed.
2. Financial Account, Program, and Category Foundation schemas do not include a free-text `description` column. The UI uses the approved semantic fields (`purpose_statement`, `policy_basis`, owner/custodian references) rather than adding schema/design changes.
3. The Foundation does not model Santunan beneficiary count, nominal per beneficiary, or recipient-register metadata. Implementing those fields would require an explicit domain/schema decision and is intentionally not inferred here.
4. No approved production master package or cutover date exists. This phase intentionally seeds no business data and makes no cutover decision.

## Gate Recommendation

Do not declare the phase fully PASS yet. Approve a separate, narrow repair of the existing `DokumentasiEvaluasiController` autoload/filename defect (or provide the prior approval covering it), then rerun route verification and the full `php artisan test`; also restore a usable local development server/session for desktop, tablet, and mobile browser QA. The master suite and Blade cache have already passed. No Financial V2 architecture decision is required for those environmental repairs.

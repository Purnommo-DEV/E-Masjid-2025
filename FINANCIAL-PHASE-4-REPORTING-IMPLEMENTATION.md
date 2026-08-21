# Financial Phase 4 — Reporting Foundation V2

## Status

Financial Reporting Foundation V2 is a read-only query/reporting layer over the Financial V2 canonical facts. It does not create a new financial source of truth.

## Scope implemented

The following report contracts are available through the isolated `admin/keuangan-v2/laporan` presentation route and its JSON/AJAX/export-ready endpoint.

| Report | Grain | Canonical source | Key outcome |
|---|---|---|---|
| Financial summary | Entity / period | Posted liquidity Ledger + Trial Balance | Cash position, period in/out, TB control. |
| Account balance | FinancialAccount | Posted liquidity Ledger | Opening, debit, credit, closing, Fund composition. |
| Fund balance | Fund | All posted Fund-attributed Ledger lines | Net Fund position plus a separate liquidity distribution by FinancialAccount. |
| Account movement | LedgerEntry | Posted liquidity Ledger | Deterministic running balance with Journal/Voucher traceability. |
| Fund movement | LedgerEntry | Posted Fund-attributed Ledger | Deterministic Fund-line movement; it does not turn Fund into Rekening. |
| Transaction history | Posted Journal | Journal, JournalLine, Transaction, Voucher, evidence-link count | Unified posted register including reversal lineage. |
| Cash flow | Entity / period | Posted liquidity Ledger | Opening, receipt/payment net movement, internal transfer and unclassified movement. |
| Trial Balance | Account | Posted GL grouped by Account | Debit/credit totals and balance control. |
| Friday operations | Entity / period | Posted liquidity Ledger | Reversible, configurable default definition; not a new accounting policy. |
| ZISWAF | Fund | Posted Fund-attributed Ledger | Fund-selected report only; no classification inferred from a name, code, or Program. |
| Program | Program | Posted Program-attributed Ledger | Receipt/use attribution only; Program is never a Fund or cash-balance substitute. |

## Architecture and integrity controls

- `FinancialReportController` only validates/filter-adapts and delegates to `FinancialReportService`.
- `PostedLedgerQuery` is the single query boundary for facts. It joins `financial_v2_ledger_entries` to `financial_v2_journal_lines` and requires `financial_v2_journals.journal_status = posted`.
- No Financial V2 `Journal`, `JournalLine`, `LedgerEntry`, projection, transaction, or master record is written by the reporting layer.
- No legacy financial table, legacy controller, legacy balance, or legacy journal is read.
- Every response carries `as_of_posting_sequence`, derived from the canonical ordered ledger through the selected accounting date. Detail order is accounting date → posting sequence → line number.
- Multiple aggregates for a report are evaluated in one read-only application transaction, so MySQL repeatable-read supplies one report-consistent snapshot.
- Reversal facts are retained. A reversal is classified for presentation using its original Journal type, while its own Journal and reversal reference remain visible in history.
- Reports return an explicit no-data state. They do not present an empty system as an official `Rp0` position.

## Policy-sensitive definitions

`config/financial_reporting.php` contains reversible display mappings for the approved V2 transaction-type codes:

- `RCV` → cash in;
- `PAY` → cash out;
- `TRF` → internal treasury transfer;
- `IFT` → Fund-transfer display.

The file does not alter posting, Fund policy, ledger effects, or business classifications. Friday reporting uses the same configurable definition because no special Friday format is established in the current design source. ZISWAF requires an explicit Fund selection; there is intentionally no string/name based inference.

## Performance strategy

The layer uses aggregate SQL for summaries and seeks through existing V2 indexes rather than loading the ledger into PHP. Detail reports are cursor-paginated and compute only the current page's running balance after an aggregate opening balance query.

Existing Foundation indexes used by these access paths include:

- `fv2_ledger_account_order_ix` — entity, Account, date, posting sequence, line;
- `fv2_ledger_fund_order_ix` — entity, Fund, date, posting sequence;
- `fv2_ledger_fin_acc_order_ix` — entity, FinancialAccount, date, posting sequence;
- `fv2_ledger_program_order_ix` — entity, Program, date, posting sequence;
- `fv2_ledger_date_sequence_ix` — deterministic entity-wide ordering;
- `fv2_journal_entity_date_status_ix` — Posted Journal filtering.

No new migration was required because the Phase 2 Foundation already contains the needed access-path indexes. No cache or snapshot table is written in Phase 4; any later cache must be rebuildable from Posted GL and retain its watermark.

## Isolation and non-goals

Phase 4 made no change to:

- legacy financial data or tables;
- Financial V2 migrations, Posting Engine, posted-fact guards, cutover, opening balance, closing, reconciliation, or operational business rules;
- dual-write behavior;
- exports (the JSON contract is ready for a later export adapter).

## Known boundary for the next governed gate

The Program report is ready to read canonical `program_id` ledger dimensions when posted facts exist. During test-fixture exploration, an existing Posting Engine optional-dimension validator attempted to access `valid_from`/`valid_to` on `financial_v2_programs`, although that master schema does not contain those columns. This is outside the read-only reporting scope and was not changed here. A governed, narrowly scoped Core Financial fix is required before relying on new operational transactions that carry Program attribution. It does not alter the report's source-of-truth design.

## Validation record

| Check | Result |
|---|---|
| Reporting reconciliation, canonical-source, and no-write test | PASS — `reporting foundation derives balances, movements, and tie-outs only from posted V2 facts` (25 assertions). It verifies account balance, Fund net position versus liquidity distribution, cash-flow tie-out, Trial Balance equality, history, movement, watermark, and unchanged fact counts. |
| Zero-data and HTTP report API test | PASS — `report API and page are zero-data safe and exclude Financial V2 drafts` (10 assertions). |
| Financial V2 suite | PASS — 41 tests, 220 assertions. |
| Full application baseline | PASS — 65 passed, 281 assertions, 1 pre-existing risky test (`ExampleTest` output buffer). No failures or skipped tests. |
| PHP syntax | PASS — all new PHP files. |
| Code style | PASS — targeted `vendor/bin/pint --test`. |
| Blade compilation | PASS — `php artisan view:cache`. |
| Production assets | PASS — `npm.cmd run build`. Browserslist emitted its existing stale-data advisory only. |
| Diff whitespace | PASS — `git diff --check`. |
| Financial fact / legacy isolation audit | PASS — no reporting-layer call to `create`, `update`, `delete`, `insert`, `upsert`, or `truncate`; no legacy financial controller/table reference; no `Journal::create`, `JournalLine::create`, or `LedgerEntry::create` outside the existing canonical writer. |

The test command emitted existing legacy-seeder `Berita ID … tidak ditemukan` console noise; it did not produce a test failure and is outside Financial V2 reporting scope.

# Financial Phase 12.5 — Fund Attribution Correction

## Final status

**PHASE 12.5 = PASS**

The actual local MRJ Financial V2 data now represents the approved attribution of Cash Tromol and the Rp1.200.000 Fund reclassification without changing total liquidity, creating income or expense, or mutating the opening balance.

## Scope and source controls

The correction was executed only against local `mrj_prod_db`. Automated tests were executed only against `mrj_test_db`, protected by the test bootstrap and runtime database guard.

Approved sources:

| Source | Lineage | SHA-256 |
| --- | --- | --- |
| `ZISWAF UPDATE 3.xlsx` | `Sisa Alokasi Dana!D66:E66` for Cash Tromol Yatim Rp2.653.000 | `404FC8CD54ECD3E35E17C30FFE6A3D88DF6656260CBEAC8F614EF99689A02F9C` |
| `Sisa Alokasi Dana Ziswaf DKM MRJ TCE (per 16 agustus 2026).pdf` | Pages 3–4 for the approved Rp1.200.000 Infaq/Tromol to Dhuafa/Anak Yatim reclassification | `0A83D582B1E78EA8F41E76024008AA358A053F99FFA8598B70F398BFD587AE82` |

Later August movements in the PDF were not imported. The PDF was used only for the explicit Rp1.200.000 decision evidenced on pages 3–4. This prevents scope expansion and preserves the approved Phase 12.5 closing position.

## Pre-correction position

| Dimension | Amount |
| --- | ---: |
| Fund Infaq & Tromol | Rp19.319.949 |
| Fund Dhuafa | Rp9.658.977 |
| Combined Funds | Rp28.978.926 |
| BNI ZISWAF | Rp123.077.312 |
| Cash Tromol Yatim | Rp2.653.000 |
| Total liquidity | Rp125.730.312 |

## Governed correction

The existing `DHUAFA` Fund was renamed from **Dana Dhuafa** to **Dana Dhuafa & Anak Yatim**. Its UUID remains `a281ac1b-2844-45e2-8419-6c91731f946b`; no replacement Fund was created. The rename is recorded by the governed master-data audit event.

Two distinct Inter-Fund Transfers were posted through the canonical Posting Engine on 16 August 2026:

| Voucher / source reference | Amount | Source Fund | Destination Fund | Attribution account | Meaning |
| --- | ---: | --- | --- | --- | --- |
| `IFT-00000001` / `MRJ-P12.5-CASH-ATTRIBUTION-2026-08-16` | Rp2.653.000 | Infaq & Tromol | Dhuafa & Anak Yatim | Cash Tromol Yatim | Reattributes the full Cash Tromol component; physical cash does not move |
| `IFT-00000002` / `MRJ-P12.5-FUND-RECLASS-2026-08-16` | Rp1.200.000 | Infaq & Tromol | Dhuafa & Anak Yatim | BNI ZISWAF | Applies the approved historical Fund reclassification; bank liquidity does not move |

The account on each transaction header is immutable attribution context. The four IFT JournalLines intentionally retain `financial_account_id = NULL`, so the correction cannot be mistaken for a Treasury Transfer.

Each IFT has exactly one balanced Journal, two JournalLines, two matching Ledger entries, one committed PostingAttempt, one completed idempotency record, one unique Voucher, one posting audit event, and the approved PDF attachment.

## Policy containment

Restricted Funds remain fail-closed.

- A governed v3 policy was effective only on 16 August 2026 and allowed the exact IFT/account combinations required by this correction; no wildcard IFT permission was introduced.
- A governed v4 policy became effective on 17 August 2026 and restores the previous fail-closed rule set without ordinary IFT permission.
- Replay validates the historical policy chain and supports a legitimate later successor without requiring v4 to remain current forever.

## Historical source correction

The existing read-only historical Cash Tromol source row was reassigned from Infaq & Tromol to Dhuafa & Anak Yatim through `HistoricalFundHistoryService::correct()`. Workbook name, worksheet, cell range, source key, amount, correction reason, actor, timestamp, and before/after audit lineage were preserved.

This source-history correction creates no Transaction, Journal, JournalLine, Ledger entry, Voucher, income, expense, or liquidity movement. The two posted IFTs are the only official V2 accounting effects.

### Source-history reconciliation hotfix

The source-history panel now treats the 27 June source closing as a **baseline**, not as the current target. Its documented `Historical Fund Reallocation` rows explain the subsequent approved Rp1.200.000 movement:

| Source-history Fund position | Baseline | Historical movement | Current source position | Reconciliation difference |
| --- | ---: | ---: | ---: | ---: |
| Infaq & Tromol | Rp16.666.949 | -Rp1.200.000 | **Rp15.466.949** | Rp0 |
| Dhuafa & Anak Yatim | Rp9.658.977 | +Rp1.200.000 | **Rp10.858.977** | Rp0 |

Cash Tromol Yatim Rp2.653.000 remains a separate Financial Account/cash component. It is visible for liquidity context but is never added to the source Fund target or reported as a source-history discrepancy. This UI/read-model change creates no accounting fact and does not alter the official posted-ledger Fund balance for Dhuafa & Anak Yatim (Rp13.511.977).

## Final Fund reconciliation

| Fund | Opening/pre-correction | Transfer in | Transfer out | Final |
| --- | ---: | ---: | ---: | ---: |
| Infaq & Tromol | Rp19.319.949 | Rp0 | Rp3.853.000 | **Rp15.466.949** |
| Dhuafa & Anak Yatim | Rp9.658.977 | Rp3.853.000 | Rp0 | **Rp13.511.977** |
| Combined | Rp28.978.926 | Rp3.853.000 | Rp3.853.000 | **Rp28.978.926** |

Total Fund position remains Rp125.730.312. No Rp1.200.000 or Cash amount was double counted.

## Final Fund/account composition

| Fund | Financial Account | Amount |
| --- | --- | ---: |
| Infaq & Tromol | BNI ZISWAF | Rp15.466.949 |
| Dhuafa & Anak Yatim | BNI ZISWAF | Rp10.858.977 |
| Dhuafa & Anak Yatim | Cash Tromol Yatim | Rp2.653.000 |

The shared posted-ledger-backed composition read model applies each tagged IFT exactly once. It is used by Fund reports, account composition, balance inquiry, and posting liquidity validation. Raw Financial Account balances continue to come directly from the Posted Ledger and are never modified by attribution.

## Final liquidity reconciliation

| Financial Account | Before | After | Difference |
| --- | ---: | ---: | ---: |
| BNI ZISWAF | Rp123.077.312 | **Rp123.077.312** | Rp0 |
| Cash Tromol Yatim | Rp2.653.000 | **Rp2.653.000** | Rp0 |
| Total liquidity | Rp125.730.312 | **Rp125.730.312** | Rp0 |

## Immutable fact preservation

The correction added only the two governed IFTs:

| Fact | Before | After | Delta |
| --- | ---: | ---: | ---: |
| Financial transactions | 3 | 5 | +2 |
| Journals | 1 | 3 | +2 |
| JournalLines | 13 | 17 | +4 |
| Ledger entries | 13 | 17 | +4 |
| Vouchers | 1 | 3 | +2 |
| Opening-balance batches | 1 | 1 | 0 |
| Opening-balance lines | 13 | 13 | 0 |

The two pre-existing draft payments were retained. No existing transaction, Journal, JournalLine, Ledger entry, Voucher, opening balance, evidence, or audit event was deleted or rewritten.

Integrity checks returned:

- Trial Balance balanced.
- Correction debit = correction credit = Rp3.853.000.
- Zero orphan Ledger entries.
- Zero duplicate Vouchers.
- Exactly four correction JournalLines and four correction Ledger entries.
- Two evidence links, one per correction transaction.
- One governed Fund-rename audit and one historical-source correction audit.

## Idempotency

The actual `--apply` execution completed successfully. A second identical `--apply` replay returned the same transaction and Journal identities and created no duplicate fact. A final read-only dry-run recognized the target state:

- Infaq & Tromol Rp15.466.949.
- Dhuafa & Anak Yatim Rp13.511.977.
- Cash attribution Rp2.653.000.
- PDF reclassification Rp1.200.000.

## UI and reporting QA

Authenticated browser QA used the local Super Admin QA account. The initial `artisan serve` wrapper could not bind port 8888, so the full pass first ran on an equivalent temporary port. A direct PHP server then served the same application successfully at the requested `http://localhost:8888`, where authenticated Dana ZISWAF verification also passed.

Verified surfaces:

- Financial V2 sidebar and Dashboard.
- Dana group cards and Dana ZISWAF list.
- Infaq & Tromol detail: Rp15.466.949, BNI composition only, no Cash component.
- Dhuafa & Anak Yatim detail: Rp13.511.977, BNI Rp10.858.977 and Cash Rp2.653.000.
- Both Fund histories show the two separate corrections and their source references.
- Filtered transaction history returns both IFTs exactly once for either Fund.
- Transaction detail labels the account as **Rekening atribusi (saldo tidak berpindah)** and displays the evidence.
- Accounting detail shows two balanced lines per IFT and no Financial Account dimension on those lines.
- New Pindah Dana form requires the attribution account and explains that bank/cash custody does not move.
- Saldo Dana report and its account composition match the final values.
- Saldo Rekening report remains BNI Rp123.077.312 and Cash Rp2.653.000.
- Trial Balance detail shows correction debit Rp3.853.000 and credit Rp3.853.000.
- At 390 px, group, Fund detail, and transaction-history pages have no global horizontal overflow; wide source tables use intentional contained scrolling.

## Automated validation

- Critical final regression: **42 passed, 342 assertions**.
- Financial V2 suite: **100 passed, 982 assertions**.
- Full `php artisan test`: **125 passed, 1046 assertions, 0 failed, 0 skipped**.
- One pre-existing risky `Tests\\Feature\\ExampleTest` output-buffer warning remains unrelated to Financial V2.
- PHP lint: PASS for 14 Phase 12.5 PHP files.
- Targeted Pint: PASS for 14 Phase 12.5 PHP files.
- Blade clear/cache: PASS.
- `git diff --check`: PASS.
- Canonical writer audit: PASS; no direct Journal/JournalLine/Ledger creator was added.
- Legacy isolation: PASS; the read-only preflight found no Financial V2 runtime access to legacy financial tables/models.
- Test isolation: PASS; preflight explicitly resolved `testing/mysql/mrj_test_db` before destructive test fixtures.

The read-only production-target preflight reports technical readiness PASS. Its aggregate status remains `not_ready` only because formal cutover/governance/rollback evidence is intentionally outside this local Phase 12.5 task.

## Remaining non-blocking limitations

1. The approved PDF contains later August activity outside the explicit Rp1.200.000 decision. It remains source evidence only and was not fabricated into the 27 June opening position or this correction.
2. Evidence archives are content-addressed and pre-staged before the database transaction. A hypothetical late database rollback could leave an unreferenced immutable archive file, but cannot leave a partial financial fact; no such rollback occurred during the actual run.
3. The legacy admin shell requested a few unrelated missing logo/favicon assets during browser QA. Financial V2 pages and assets loaded successfully.

## Acceptance conclusion

All Phase 12.5 accounting and functional acceptance criteria pass:

- Infaq & Tromol = **Rp15.466.949**.
- Dhuafa & Anak Yatim = **Rp13.511.977**.
- Cash Tromol Yatim = **Rp2.653.000**.
- BNI ZISWAF = **Rp123.077.312**.
- Total liquidity = **Rp125.730.312**.
- No duplicate, fake liquidity, double count, legacy dependency, sample data, or destructive operation was introduced.

**FINANCIAL V2 = READY FOR DAILY OPERATION within the approved local-development scope.**

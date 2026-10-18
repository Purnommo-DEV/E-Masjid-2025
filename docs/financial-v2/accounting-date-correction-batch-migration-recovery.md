# Accounting Date Correction Bank Batch — Migration Recovery

Urutan deployment wajib: migration 15 Replacement Realization → migration 16 Bank Batch → migration 17 Execution Pair. Migration 16 additive, append-only, dan `down()` fail-closed.

## Preflight executable

- migration fondasi Accounting Date Correction dan hardening REV tercatat;
- ketiga tabel target seluruhnya belum ada;
- Entity, Transaction, dan Journal adalah InnoDB dengan PK/unique UUID `CHAR(36)` non-null;
- `users.id` adalah InnoDB `BIGINT UNSIGNED PRIMARY KEY`;
- unique transaction journal, unique reversal journal, serta FK reversal journal sesuai kontrak.

## Expected-schema matrix

| Tabel | Kolom inti | Unique/index | FK/delete rule |
|---|---|---|---|
| `accounting_date_correction_batches` | Entity/correlation/guard UUID, target date, status enum, JSON snapshot/blocker, hash, net effect, idempotency, lifecycle actors/timestamps | entity+idempotency; active group; entity+status | Entity/creator `RESTRICT`; nullable approver/canceller `SET NULL` |
| `accounting_date_correction_batch_items` | batch/transaction/journal UUID, item number, JSON snapshot/blocker, hash | batch+transaction; batch+item number | seluruh FK `RESTRICT` |
| `accounting_date_correction_batch_approvals` | batch UUID, decision enum, snapshot/hash, actor/timestamps | batch+decided timeline | batch/actor `RESTRICT` |

Postflight executable memeriksa semua kolom type/null/default, engine dan collation tabel, unique/index, FK, pasangan kolom, serta kompatibilitas `COLUMN_TYPE`, charset, dan collation pada kedua sisi setiap FK. `DELETE_RULE` diperiksa literal; hanya `NO ACTION` dan `RESTRICT` yang ekuivalen untuk `UPDATE_RULE`. Kolom string non-FK mengikuti collation tabel dan tidak diklaim mempunyai pemeriksaan collation per kolom. Ketiga tabel wajib kosong.

DDL parsial harus dihentikan dan dipulihkan hanya melalui reconciliation migration forward-only. Jangan drop tabel, rollback, `migrate:fresh`, atau mengubah histori migration.

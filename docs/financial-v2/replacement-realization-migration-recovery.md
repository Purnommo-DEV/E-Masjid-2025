# Replacement Realization — Migration Recovery

Urutan deployment wajib: fondasi Accounting Date Correction → migration 15 Replacement Realization → migration 16 Bank Batch → migration 17 Execution Pair. Migration bersifat additive dan `down()` selalu fail-closed.

## Preflight executable

- dependency `2026_10_12_000001_create_financial_v2_accounting_date_correction_workflow` tercatat;
- keempat tabel target seluruhnya belum ada;
- seluruh parent adalah InnoDB dan `id` merupakan unique/PK `CHAR(36)` non-null dengan charset/collation eksplisit;
- `users.id` adalah InnoDB `BIGINT UNSIGNED PRIMARY KEY` non-null;
- correction request mempunyai PK, active-request unique guard, dan FK transaksi original yang benar.

## Expected-schema matrix

| Tabel | Kolom inti | Unique/index | FK/delete rule |
|---|---|---|---|
| `replacement_realization_requests` | UUID identity/lineage, enum separation/status, JSON snapshot, hash, idempotency, nullable lifecycle timestamps/actors | entity+idempotency; active correction guard | fakta `RESTRICT`; nullable approver/materializer/canceller `SET NULL` |
| `replacement_realization_decisions` | UUID, decision enum, snapshot/hash, actor, decided/created timestamps | request+decided timeline | request/entity/actor `RESTRICT` |
| `replacement_realization_lineages` | request/correction/original/replacement UUID, JSON/hash, created timestamp | request; correction; replacement realization | seluruh lineage `RESTRICT` |
| `distribution_correction_lineages` | Entity, replacement lineage, original Distribution/Realization, replacement Realization, JSON/hash | original Distribution; replacement lineage | seluruh lineage `RESTRICT` |

Postflight executable memeriksa setiap kolom (type/null/default), engine dan collation tabel, seluruh unique/index di atas, seluruh FK dan pasangan kolomnya, serta kompatibilitas `COLUMN_TYPE`, charset, dan collation pada kedua sisi setiap FK. `DELETE_RULE` diperiksa literal; hanya `NO ACTION` dan `RESTRICT` yang ekuivalen untuk UPDATE. Kolom string non-FK mengikuti collation tabel dan tidak diklaim mempunyai pemeriksaan collation per kolom. Semua tabel baru wajib kosong.

Jika satu objek terbentuk akibat implicit commit lalu DDL gagal, hentikan. Jangan drop, rollback, mengubah tabel `migrations`, atau menjalankan ulang. Inventarisasi `information_schema` dan buat reconciliation migration forward-only setelah review.

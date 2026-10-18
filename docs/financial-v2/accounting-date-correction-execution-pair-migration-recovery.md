# Accounting Date Correction Execution Pair — Migration Recovery

Urutan deployment wajib: migration 15 Replacement Realization → migration 16 Bank Batch → migration 17 Execution Pair. Migration 17 additive dan `down()` fail-closed.

## Preflight executable

- foundation Accounting Date Correction, REV hardening/governance, migration 15, dan migration 16 tercatat;
- kedua tabel target seluruhnya belum ada;
- seluruh parent adalah InnoDB dengan PK/unique UUID `CHAR(36)` non-null dan charset/collation eksplisit;
- `users.id` adalah InnoDB `BIGINT UNSIGNED PRIMARY KEY`;
- unique reversal journal, reversal FK, correction active guard, batch uniques, serta batch-item FK tersedia dan sesuai rules.

## Expected-schema matrix

| Tabel | Kolom inti | Unique/index | FK/delete rule |
|---|---|---|---|
| `accounting_date_correction_execution_pairs` | request/source/config/reason/reversal/replacement UUID; target date; 9-state enum; approved/validation JSON+hash; blockers; actors/timestamps | request; active original; entity+idempotency; reversal; replacement; entity+status | fakta/guard/config `RESTRICT`; nullable validator `SET NULL` |
| `financial_v2_acct_date_corr_batch_item_pairs` | Entity/batch/item/request/pair UUID; JSON/hash; creator/timestamp | batch item; request; pair | seluruh FK `RESTRICT` |

`active_original_guard` wajib memiliki FK ke `financial_v2_transactions.id` serta unique constraint. `execution_pair_id` diverifikasi langsung terhadap `execution_pairs.id`, termasuk pasangan kolom dan referential rules.

Postflight executable memeriksa seluruh kolom type/null/default, engine dan collation tabel, lima unique execution-pair, tiga unique linkage, status index, seluruh FK, serta kompatibilitas `COLUMN_TYPE`, charset, dan collation pada kedua sisi setiap FK. Kolom string non-FK mengikuti collation tabel dan tidak diklaim mempunyai pemeriksaan collation per kolom. `DELETE_RULE` diperiksa literal. `UPDATE_RULE` menerima hanya ekuivalensi `NO ACTION`/`RESTRICT`; `CASCADE` dan `SET NULL` tetap berbeda.

Jika implicit commit meninggalkan salah satu tabel/index/FK secara parsial, hentikan. Jangan drop, rollback, mengubah histori migration, atau rerun. Simpan inventaris `information_schema` dan gunakan reconciliation migration forward-only yang ditinjau. Migration tidak boleh membuat Transaction, Journal, Ledger, master REV, correction request, atau fakta bisnis.

## Recovery kegagalan identifier 1059 pada `mrj_prod_new`

Percobaan migration 17 dapat meninggalkan `financial_v2_accounting_date_correction_execution_pairs` lengkap dan kosong, sementara tabel linkage tidak terbentuk dan migration 17 tidak tercatat. Source terbaru mempunyai recovery fail-closed khusus untuk kondisi ini.

State sebelum DDL diklasifikasikan sebagai berikut:

- `normal`: kedua tabel belum ada; migration membuat tabel utama lalu linkage;
- `recover_link_only`: hanya tabel utama ada, seluruh schema/PK/index/unique/FK/rules/engine/collation tepat dan tabel kosong; migration hanya membuat `financial_v2_acct_date_corr_batch_item_pairs`;
- selain itu: migration berhenti sebelum DDL dengan error state tidak dikenal.

Sebelum klasifikasi, migration memverifikasi identitas `mrj_prod_new`/`DESKTOP-O10NAPB`/MySQL `8.4.9`, memastikan migration 17 belum tercatat, serta memvalidasi seluruh dependency. Setelah create, postflight penuh wajib lulus pada kedua tabel dan keduanya wajib kosong. Framework hanya mencatat migration 17 setelah `up()` kembali normal.

Jangan menghapus atau mengganti nama tabel parsial, menambah catatan migration secara manual, atau melewati preflight. Bila create linkage atau postflight gagal, source tidak melakukan cleanup otomatis; simpan inventaris schema dan lakukan review baru. Migration 17 tetap `BLOCKED` sampai pemeriksaan read-only terbaru membuktikan state `recover_link_only` yang valid.

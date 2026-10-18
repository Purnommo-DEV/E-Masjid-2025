# Accounting Date Correction and REV Migration — Preflight and Recovery

Urutan wajib migration:

1. `2026_10_12_000001_create_financial_v2_accounting_date_correction_workflow`
2. `2026_10_12_000002_create_financial_v2_reversal_configuration_workflow`
3. `2026_10_13_000001_harden_financial_v2_reversal_configuration_workflow`

Ketiganya bersifat forward-only. `down()` tidak boleh dipakai untuk menghapus histori workflow. MySQL/MariaDB dapat melakukan implicit commit pada DDL; transaksi migration tidak menjamin seluruh rangkaian DDL dapat di-rollback.

## Batas bukti lingkungan

SQL dump `raudhotu_mrj_db_new.sql` hanya membuktikan keadaan snapshot hosting saat export 12 Oktober 2026. Pada snapshot tersebut ketiga migration belum tercatat, tabel workflow belum ada, dan `compilation_strategy` belum ada. Temuan itu tidak membuktikan keadaan live hosting pada saat penerapan.

Database target aktif wajib diperiksa kembali tepat sebelum penerapan. Jangan menyalin kesimpulan dari dump atau database lokal ke live hosting.

## Preflight read-only

Simpan seluruh output pemeriksaan berikut bersama bukti backup/restore database target.

```sql
SELECT VERSION(), DATABASE();

SELECT migration, batch
FROM migrations
WHERE migration IN (
  '2026_10_12_000001_create_financial_v2_accounting_date_correction_workflow',
  '2026_10_12_000002_create_financial_v2_reversal_configuration_workflow',
  '2026_10_13_000001_harden_financial_v2_reversal_configuration_workflow'
)
ORDER BY migration;

SELECT table_name
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN (
    'financial_v2_accounting_date_correction_requests',
    'financial_v2_accounting_date_correction_approvals',
    'financial_v2_accounting_date_correction_evaluations',
    'financial_v2_accounting_date_correction_lineages',
    'financial_v2_reversal_configuration_requests',
    'financial_v2_reversal_configuration_decisions'
  )
ORDER BY table_name;
```

Verifikasi seluruh parent UUID dan kolom anak aktual. Migration membaca definisi ini dari `information_schema`; hasil yang kosong, bukan `CHAR(36)`, atau tidak memiliki charset/collation akan menghentikan migration sebelum DDL pertama.

```sql
SELECT table_name, column_name, column_type, character_set_name, collation_name,
       column_key, is_nullable
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND (
    (column_name = 'id' AND table_name IN (
      'financial_v2_accounting_entities',
      'financial_v2_transactions',
      'financial_v2_journals',
      'financial_v2_attachments',
      'financial_v2_fund_realizations',
      'financial_v2_transaction_types',
      'financial_v2_posting_rules',
      'financial_v2_posting_rule_versions',
      'financial_v2_reason_codes',
      'financial_v2_approval_requirements',
      'financial_v2_document_sequences',
      'users'
    ))
    OR (table_name = 'financial_v2_posting_rule_versions' AND column_name = 'compilation_strategy')
    OR (table_name = 'financial_v2_reversal_configuration_requests' AND column_name IN (
      'accounting_entity_id', 'transaction_type_id', 'posting_rule_id',
      'posting_rule_version_id', 'reason_code_id', 'approval_requirement_id',
      'governance_attachment_id', 'document_sequence_id', 'evidence_requirement_ids'
    ))
    OR (table_name = 'financial_v2_journals' AND column_name = 'reversal_of_journal_id')
  )
ORDER BY table_name, ordinal_position;
```

`users.id` wajib merupakan primary key `BIGINT UNSIGNED`. Migration tidak mengubah tabel `users` untuk menyesuaikannya.

Periksa indeks dan foreign key, termasuk nama, kolom, parent, dan aturan delete:

```sql
SELECT table_name, index_name, non_unique,
       GROUP_CONCAT(column_name ORDER BY seq_in_index) AS columns_in_index
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND table_name IN (
    'financial_v2_reversal_configuration_requests',
    'financial_v2_reversal_configuration_decisions',
    'financial_v2_posting_rule_versions',
    'financial_v2_journals'
  )
GROUP BY table_name, index_name, non_unique
ORDER BY table_name, index_name;

SELECT tc.table_name, tc.constraint_name, kcu.column_name,
       kcu.referenced_table_name, kcu.referenced_column_name,
       rc.delete_rule, rc.update_rule
FROM information_schema.table_constraints tc
JOIN information_schema.key_column_usage kcu
  ON kcu.constraint_schema = tc.constraint_schema
 AND kcu.table_name = tc.table_name
 AND kcu.constraint_name = tc.constraint_name
JOIN information_schema.referential_constraints rc
  ON rc.constraint_schema = tc.constraint_schema
 AND rc.table_name = tc.table_name
 AND rc.constraint_name = tc.constraint_name
WHERE tc.constraint_schema = DATABASE()
  AND tc.constraint_type = 'FOREIGN KEY'
  AND tc.table_name IN (
    'financial_v2_accounting_date_correction_requests',
    'financial_v2_accounting_date_correction_approvals',
    'financial_v2_accounting_date_correction_evaluations',
    'financial_v2_accounting_date_correction_lineages',
    'financial_v2_reversal_configuration_requests',
    'financial_v2_reversal_configuration_decisions'
  )
ORDER BY tc.table_name, tc.constraint_name, kcu.ordinal_position;
```

Unique reversal hanya dapat dipasang bila query berikut tidak menghasilkan baris:

```sql
SELECT reversal_of_journal_id, COUNT(*) AS reversal_count
FROM financial_v2_journals
WHERE reversal_of_journal_id IS NOT NULL
GROUP BY reversal_of_journal_id
HAVING COUNT(*) > 1;
```

## Kondisi wajib berhenti

Hentikan penerapan sebelum menjalankan migration berikutnya apabila salah satu kondisi terjadi:

- status pada `migrations` tidak sejalan dengan objek schema yang ditemukan;
- sebagian tabel workflow sudah ada sementara migration belum tercatat;
- `compilation_strategy` atau `fv2_rule_ver_compile_ix` ada tanpa kedua tabel workflow REV lengkap;
- salah satu kolom hardening/FK/unique index sudah ada sementara migration hardening belum tercatat;
- parent UUID bukan `CHAR(36)`, atau child yang sudah ada tidak persis sama tipe, panjang, charset, dan collation-nya;
- `users.id` bukan primary key `BIGINT UNSIGNED`;
- ada duplikasi non-null `reversal_of_journal_id`;
- parent table, index, atau foreign key dependency tidak tersedia;
- database target yang diperiksa bukan database yang akan menerima migration.

Preflight di source migration juga menolak keadaan parsial. Penolakan tersebut adalah pengaman, bukan instruksi untuk menghapus objek.

## Recovery forward-only untuk DDL parsial

1. Hentikan deployment. Jangan rerun, rollback, drop, rename, atau menambah record `migrations` secara manual.
2. Ambil snapshot schema dan data workflow yang dapat dipulihkan. Catat semua objek yang berhasil dibuat dan jumlah baris di setiap tabel yang ada.
3. Bandingkan metadata aktual terhadap source migration dan output preflight di atas.
4. Tentukan statement DDL terakhir yang berhasil dari keberadaan kolom, indeks, dan constraint—jangan hanya mengandalkan pesan error.
5. Buat migration reconciliation baru dengan timestamp setelah migration terakhir. Migration itu harus:
   - memvalidasi keadaan parsial yang spesifik sebelum DDL;
   - hanya menambah objek yang terbukti belum ada;
   - tidak menghapus atau menulis ulang histori/data;
   - menggunakan definisi UUID parent aktual dari `information_schema`;
   - memverifikasi hasil setelah setiap tahap yang dapat implicit-commit.
6. Tinjau cara Laravel melanjutkan urutan migration tanpa memalsukan tabel `migrations`. Jika migration asal belum tercatat tetapi objek parsial menghalangi eksekusinya, recovery harus dirancang khusus untuk keadaan itu dan disetujui sebelum dijalankan.
7. Uji prosedur recovery dan restore pada salinan terisolasi sebelum menyentuh target aktif.

Tidak ada reconciliation universal yang aman: bentuk migration recovery bergantung pada objek aktual dan apakah tabel parsial sudah berisi data.

## Validasi akhir per tahap

Sesudah `000001`, empat tabel accounting-date correction harus lengkap sebelum `000002` boleh dijalankan.

Sesudah `000002`:

- tabel request dan decision REV tersedia beserta seluruh FK;
- UUID anak cocok dengan masing-masing parent, termasuk parent yang berbeda collation;
- actor FK cocok dengan `users.id`;
- `compilation_strategy` dan `fv2_rule_ver_compile_ix` tersedia;
- migration tercatat hanya melalui eksekusi migration normal yang berhasil.

Sesudah `000003`:

- `governance_attachment_id`, `document_sequence_id`, dan `evidence_requirement_ids` tersedia;
- kedua FK hardening tersedia dan kompatibel dengan parent aktual;
- `fv2_journal_reversal_once_uq` ada dan benar-benar unique;
- query duplikasi reversal tetap menghasilkan nol baris;
- tidak ada perubahan record workflow, master REV, transaksi, jurnal, atau ledger sebagai efek validasi schema.

Jalankan migration berikutnya hanya setelah hasil tahap sebelumnya lengkap dan konsisten. Snapshot dump 12 Oktober tetap diperlakukan sebagai bukti historis, bukan preflight live.

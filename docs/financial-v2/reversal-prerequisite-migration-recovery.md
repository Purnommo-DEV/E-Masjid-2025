# REV Prerequisite Governance Migration — Preflight and Recovery

Migration additive: `2026_10_14_000001_create_financial_v2_reversal_prerequisite_governance`.

Migration ini belum boleh dijalankan sebelum `2026_10_13_000001_harden_financial_v2_reversal_configuration_workflow` tercatat dan schema hardening lengkap. Snapshot dump 12 Oktober 2026 bukan bukti keadaan database aktif.

## Preflight read-only

```sql
SELECT VERSION(), DATABASE();

SELECT migration, batch FROM migrations WHERE migration IN (
  '2026_10_13_000001_harden_financial_v2_reversal_configuration_workflow',
  '2026_10_14_000001_create_financial_v2_reversal_prerequisite_governance'
);

SELECT table_name FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name IN (
  'financial_v2_governance_documents',
  'financial_v2_governance_document_decisions',
  'financial_v2_reversal_prerequisite_proposals',
  'financial_v2_reversal_prerequisite_decisions'
);

SELECT column_name, column_type, character_set_name, collation_name
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'financial_v2_reversal_configuration_requests'
  AND column_name IN ('governance_attachment_id', 'document_sequence_id', 'evidence_requirement_ids',
    'prerequisite_proposal_id', 'governance_document_decision_id',
    'cancelled_at', 'cancelled_by_user_id', 'cancellation_reason');

SELECT constraint_name, table_name, referenced_table_name
FROM information_schema.referential_constraints
WHERE constraint_schema = DATABASE() AND constraint_name IN (
  'fv2_rev_cfg_gov_attachment_fk', 'fv2_rev_cfg_doc_sequence_fk',
  'fv2_rev_cfg_prereq_fk', 'fv2_rev_cfg_gov_dec_fk', 'fv2_rev_cfg_canceller_fk'
);

SELECT table_name, index_name, non_unique
FROM information_schema.statistics
WHERE table_schema = DATABASE() AND index_name = 'fv2_journal_reversal_once_uq';

SELECT COUNT(*) AS existing_rev_requests FROM financial_v2_reversal_configuration_requests;

SELECT table_name, column_name, column_type, character_set_name, collation_name, column_key
FROM information_schema.columns
WHERE table_schema = DATABASE() AND column_name = 'id'
  AND table_name IN (
    'financial_v2_accounting_entities', 'financial_v2_attachments',
    'financial_v2_transaction_types', 'financial_v2_document_sequences', 'users'
  );
```

`users.id` wajib primary key `BIGINT UNSIGNED`; seluruh UUID parent wajib `CHAR(36)` dengan charset dan collation yang tersedia. Migration menyalin definisi parent aktual, bukan default koneksi.

## Expected-schema matrix

Preflight source migration membandingkan metadata aktual, bukan hanya nama objek atau catatan migration. Kontrak dependency yang wajib identik meliputi:

| Objek | Kontrak wajib |
| --- | --- |
| `financial_v2_reversal_configuration_requests` | Seluruh UUID/master lineage, snapshot JSON, hash `CHAR(64)`, governance ref, idempotency, active guard, enum status/default, actor `BIGINT UNSIGNED`, `submitted_at`, `approved_at`, `activated_at`, `created_at`, `updated_at`, kolom hardening, seluruh FK dengan rules, serta index idempotency/entity/status |
| `financial_v2_reversal_configuration_decisions` | UUID request/Entity, snapshot JSON, hash, enum decision, note, actor, `decided_at`, `created_at DEFAULT CURRENT_TIMESTAMP`, FK request/Entity/actor, dan timeline index |
| `financial_v2_posting_rule_versions` | `compilation_strategy VARCHAR(40) NOT NULL DEFAULT 'rule_lines'` dan index non-unique `fv2_rule_ver_compile_ix(accounting_entity_id, compilation_strategy)` |
| `financial_v2_journals` | `fv2_journal_reversal_once_uq` benar-benar UNIQUE dan tepat hanya pada `reversal_of_journal_id` |

Kontrak objek baru yang diverifikasi postflight:

| Tabel | Kolom/constraint utama |
| --- | --- |
| `financial_v2_governance_documents` | UUID dan parent-collation, attachment/supersedes, purpose/basis, snapshot JSON+hash, status, idempotency, active-purpose guard, actor/timestamp/cancellation, seluruh FK dan tiga index scope |
| `financial_v2_governance_document_decisions` | lineage document+Entity, snapshot JSON+hash, enum decision, actor, note, decided timestamp, seluruh FK dan timeline index |
| `financial_v2_reversal_prerequisite_proposals` | lineage governance, snapshot JSON+hash, status, idempotency/entity guard, hasil materialisasi nullable, seluruh actor/timestamp/cancellation, seluruh FK dan index idempotency/guard/lineage/status |
| `financial_v2_reversal_prerequisite_decisions` | lineage proposal+Entity, snapshot JSON+hash, enum decision, actor, note, decided timestamp, seluruh FK dan timeline index |
| Integrasi request REV | `prerequisite_proposal_id`, `governance_document_decision_id`, `cancelled_at`, `cancelled_by_user_id`, `cancellation_reason`, tiga FK terkait, serta kompatibilitas UUID/actor |

Untuk MySQL, JSON harus dilaporkan sebagai tipe native `JSON`. Untuk MariaDB, representasi alias `LONGTEXT` hanya diterima bila metadata juga memiliki `CHECK JSON_VALID(...)` untuk kolom yang sama, charset yang sama dengan tabel, dan collation non-kosong yang diberikan server untuk alias JSON. Default, nullability, `DATA_TYPE`, `COLUMN_TYPE`, signedness actor, panjang, enum, dan urutan kolom index merupakan bagian kontrak; kesamaan nama saja tidak cukup. Kolom karakter non-JSON/non-FK yang tercantum dalam matrix harus memakai charset/collation default tabel. Setiap tabel baru harus memakai table collation yang sama dengan UUID canonical `financial_v2_accounting_entities.id`; UUID FK yang memakai parent lain tetap harus identik dengan definisi parent masing-masing.

Postflight executable memeriksa seluruh kolom empat tabel baru, lima kolom integrasi request REV, table collation, JSON/`JSON_VALID`, semua index beserta unique flag dan urutan kolom, seluruh FK beserta child/parent dan delete/update rules, serta actor `BIGINT UNSIGNED`. Bila matrix ini berubah, migration dan dokumen wajib diubah dalam review yang sama.

### Canonical foreign-key rules

MySQL/InnoDB dapat melaporkan aturan update default sebagai `NO ACTION`, sedangkan kontrak source migration menyebut `RESTRICT`. Untuk `UPDATE_RULE` saja, preflight dan postflight menormalisasi kedua representasi tersebut menjadi aturan canonical `RESTRICT`, karena keduanya mempunyai perilaku referential-integrity yang setara pada platform target. `CASCADE`, `SET NULL`, dan `SET DEFAULT` tetap berbeda dan tidak diterima ketika kontrak mengharuskan `RESTRICT`.

Normalisasi tersebut tidak diterapkan pada `DELETE_RULE`. Aturan delete setiap FK tetap dibandingkan secara ketat sesuai kontraknya (`RESTRICT` atau `SET NULL`). Nilai metadata yang tidak dikenal selalu menghentikan migration. Selain rules, pemeriksaan executable tetap mewajibkan tepat satu pasangan kolom untuk setiap FK workflow saat ini, ordinal pertama, tabel/kolom child, tabel/kolom parent, serta kesetaraan tipe, panjang, charset, dan collation child-parent.

## Kondisi berhenti

- Dependency hardening belum tercatat atau tidak lengkap.
- Tabel request/decision REV, tiga kolom hardening, dua FK hardening, atau `fv2_journal_reversal_once_uq` tidak tersedia walau migration tercatat.
- Request REV lama sudah ada dan membutuhkan reconciliation eksplisit untuk kolom integrasi baru.
- Salah satu dari empat tabel baru sudah ada saat migration belum tercatat.
- Salah satu kolom integrasi pada request REV sudah ada saat migration belum tercatat.
- UUID parent atau actor key tidak kompatibel.
- Database target tidak dapat dibuktikan sebagai target yang dimaksud.

Jangan drop objek, mengedit tabel `migrations`, menjalankan rollback, atau mengulang migration secara membabi buta.

## Recovery forward-only

MySQL/MariaDB melakukan implicit commit pada banyak DDL. Karena itu kegagalan postflight dapat meninggalkan objek yang sudah permanen walau record migration belum ditulis. Jika DDL berhenti sebagian, inventaris semua tabel, kolom, index, FK, serta jumlah baris. Jangan rerun file utama: preflight memang akan menolak keadaan parsial. Buat migration reconciliation baru yang secara eksplisit hanya melengkapi objek yang hilang, memvalidasi definisi parent aktual, dan di-review terhadap diagnosis tersebut. Histori proposal, keputusan, dokumen, attachment, dan request REV tidak boleh dihapus. Uji recovery pada salinan terisolasi dan verifikasi backup sebelum penerapan.

Diagnosis wajib mengambil `information_schema.COLUMNS`, `STATISTICS`, `TABLE_CONSTRAINTS`, `KEY_COLUMN_USAGE`, `REFERENTIAL_CONSTRAINTS`, dan `CHECK_CONSTRAINTS`. Catat untuk setiap objek: definisi aktual, definisi expected di atas, status ada/hilang/berbeda, jumlah baris, serta statement DDL terakhir yang terbukti berhasil. Bila postflight migration gagal, pesan exception menunjuk kontrak pertama yang berbeda; inventaris metadata tetap wajib dilakukan karena objek sebelum titik itu mungkin sudah implicit-commit.

Reconciliation harus forward-only dan spesifik terhadap snapshot kerusakan: fail jika keadaan aktual berubah sejak diagnosis, tambah hanya objek yang hilang, dan validasi kembali seluruh matrix. Jangan menormalkan collation parent, jangan menghapus constraint yang berbeda secara otomatis, dan jangan menandai migration asal berhasil secara manual.

## Validasi akhir

- Empat tabel workflow tersedia dan kosong sebelum penggunaan pertama.
- Dua kolom integrasi pada request REV tersedia dengan FK yang benar.
- Metadata cancel dan actor FK tersedia pada ketiga workflow.
- Postflight membandingkan tipe/charset/collation UUID utama dengan parent dan memverifikasi FK yang diwajibkan.
- Semua actor FK kompatibel dengan `users.id`.
- Unique guard idempotency, active Entity/purpose, dan hasil materialisasi tersedia.
- Tidak ada Transaction Type, Document Sequence, atau konfigurasi REV yang dibuat oleh migration.

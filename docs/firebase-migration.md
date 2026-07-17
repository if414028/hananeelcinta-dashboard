# Firebase Migration Guide

Phase 6 memindahkan data Firebase Realtime Database ke database Laravel secara idempotent. Importer mendukung jemaat, Prayer Request, announcements, Mezbah Keluarga (`mk`), Pastor Message, dry-run, filter module, chunk transaction, import logs, dan migrasi image opsional.

## Persiapan

1. Export Firebase Realtime Database sebagai JSON.
2. Simpan file di lokasi privat, misalnya `storage/app/private/import/firebase-export.json`.
3. Backup database MySQL sebelum import production.
4. Pastikan migration database sudah dijalankan.

Importer mengenali node berikut:

| Module | Node yang dikenali |
|---|---|
| Jemaat | `users` |
| Prayer Request | `prayerRequest`, `prayerRequests`, `prayer_requests`, `prayer-requests` |
| Announcements | `announcements`, `announcement`, `pengumuman` |
| Mezbah Keluarga | `mk` |
| Pastor Message | `pastorMessages`, `pastor_messages`, `pastor-messages`, `pastorMessage` |

Alias node dapat disesuaikan di `config/firebase.php`.

## Dry-run

Selalu jalankan dry-run terlebih dahulu:

```bash
php artisan firebase:import storage/app/private/import/firebase-export.json --dry-run
```

Dry-run membaca, memvalidasi, memetakan, menghitung hasil, dan menampilkan warning tanpa menulis content maupun `data_imports`.

## Menjalankan Import

Import seluruh module:

```bash
php artisan firebase:import storage/app/private/import/firebase-export.json
```

Import satu module:

```bash
php artisan firebase:import storage/app/private/import/firebase-export.json --only=congregations
php artisan firebase:import storage/app/private/import/firebase-export.json --only=prayer-requests
php artisan firebase:import storage/app/private/import/firebase-export.json --only=announcements
php artisan firebase:import storage/app/private/import/firebase-export.json --only=family-altars
php artisan firebase:import storage/app/private/import/firebase-export.json --only=pastor-messages
```

Beberapa module dapat dipisahkan koma:

```bash
php artisan firebase:import storage/app/private/import/firebase-export.json --only=announcements,pastor-messages
```

Atur ukuran transaksi per chunk:

```bash
php artisan firebase:import storage/app/private/import/firebase-export.json --chunk=250
```

Nilai chunk dibatasi antara 1–1000. Default adalah 100.

## Idempotency dan Force

- Jemaat menggunakan `legacy_firebase_uid`.
- Prayer Request menggunakan `legacy_firebase_key`.
- Announcement dan Pastor Message menggunakan `legacy_firebase_key`.
- Mezbah Keluarga menggunakan `legacy_firebase_index`.
- Import ulang tanpa `--force` melewati record yang sudah ada.
- Import ulang dengan `--force` memperbarui record lama dan tetap mempertahankan MySQL primary key.
- Firebase field `id` lama tidak pernah dipakai sebagai MySQL primary key.

```bash
php artisan firebase:import storage/app/private/import/firebase-export.json --force
```

## Mapping Penting

Timestamp numerik Firebase otomatis dikenali sebagai milliseconds. Timestamp seconds, ISO date string, serta object `{seconds, nanoseconds}` juga didukung. Semua nilai dikonversi ke timezone aplikasi (`Asia/Jakarta`). Timestamp kosong atau invalid menjadi `null`, menghasilkan warning, dan content disimpan sebagai draft.

Hari pada node `mk` dinormalisasi dari bahasa Indonesia atau Inggris ke enum `monday`–`sunday`. Waktu seperti `17:30 WIB` menjadi `17:30:00`. Nomor telepon dibersihkan dan nomor Indonesia berawalan `0` diubah menjadi `+62`.

Item `null` pada array `mk` diabaikan tetapi index aslinya tetap digunakan sebagai `legacy_firebase_index` dan `sort_order`.

Node `users` dipetakan ke tabel `congregations`. NIJ menjadi `member_number`; NIJ duplikat diberi suffix berurutan dan dicatat sebagai warning. Akun dengan `deletedAt` ditandai tidak aktif. Data profil tambahan disimpan pada `notes`, sedangkan password dan FCM token tidak pernah diimpor.

Node `prayerRequest` dipetakan ke tabel `prayer_requests`. Kontak pemohon dilengkapi dari jemaat berdasarkan Firebase UID. Status `OPEN`, `IN_PROGRESS`, dan `DONE` menjadi `new`, `in_prayer`, dan `closed`. Semua data hasil migrasi ditandai rahasia, memakai source `migration`, dan menyimpan jenis permohonan serta handler lama pada catatan admin.

Pastor Message dengan `writer` kosong tetap diimpor menggunakan label transparan `Penulis tidak tercantum` dan menghasilkan warning. Label fallback ini dapat disesuaikan melalui `firebase.import.missing_writer` di `config/firebase.php`.

## Import Logs

Setiap import non-dry-run membuat satu record `data_imports` berisi:

- SHA-256 checksum file.
- Filename.
- Status proses.
- Total, inserted, updated, skipped, dan failed records.
- Waktu mulai dan selesai.
- Maksimal 100 warning/error pertama dalam `error_summary`.

Status akhirnya adalah `completed`, `completed_with_errors`, atau `failed`. Record invalid tidak menghentikan record atau module berikutnya.

## Migrasi Image Opsional

Setelah data announcement selesai diimport, URL lama tetap tersimpan di `legacy_image_url`. Pindahkan image ke Laravel public storage dengan:

```bash
php artisan firebase:migrate-images --dry-run
php artisan firebase:migrate-images
```

Opsi yang tersedia:

```bash
php artisan firebase:migrate-images --limit=100 --chunk=25 --timeout=15 --retries=3
```

Image migration hanya memilih announcement yang memiliki `legacy_image_url` tetapi belum memiliki `image`, sehingga proses dapat dilanjutkan setelah terhenti. Downloader:

- Hanya menerima URL HTTP/HTTPS non-lokal.
- Memiliki timeout dan retry.
- Membatasi file default maksimal 5 MB.
- Memvalidasi isi dengan MIME sniffing (`JPEG`, `PNG`, `WebP`).
- Menggunakan UUID sebagai filename.
- Mempertahankan `legacy_image_url` sebagai backup.
- Mencatat kegagalan per image tanpa menghentikan seluruh proses.

Konfigurasi directory, disk, ukuran, MIME, timeout, dan retry tersedia di `config/firebase.php`.

## Checklist Production

1. Backup database dan `storage/app/public`.
2. Jalankan dry-run untuk seluruh module.
3. Tinjau warning dan jumlah record.
4. Jalankan import content.
5. Verifikasi beberapa timestamp, slug, hari, nomor telepon, dan legacy key.
6. Jalankan image migration dry-run.
7. Jalankan image migration bertahap dengan `--limit` bila datanya besar.
8. Tinjau tabel `data_imports` dan log aplikasi.

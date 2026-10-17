# Impor tambahan Jemaat Firebase ke production

Command `firebase:import-new-congregations` menerima ekspor users langsung atau JSON dengan node `users`. Command ini hanya menambahkan Firebase UID yang belum ada di database, termasuk pengecekan record lokal yang telah diarsipkan.

Record Jemaat yang sudah ada tidak diubah, dihapus, atau dipulihkan. Akun admin/mobile dan relasi data lama juga tetap utuh. Nama sama dengan Jemaat lama tidak menyebabkan penggabungan; misalnya HC-00194 tetap ada dan HC-00786 ditambahkan.

Deduplikasi nama hanya berlaku antar-record **baru yang akan diimpor**. Nama dibandingkan tanpa membedakan huruf besar/kecil dan dengan spasi yang dirapikan. Jika nama berulang dalam batch baru, hanya NIJ dengan angka tertinggi yang dipakai. Kesamaan telepon atau email tidak dipakai untuk deduplikasi nama. Email yang sudah dipakai akan menghasilkan peringatan dari importer dan dikosongkan pada record baru; email lama tetap utuh.

## Persiapan

1. Deploy commit yang memuat command ini ke VPS dengan alur deploy biasa, termasuk migration profil Jemaat bila belum dijalankan.
2. Backup database production.
3. Upload file ekspor JSON ke direktori privat di luar `public/` dan repository, misalnya `/var/www/hananeel-cinta/imports/firebase-users.json`. File ekspor dapat memuat password/token lama; batasi permission file agar hanya user deployment dapat membacanya.

## Simulasi

Dari folder project di VPS:

```bash
php artisan firebase:import-new-congregations /var/www/hananeel-cinta/imports/firebase-users.json --dry-run --expected=50
```

Simulasi memeriksa data dan menampilkan daftar record baru tanpa menulis Jemaat atau log impor. Untuk ekspor 826 record dan database 776 record yang diperiksa pada 17 Oktober 2026, hasilnya: 776 dilewati, 0 duplikat antar-record baru, dan 50 akan ditambahkan.

Angka production dihitung ulang dari database VPS. `--expected=50` membatalkan proses jika jumlah record terpilih berbeda. Untuk memeriksa perbedaan tanpa menulis data:

```bash
php artisan firebase:import-new-congregations /var/www/hananeel-cinta/imports/firebase-users.json --dry-run
```

## Impor

Setelah hasil simulasi sesuai:

```bash
php artisan firebase:import-new-congregations /var/www/hananeel-cinta/imports/firebase-users.json --expected=50
```

Semua penambahan dan log impor dijalankan dalam satu transaksi; kegagalan salah satu record membatalkan seluruh penambahan. Benturan NIJ dengan identitas lain membatalkan proses, bukan mengubah NIJ atau data yang sudah ada. Password dan FCM token tidak disalin ke batch sementara atau model Jemaat.

Dengan kondisi awal 776 Jemaat, hasil akhirnya 826 Jemaat, tanpa pengarsipan record lama. Impor ulang tanpa `--expected` akan melewati UID yang sudah masuk. Dengan `--expected=50`, pengulangan akan dibatalkan karena jumlah record baru sudah menjadi 0.

Akun mobile bagi UID baru disiapkan oleh middleware saat autentikasi Firebase pertama yang valid; command ini tidak mengubah akun mobile yang sudah ada.

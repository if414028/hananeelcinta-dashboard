# Pemuridan

Menu **Pelayanan → Pemuridan** tersedia untuk Admin dan Super Admin. Permission terpisah: `discipleship.view`, `discipleship.create`, `discipleship.update`, dan `discipleship.complete`. Migration menambahkan permission ke role yang sudah ada tanpa mengubah permission lainnya.

## Alur admin

1. Pilih **Buat Kelas**. Isi nama, deskripsi, pengajar, waktu mulai dan selesai (WIB), banner, serta badge. Gambar JPG/PNG/WebP, maksimal 5 MB per file.
2. Cari jemaat berdasarkan nama atau NIJ, lalu pilih minimal satu peserta. Pilihan tetap tersimpan saat mengganti pencarian atau memuat halaman berikutnya.
3. Setelah kelas dibuat, gunakan **Edit Kelas** atau **Tambah Peserta** untuk memperbarui informasi dan peserta, termasuk ketika kelas sedang berlangsung. Gambar lama dipertahankan jika tidak diganti.
4. Tekan **Selesaikan Kelas** dan konfirmasi. Semua peserta memperoleh badge, ditampilkan di **Jemaat → Detail Jemaat → Badge Pemuridan** beserta tanggal kelulusan.

Berakhirnya jadwal tidak otomatis memberikan badge. Penyelesaian harus dilakukan admin. Setelah diselesaikan, informasi dan peserta kelas dikunci untuk menjaga riwayat kelulusan. Pengiriman tombol penyelesaian berulang tidak menggandakan badge atau mengubah tanggal kelulusan. Edit dan penyelesaian menggunakan transaksi serta penguncian record kelas agar edit yang terlambat tidak mengubah badge yang sudah diberikan.

## Profil mobile

Respons profil dari `GET /api/v1/me`, `POST /api/v1/auth/session`, dan registrasi memiliki field tambahan `data.profile.discipleship_badges` (array kosong jika belum lulus kelas). Setiap item memuat `class_id`, `class_name`, `teacher_name`, `badge_url`, dan `completed_at` (ISO 8601). Hanya badge milik jemaat yang terautentikasi yang dikembalikan. Aplikasi mobile dapat memakai field ini untuk merender badge; source UI mobile tidak berada di repository CMS ini.

## Instalasi pembaruan

Jalankan `php artisan migrate` dan `npm run build`, serta pastikan `php artisan storage:link` sudah dijalankan agar banner dan badge dapat diakses. Tidak perlu menjalankan ulang seeder pada database yang sudah ada.

Pengujian: `php artisan test --filter=DiscipleshipManagementTest` mencakup pembuatan, edit, upload, pencarian/paginasi peserta, validasi, permission, kelulusan, penguncian kelas, badge profil CMS, dan profil API mobile.

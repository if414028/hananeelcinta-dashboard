# Production checklist

Checklist ini wajib ditinjau sebelum dan sesudah go-live.

## Infrastruktur

- [ ] DNS domain dan `www` mengarah ke IP VPS.
- [ ] Firewall hanya membuka SSH, HTTP, dan HTTPS.
- [ ] Login SSH menggunakan key; root/password login dibatasi.
- [ ] Nginx, PHP-FPM 8.4, MySQL, Composer, Node.js, Supervisor, dan cron aktif.
- [ ] Zona waktu server konsisten; aplikasi menggunakan `Asia/Jakarta`.

## Environment

- [ ] `.env` hanya dapat dibaca user deployment dan `www-data`.
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`.
- [ ] `APP_KEY` unik dan sudah dicadangkan aman.
- [ ] Database memakai user khusus dengan hak minimum.
- [ ] `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`.
- [ ] `FRONTEND_ALLOWED_ORIGINS` hanya berisi origin resmi.
- [ ] Firebase project ID benar dan endpoint public key tidak diubah sembarangan.
- [ ] SMTP production dan alamat pengirim telah diuji.
- [ ] Kredensial/backup/service account tidak berada di Git.

## Aplikasi

- [ ] `composer install --no-dev --optimize-autoloader` berhasil.
- [ ] `npm ci && npm run build` berhasil.
- [ ] `php artisan migrate --force` berhasil dan status migration bersih.
- [ ] `php artisan storage:link` tersedia.
- [ ] `php artisan optimize` berhasil.
- [ ] Permission `storage` dan `bootstrap/cache` benar.
- [ ] Super Admin production telah dibuat dan password awal sudah diganti.
- [ ] Semua data hasil migrasi Firebase telah direkonsiliasi.

## HTTPS dan keamanan

- [ ] Sertifikat TLS valid dan auto-renew aktif.
- [ ] HTTP dialihkan ke HTTPS.
- [ ] Setelah HTTPS valid, `SECURITY_HSTS_ENABLED=true`.
- [ ] Login admin, reset password, logout, role, dan permission diuji.
- [ ] Security headers muncul pada website/API.
- [ ] Route admin guest mengarah ke `/admin/login`.
- [ ] Rate limit login, API, mobile auth, dan Prayer Request diuji.
- [ ] Upload menolak tipe/ukuran file yang tidak diizinkan.

## Proses background

- [ ] Supervisor worker berstatus `RUNNING`.
- [ ] Cron `schedule:run` berjalan setiap menit.
- [ ] Scheduler muncul di `php artisan schedule:list`.
- [ ] Failed jobs dan log worker dimonitor.
- [ ] Logrotate terpasang.

## Backup dan recovery

- [ ] Backup database dan `storage/app/public` berjalan harian.
- [ ] File kredensial MySQL backup permission `0600`.
- [ ] Backup disalin ke lokasi/off-site storage terpisah.
- [ ] Retensi sesuai kebijakan organisasi.
- [ ] Restore database dan media pernah diuji di environment non-production.
- [ ] RTO/RPO dan PIC insiden terdokumentasi.

## Verifikasi go-live

- [ ] Homepage, sitemap, robots, halaman konten, dan form Prayer Request normal.
- [ ] CMS CRUD, ekspor, audit log, dan dashboard normal.
- [ ] Seluruh 12 endpoint API v1 diuji melalui Postman.
- [ ] Firebase login mobile, `/auth/session`, dan `/me` berhasil.
- [ ] Foto jemaat legacy tampil atau fallback bekerja.
- [ ] Email reset password masuk.
- [ ] Tidak ada error baru di Nginx/Laravel log.
- [ ] Monitoring uptime memeriksa `/api/v1/health`.

## Setelah deployment

- [ ] Catat commit/tag, waktu deployment, migrasi, dan operator.
- [ ] Pantau error, latency, queue, disk, CPU, memory, serta database minimal 30 menit.
- [ ] Uji rollback versi aplikasi tanpa membatalkan migrasi secara destruktif.
- [ ] Simpan bukti backup sebelum migrasi besar berikutnya.

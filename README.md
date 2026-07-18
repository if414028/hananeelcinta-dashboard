# JKI Hananeel Cinta

Website resmi, CMS admin, dan REST API v1 untuk JKI Hananeel Cinta. Aplikasi dibangun dengan Laravel 13, Blade, Tailwind CSS 4, Alpine.js, MySQL, dan Vite.

## Fitur utama

- Website publik: profil gereja, pengumuman, Pastor Message, Mezbah Keluarga, kontak, dan Prayer Request.
- CMS admin dengan role/permission, audit log, ekspor CSV, dan pengelolaan seluruh konten.
- REST API v1 untuk aplikasi mobile.
- Firebase Auth Bridge: aplikasi mobile tetap login melalui Firebase Auth, lalu profil dan data jemaat dibaca dari database Laravel.
- Import Firebase JSON yang idempotent, mendukung dry-run, filter modul, chunking, log, dan migrasi gambar opsional.
- Security headers, rate limiting, cache publik, queue worker, scheduler, backup, serta paket deployment VPS.

## Kebutuhan lokal

- PHP 8.4 atau lebih baru beserta ekstensi Laravel/MySQL
- Composer 2
- MySQL 8 atau MariaDB yang kompatibel
- Node.js 20+ dan npm

## Instalasi

```bash
composer install
cp .env.example .env
php artisan key:generate

# Sesuaikan DB_* di .env, lalu:
php artisan migrate --seed
php artisan storage:link

npm install
npm run build
php artisan serve
```

Website tersedia di `http://127.0.0.1:8000`, CMS di `/admin/login`, dan API di `/api/v1`.

Untuk development terpadu:

```bash
composer run dev
```

## Worker dan scheduler

Jalankan proses berikut pada terminal terpisah ketika mengembangkan fitur queue/scheduler:

```bash
php artisan queue:work
php artisan schedule:work
```

Di production, gunakan Supervisor dan cron yang disediakan dalam folder `deploy/`.

## Quality gate

```bash
vendor/bin/pint --test
php artisan test
npm run build
composer audit
```

## Firebase migration

```bash
# Simulasi semua modul
php artisan firebase:import /path/firebase-export.json --dry-run

# Import aktual modul tertentu
php artisan firebase:import /path/firebase-export.json --only=congregations

# Daftar opsi lengkap
php artisan help firebase:import
```

Import aman dijalankan ulang karena memakai Firebase key/UID sebagai identitas legacy. Simpan file ekspor dan kredensial Firebase di luar repository.

## Dokumentasi

- [REST API v1](docs/api-v1.md)
- [Postman collection](docs/postman/JKI-Hananeel-Cinta-API.postman_collection.json)
- [Firebase JSON migration](docs/firebase-migration.md)
- [Firebase Auth Bridge](docs/firebase-auth-bridge.md)
- [Deployment Hostinger VPS](docs/deployment-hostinger-vps.md)
- [Production checklist](docs/production-checklist.md)

## Struktur operasional

```text
app/              Domain, controllers, middleware, services
database/         Migrations, factories, dan seeders
resources/        Blade views, CSS, dan JavaScript
routes/           Web, admin, API, dan scheduler
docs/             Dokumentasi teknis dan operasional
deploy/           Nginx, Supervisor, logrotate, deploy, backup
tests/            Feature dan unit tests
```

## Keamanan

- Jangan commit `.env`, Firebase service-account JSON, database dump, atau backup.
- Gunakan `APP_DEBUG=false`, HTTPS, secure session cookie, dan HSTS di production.
- Token Firebase dikirim melalui `Authorization: Bearer <ID_TOKEN>` dan diverifikasi terhadap project ID yang dikonfigurasi.
- Reset password admin memerlukan mailer production yang valid.

Laporkan insiden keamanan langsung kepada pengelola sistem JKI Hananeel Cinta, bukan melalui issue publik.

# Deployment ke Hostinger VPS

Panduan ini ditujukan untuk Ubuntu VPS dengan Nginx, PHP-FPM 8.4, MySQL, Supervisor, dan domain `hananeelcinta.id`. Sesuaikan domain, user, repository, serta path jika berbeda.

## 1. Persiapan server

Masuk menggunakan user sudo, update paket, lalu pasang kebutuhan aplikasi:

```bash
sudo apt update
sudo apt upgrade -y
sudo apt install -y nginx mysql-server supervisor git unzip curl \
  php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml \
  php8.4-curl php8.4-zip php8.4-gd php8.4-bcmath php8.4-intl
```

Pasang Composer 2 dari installer resmi dan Node.js LTS dari sumber yang dipercaya. Verifikasi:

```bash
php -v
composer --version
node --version
npm --version
```

## 2. Database

Jalankan hardening MySQL:

```bash
sudo mysql_secure_installation
```

Buat database dan user khusus:

```sql
CREATE DATABASE hananeel_cinta
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER 'hananeel_app'@'localhost' IDENTIFIED BY 'PASSWORD_ACAK_PANJANG';
GRANT ALL PRIVILEGES ON hananeel_cinta.* TO 'hananeel_app'@'localhost';
FLUSH PRIVILEGES;
```

## 3. Ambil source

Contoh struktur sederhana:

```bash
sudo mkdir -p /var/www/hananeel-cinta
sudo chown "$USER":www-data /var/www/hananeel-cinta
git clone <REPOSITORY_URL> /var/www/hananeel-cinta/current
cd /var/www/hananeel-cinta/current
```

Copy environment dan generate key hanya pada instalasi pertama:

```bash
cp .env.example .env
php artisan key:generate
chmod 640 .env
```

Isi nilai production:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://hananeelcinta.id
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=hananeel_cinta
DB_USERNAME=hananeel_app
DB_PASSWORD=PASSWORD_ACAK_PANJANG

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=database

FRONTEND_ALLOWED_ORIGINS=https://hananeelcinta.id,https://www.hananeelcinta.id
FIREBASE_PROJECT_ID=jki-hananeel-cinta

MAIL_MAILER=smtp
MAIL_HOST=<SMTP_HOST>
MAIL_PORT=587
MAIL_USERNAME=<SMTP_USERNAME>
MAIL_PASSWORD=<SMTP_PASSWORD>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@hananeelcinta.id
MAIL_FROM_NAME="${APP_NAME}"

SECURITY_HSTS_ENABLED=false
```

HSTS sengaja dinonaktifkan sampai sertifikat HTTPS valid.

## 4. Build pertama

```bash
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
npm ci
npm run build

php artisan migrate --force
php artisan storage:link
php artisan optimize

sudo chown -R "$USER":www-data /var/www/hananeel-cinta/current
sudo find storage bootstrap/cache -type d -exec chmod 2775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

Jangan memberi permission `777`.

## 5. Nginx

Copy template:

```bash
sudo cp deploy/nginx/hananeel-cinta.conf /etc/nginx/sites-available/hananeel-cinta
sudo ln -s /etc/nginx/sites-available/hananeel-cinta /etc/nginx/sites-enabled/hananeel-cinta
sudo nginx -t
sudo systemctl reload nginx
```

Pastikan socket `php8.4-fpm.sock` pada template sesuai dengan server:

```bash
ls /run/php/
```

## 6. HTTPS

Pastikan DNS `A` untuk root domain dan `www` telah mengarah ke VPS. Pasang Certbot sesuai metode resmi Hostinger/Certbot untuk OS VPS, lalu:

```bash
sudo certbot --nginx -d hananeelcinta.id -d www.hananeelcinta.id
sudo certbot renew --dry-run
```

Setelah HTTPS dan redirect berfungsi, ubah:

```dotenv
SECURITY_HSTS_ENABLED=true
```

Lalu jalankan `php artisan optimize`.

## 7. Queue worker

```bash
sudo cp deploy/supervisor/hananeel-cinta-worker.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status hananeel-cinta-worker:*
```

Worker harus berstatus `RUNNING`. Setiap deployment menjalankan `queue:restart` agar worker membaca kode terbaru.

## 8. Scheduler

Edit crontab untuk user `www-data`:

```bash
sudo crontab -u www-data -e
```

Tambahkan tepat satu entry:

```cron
* * * * * cd /var/www/hananeel-cinta/current && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Verifikasi:

```bash
sudo -u www-data php artisan schedule:list
```

## 9. Logrotate

```bash
sudo cp deploy/logrotate/hananeel-cinta /etc/logrotate.d/hananeel-cinta
sudo logrotate -d /etc/logrotate.d/hananeel-cinta
```

## 10. Backup

Buat file `/etc/mysql/hananeel-backup.cnf`:

```ini
[client]
user=hananeel_backup
password=PASSWORD_BACKUP
host=localhost
```

Buat user MySQL backup dengan hak minimum yang memadai, lalu:

```bash
sudo chmod 600 /etc/mysql/hananeel-backup.cnf
sudo mkdir -p /var/backups/hananeel-cinta
sudo chmod +x /var/www/hananeel-cinta/current/deploy/scripts/backup.sh
sudo /var/www/hananeel-cinta/current/deploy/scripts/backup.sh
```

Jadwalkan backup harian, misalnya pukul 01:30:

```cron
30 1 * * * /var/www/hananeel-cinta/current/deploy/scripts/backup.sh >> /var/log/hananeel-backup.log 2>&1
```

Salin backup ke storage off-site dan lakukan restore drill berkala. Backup lokal pada VPS yang sama tidak cukup untuk disaster recovery.

## 11. Deployment update

Sebelum update, buat backup dan catat commit saat ini. Setelah source terbaru tersedia:

```bash
cd /var/www/hananeel-cinta/current
git pull --ff-only
chmod +x deploy/scripts/deploy.sh
./deploy/scripts/deploy.sh
```

Script mengaktifkan maintenance mode, memasang dependency production, build asset, menjalankan migration, mengoptimalkan Laravel, merestart worker, dan selalu mencoba mengembalikan aplikasi dari maintenance mode jika terjadi error.

## 12. Verifikasi

```bash
curl -I https://hananeelcinta.id
curl -s https://hananeelcinta.id/api/v1/health
php artisan migrate:status
php artisan schedule:list
sudo supervisorctl status
tail -n 100 storage/logs/laravel.log
```

Uji login admin, reset password, CMS CRUD, upload, Prayer Request, seluruh endpoint Postman, Firebase `/auth/session`, dan `/me`.

## Rollback

Rollback aplikasi dilakukan dengan kembali ke tag/commit stabil lalu menjalankan ulang install/build/optimize. Jangan menjalankan `migrate:rollback` otomatis di production: perubahan schema/data harus ditinjau per migration. Jika migration destruktif gagal, restore database dan media dari backup yang sudah diuji.

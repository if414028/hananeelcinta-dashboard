# Firebase Auth Bridge

Phase 6.5 mempertahankan Firebase Authentication sebagai identity provider mobile, sementara Laravel menjadi sumber utama data jemaat. Akun admin CMS dan akun mobile tetap terpisah.

## Alur Autentikasi

1. Android/iOS login menggunakan Firebase Authentication SDK.
2. Mobile mengambil Firebase ID token terbaru.
3. Mobile mengirim token melalui header `Authorization: Bearer <firebase-id-token>`.
4. Laravel memverifikasi signature RS256 menggunakan public certificates Google.
5. Claim `aud`, `iss`, `sub`, `exp`, `iat`, dan `auth_time` divalidasi.
6. Claim `sub`/UID dicocokkan dengan `congregations.legacy_firebase_uid`.
7. Laravel membuat atau memperbarui `mobile_accounts`, lalu memberikan akses ke endpoint terproteksi.

UID yang dikirim sebagai body atau query parameter tidak pernah dipercaya. Laravel hanya menggunakan UID dari token yang telah terverifikasi.

## Konfigurasi

```env
FIREBASE_PROJECT_ID=jki-hananeel-cinta
FIREBASE_PUBLIC_KEYS_URL=https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com
FIREBASE_PUBLIC_KEYS_CACHE_TTL=3600
FIREBASE_AUTH_HTTP_TIMEOUT=5
FIREBASE_AUTH_LEEWAY=30
```

Public certificates dicache sesuai `Cache-Control` Google, dengan batas TTL dari konfigurasi. Service account tidak diperlukan untuk verifikasi signature dasar. Implementasi ini tidak memeriksa revocation token ke Firebase Admin API; akun tetap dapat dinonaktifkan segera melalui `mobile_accounts.is_active` atau `congregations.is_active`.

## Sinkronisasi Akun

Dry-run:

```bash
php artisan mobile-accounts:sync --dry-run
```

Sinkronisasi aktual:

```bash
php artisan mobile-accounts:sync
```

Command bersifat idempotent dan hanya memproses jemaat yang memiliki `legacy_firebase_uid`. Password, ID token, refresh token, dan FCM token tidak disimpan.

## Endpoint

### Membuka session Laravel

```http
POST /api/v1/auth/session
Authorization: Bearer <firebase-id-token>
Accept: application/json
X-App-Platform: android
X-App-Version: 1.0.0
```

### Mengambil profil

```http
GET /api/v1/me
Authorization: Bearer <firebase-id-token>
Accept: application/json
```

Kedua endpoint mengembalikan account identity dan profil jemaat. Field internal seperti notes, legacy metadata, audit user, dan permission admin tidak dikembalikan.

## Integrasi Mobile

Firebase SDK harus mengambil ID token terbaru sebelum memanggil API. Bila API mengembalikan `401`, refresh token melalui Firebase SDK dan ulangi request satu kali. Bila tetap gagal, arahkan pengguna untuk login ulang.

Logout tetap dilakukan melalui Firebase SDK. Laravel tidak menyimpan session atau refresh token Firebase.

## Kode Status

| Status | Arti |
|---|---|
| `200` | Token valid dan akun terhubung |
| `401` | Token kosong, invalid, expired, atau akun auth tidak tersedia |
| `403` | UID valid tetapi tidak terhubung atau profil jemaat nonaktif |
| `429` | Rate limit terlampaui |
| `503` | Public certificates Firebase sementara tidak dapat diakses |

## Keamanan

- Jangan mencatat bearer token ke log.
- Gunakan HTTPS di production.
- Jangan menaruh service account JSON di repository.
- Batasi akses admin terhadap data jemaat.
- Rotasi credential bila service account ditambahkan pada phase berikutnya.
- Firebase Storage rules harus ditinjau karena foto profil saat ini dapat diakses melalui URL object.

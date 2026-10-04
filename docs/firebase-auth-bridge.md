# Firebase Auth Bridge

API mobile mempertahankan Firebase Authentication sebagai identity provider mobile, sementara Laravel menjadi sumber utama data jemaat. Akun admin CMS dan akun mobile tetap terpisah.

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

### Register jemaat baru

Aplikasi membuat akun melalui Firebase SDK terlebih dahulu (misalnya `createUserWithEmailAndPassword`), kemudian mengambil Firebase ID token. API ini membuat profil di CMS dan menghubungkannya ke akun Firebase; API tidak membuat akun/password di Firebase.

```http
POST /api/v1/auth/register
Authorization: Bearer <firebase-id-token>
Accept: application/json
Content-Type: application/json
X-App-Platform: ios
X-App-Version: 1.0.0
```

```json
{
  "full_name": "Maria Santoso",
  "gender": "female",
  "nickname": "Maria",
  "place_of_birth": "Jakarta",
  "date_of_birth": "1995-06-20",
  "phone_number": "08123456789",
  "address": "Jalan Gereja 1",
  "city": "Jakarta",
  "blood_type": "AB",
  "last_education": "D1, D2, D3",
  "occupation": "Wiraswasta",
  "marital_status": "married",
  "baptism_status": "baptized",
  "baptism_date": "2015-01-02",
  "baptism_church": "Gereja tempat baptis",
  "holy_spirit_baptism": true,
  "church_origin": "Gereja asal",
  "reason_to_move_church": "Pindah domisili",
  "family_status": "Istri",
  "husband_name": "Nama suami",
  "children_names": ["Anak pertama", "Anak kedua"],
  "siblings_names": ["Saudara pertama", "Saudara kedua"]
}
```

Tanpa foto, kirim JSON. Dengan foto, kirim `multipart/form-data` dan file JPEG pada `profile_photo`, maksimal 5 MiB (5120 KiB). Foto disimpan di disk public CMS pada direktori `congregations`; URL dikembalikan sebagai `profile_photo_url`. Pastikan `php artisan storage:link` sudah tersedia pada deployment. Biarkan HTTP client menentukan boundary multipart.

| Field wajib | Ketentuan |
|---|---|
| `full_name` | String, maksimal 255 karakter |
| `gender` | `male` atau `female` |
| `place_of_birth` | String, maksimal 100 karakter |
| `date_of_birth` | `YYYY-MM-DD`, sebelum hari ini |
| `phone_number` | String angka dan simbol telepon `+() .-`, 7–30 karakter setelah normalisasi awalan `0` menjadi `+62` |
| `address` | String alamat, maksimal 2000 karakter |
| `blood_type` | `A`, `B`, `AB`, atau `O` |
| `last_education` | String label pendidikan, maksimal 100 karakter; label lama SD, SMP, SMA, `D1, D2, D3`, S1, S2, S3, Lainnya dipertahankan |
| `occupation` | String, maksimal 150 karakter; boleh memuat detail pekerjaan |
| `marital_status` | `single`, `married`, `widowed`, `divorced` |

| Field opsional | Ketentuan |
|---|---|
| `nickname` | Maksimal 100 karakter |
| `whatsapp_number` | 7–30 karakter angka dan simbol telepon `+() .-` |
| `city`, `province` | Maksimal 100 karakter |
| `postal_code` | Maksimal 10 karakter |
| `baptism_status` | `unknown` (default), `not_baptized`, `baptized` |
| `baptism_date` | Wajib jika `baptism_status=baptized`; `YYYY-MM-DD`, tidak di masa depan; hanya berlaku untuk status baptized |
| `baptism_church` | String, maksimal 255 karakter; tetap disimpan untuk status selain baptized |
| `holy_spirit_baptism` | Boolean JSON; multipart menggunakan `1`/`0`; jika belum dipilih, hilangkan field |
| `church_origin` | String, maksimal 255 karakter |
| `reason_to_move_church` | String, maksimal 2000 karakter |
| `family_status` | String label keluarga, maksimal 100 karakter: Kepala Keluarga, Istri, Anak, Janda, Duda, Single - Belum Menikah |
| `wife_name`, `husband_name` | String, masing-masing maksimal 255 karakter |
| `children_names`, `siblings_names` | Array string, masing-masing nama maksimal 255 karakter |
| `profile_photo` | File JPEG, maksimal 5 MiB; hanya dikirim melalui multipart |

Field teks opsional yang kosong diabaikan. Nama anak/saudara menggunakan array JSON atau field multipart berulang `children_names[]` / `siblings_names[]`; entri teks kosong diabaikan. Nilai `holy_spirit_baptism=false` atau multipart `0` tetap disimpan.

Untuk `family_status=Kepala Keluarga`, `husband_name` diabaikan; untuk Istri, `wife_name` diabaikan; untuk Anak, `children_names` diabaikan. Field tersembunyi ini tidak divalidasi atau disimpan meskipun dikirim. Status keluarga lainnya menerima ketiga field.

UID dan email berasal dari token Firebase, termasuk untuk akun tanpa email (misalnya login telepon). Email body diabaikan. NIJ dibuat otomatis; `membership_status` awal `visitor`, `is_active=true`, dan `joined_at` hari registrasi. Field internal/admin dan field lain di luar daftar validasi diabaikan. Status keanggotaan dapat diubah oleh admin melalui CMS.

Response `201` memakai struktur `data.account` dan `data.profile` yang sama dengan `/me` dan `/auth/session`. Semua field tambahan dikembalikan pada `data.profile`; `holy_spirit_baptism` berupa boolean atau `null` jika belum dipilih, dan `children_names` / `siblings_names` selalu berupa array (default `[]`). Bentuk alamat tetap objek `address.street`, `address.city`, `address.province`, dan `address.postal_code`. Tanpa foto CMS atau URL foto legacy yang tersimpan, `profile_photo_url=null`; API tidak membuat URL Firebase Storage berdasarkan UID.

Registrasi menyimpan profil serta mobile account dalam satu transaksi. Jika transaksi gagal, foto upload dibersihkan. Jika foto gagal disimpan, registrasi gagal tanpa membuat profil/account. Request ulang dengan payload valid untuk UID yang sudah terdaftar menghasilkan `409` tanpa mengubah profil atau foto; akun/profil terhapus atau nonaktif tidak dibuat ulang.

Jika email dari token sudah dipakai profil lain, API mengembalikan `409`. Pengguna harus menghubungi admin gereja untuk menghubungkan UID yang benar; API tidak menghubungkan profil hanya berdasarkan kecocokan email.

### Bentuk response profil

Contoh struktur response `/me` (field profil lainnya juga tersedia sesuai resource):

```json
{
  "success": true,
  "message": "Mobile profile retrieved.",
  "data": {
    "account": {
      "id": 1,
      "uid": "firebase-uid",
      "email": "maria@example.com",
      "email_verified": true,
      "providers": ["password"],
      "authenticated_at": "2026-10-02T15:00:00+00:00"
    },
    "profile": {
      "id": 1,
      "member_number": "HC-2026-00001",
      "full_name": "Maria Santoso",
      "role": "User",
      "gender": "female",
      "email": "maria@example.com",
      "address": {
        "street": "Jalan Gereja 1",
        "city": "Jakarta",
        "province": null,
        "postal_code": null
      },
      "membership_status": "visitor",
      "is_active": true
    }
  }
}
```

`data.account` berasal dari identitas Firebase yang terverifikasi. `data.profile` berasal dari CMS, sehingga perubahan nama/alamat oleh admin tersedia pada request berikutnya. Email profil CMS dan email akun Firebase dapat berbeda jika data profil diedit oleh admin.

### Role admin mobile

`data.profile.role` tersedia pada response `/auth/session`, `/me`, dan `/auth/register`:

- `SuperUser`: jemaat terhubung ke akun Admin Users CMS yang aktif, belum dihapus, dan memiliki role CMS (Super Admin, Admin, atau Pastor).
- `User`: jemaat tidak memiliki akun admin aktif dengan role CMS.

Untuk menjadikan jemaat admin, buka **Admin Users → Tambah data**, pilih **Hubungkan ke jemaat**, lalu isi role CMS dan password CMS. Nama/email otomatis diisi dari jemaat dan dapat disesuaikan. Untuk akun admin CMS yang sudah ada, pilih jemaat melalui **Edit**. Satu jemaat hanya dapat terhubung ke satu akun admin. Admin CMS tanpa hubungan jemaat tetap dapat digunakan seperti sebelumnya.

Status mobile dihitung dari hubungan `users.congregation_id`, bukan kecocokan email atau catatan `Role Firebase` hasil impor. Menonaktifkan, menghapus, atau melepas hubungan jemaat dari admin mengembalikan role mobile menjadi `User` pada request berikutnya. Menghapus admin juga melepaskan hubungan agar jemaat dapat dipilih untuk akun admin baru. Nilai `SuperUser` di mobile tidak mengubah permission role CMS; login mobile tetap memakai token Firebase.

## Integrasi Mobile

Firebase SDK harus mengambil ID token terbaru sebelum memanggil API. Bila API mengembalikan `401`, refresh token melalui Firebase SDK dan ulangi request satu kali. Bila tetap gagal, arahkan pengguna untuk login ulang.

Untuk login akun yang sudah terdaftar, panggil `GET /api/v1/me` atau `POST /api/v1/auth/session` setelah login Firebase berhasil. Untuk signup, panggil `POST /api/v1/auth/register` dengan profil pengguna setelah signup Firebase berhasil. Jangan mengirim password Firebase ke CMS.

Jika request registrasi timeout, coba `/me` untuk memastikan apakah profil sudah tersimpan. Bila retry registrasi menghasilkan `409`, coba `/me`; jika profil tetap tidak tersedia, tampilkan pesan menghubungi admin. Jangan menghapus akun Firebase otomatis ketika registrasi CMS gagal: token/akun yang sama dapat digunakan untuk retry setelah masalah jaringan atau validasi selesai. `403` saat login dapat berarti profil belum terhubung atau akun nonaktif; jangan menganggap semua `403` sebagai izin membuat profil baru.

Logout tetap dilakukan melalui Firebase SDK. Laravel tidak menyimpan session atau refresh token Firebase.

## Kode Status

| Status | Arti |
|---|---|
| `200` | Token valid dan akun terhubung |
| `201` | Profil jemaat dan mobile account berhasil dibuat |
| `401` | Token kosong, invalid, expired, atau akun auth tidak tersedia |
| `403` | UID valid tetapi tidak terhubung atau profil jemaat nonaktif |
| `409` | UID/email sudah terdaftar; profil tidak ditimpa |
| `422` | Data registrasi tidak valid; lihat field `errors` |
| `429` | Rate limit terlampaui (30 request/menit/IP) |
| `503` | Public certificates Firebase sementara tidak dapat diakses |

## Keamanan

- Jangan mencatat bearer token ke log.
- Gunakan HTTPS di production.
- Jangan menaruh service account JSON di repository.
- Batasi akses admin terhadap data jemaat.
- Rotasi credential bila service account ditambahkan pada phase berikutnya.
- Firebase Storage rules harus ditinjau karena foto profil saat ini dapat diakses melalui URL object.

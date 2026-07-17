# REST API v1

Base URL production:

```text
https://hananeelcinta.id/api/v1
```

Semua response menggunakan JSON. Kirim header berikut:

```http
Accept: application/json
Content-Type: application/json
X-App-Platform: android
X-App-Version: 1.0.0
X-Request-Id: optional-client-generated-id
```

## Format response

Sukses:

```json
{
  "success": true,
  "message": "Data retrieved successfully.",
  "data": {}
}
```

Error validasi:

```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "field": ["Pesan validasi."]
  }
}
```

List menggunakan `meta` (`current_page`, `last_page`, `per_page`, `total`) dan `links` (`first`, `last`, `prev`, `next`). Nilai `per_page` default 15 dan maksimum 50.

## Endpoint

| Method | Path | Auth | Keterangan |
|---|---|---|---|
| GET | `/health` | Tidak | Health check dan versi API |
| GET | `/config` | Tidak | Konfigurasi publik gereja/mobile |
| GET | `/home` | Tidak | Konten ringkas halaman utama |
| GET | `/announcements` | Tidak | Daftar pengumuman terbit |
| GET | `/announcements/{slug}` | Tidak | Detail pengumuman |
| GET | `/pastor-messages` | Tidak | Daftar Pastor Message terbit |
| GET | `/pastor-messages/{slug}` | Tidak | Detail Pastor Message |
| GET | `/family-altars` | Tidak | Daftar Mezbah Keluarga aktif |
| GET | `/family-altars/{id}` | Tidak | Detail Mezbah Keluarga |
| POST | `/prayer-requests` | Tidak | Kirim Prayer Request |
| POST | `/auth/session` | Firebase | Sinkronisasi sesi/profil mobile |
| GET | `/me` | Firebase | Profil mobile saat ini |

### Filter list

`GET /announcements`

- `page`, `per_page`
- `search`
- `featured=true|false`
- `published_after=YYYY-MM-DD`
- `published_before=YYYY-MM-DD`

`GET /pastor-messages`

- `page`, `per_page`
- `search`
- `writer`
- `featured=true|false`
- `published_after=YYYY-MM-DD`
- `published_before=YYYY-MM-DD`

`GET /family-altars`

- `page`, `per_page`
- `search`
- `city`
- `day`: `monday`, `tuesday`, `wednesday`, `thursday`, `friday`, `saturday`, atau `sunday`

### Prayer Request

`POST /prayer-requests`

```json
{
  "name": "Nama Pengirim",
  "email": "pengirim@example.com",
  "phone_number": "+628123456789",
  "prayer_category": "other",
  "prayer_content": "Mohon dukungan doa untuk keluarga kami.",
  "is_anonymous": false,
  "is_confidential": true,
  "privacy_accepted": true,
  "client_platform": "android"
}
```

Ketentuan:

- `name`, `prayer_category`, `prayer_content`, dan `privacy_accepted` wajib.
- `prayer_content` 10–5000 karakter.
- `client_platform` opsional: `android` atau `ios`.
- Response `201` berisi `reference_number`, `status`, dan `submitted_at`.

### Firebase Auth Bridge

Untuk `/auth/session` dan `/me`, ambil Firebase ID token setelah pengguna login di aplikasi mobile:

```http
Authorization: Bearer <FIREBASE_ID_TOKEN>
```

Server memverifikasi signature, issuer, audience/project ID, waktu berlaku, serta UID. Akun mobile dipetakan ke jemaat melalui `legacy_firebase_uid`. Firebase tetap menjadi identity provider; data profil authoritative berada di database Laravel.

`POST /auth/session` dipanggil sesudah login/token refresh untuk menyinkronkan metadata akun. `GET /me` mengambil profil terbaru. Lihat [Firebase Auth Bridge](firebase-auth-bridge.md) untuk alur lengkap dan penanganan kasus UID yang belum dipetakan.

## Rate limit

- Public API: 60 request/menit/IP.
- Prayer Request: 5 request/10 menit/IP.
- Mobile auth: 30 request/menit/IP.

Ketika batas terlampaui, server mengembalikan `429 Too Many Requests`. Klien harus menghormati header `Retry-After` dan menggunakan exponential backoff.

## Status HTTP

- `200` berhasil
- `201` Prayer Request dibuat
- `401` token Firebase hilang/tidak valid/kedaluwarsa
- `403` akun mobile nonaktif, konflik mapping, atau UID belum dipetakan
- `404` resource tidak ditemukan
- `422` validasi gagal
- `429` rate limit terlampaui
- `500` kesalahan server

## Cache

`/config` dan `/home` dapat di-cache publik selama 5 menit dan mengirim ETag. Klien disarankan menyimpan ETag dan melakukan conditional request. Endpoint profil/auth selalu `private, no-store`.

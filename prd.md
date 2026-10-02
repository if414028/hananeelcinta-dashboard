# Product Requirements Document

## Product Overview

Product: Hananeel Cinta CMS — Website, CMS Admin, dan REST API JKI Hananeel Cinta.

Goal: Memusatkan pengelolaan informasi gereja, data jemaat, pelayanan doa, dan kegiatan gereja agar pengurus dapat bekerja lebih efisien serta jemaat dapat mengakses informasi dan layanan melalui website maupun aplikasi mobile.

Dokumen ini merangkum cakupan implementasi project saat ini per 2 Oktober 2026, termasuk perubahan lokal yang ada di workspace. Backend menggunakan Laravel dan database MySQL; website serta CMS menggunakan Blade, Tailwind CSS, dan Alpine.js. Project menyediakan API untuk aplikasi mobile, sementara autentikasi pengguna mobile tetap menggunakan Firebase Auth.

## Problem

Pengurus gereja membutuhkan satu sistem untuk memperbarui informasi publik, menjaga data jemaat, dan menindaklanjuti permohonan doa secara terstruktur. Pengelolaan melalui sumber data dan proses yang terpisah menyulitkan konsistensi informasi, pembagian tanggung jawab pelayanan, serta pencatatan hasil tindak lanjut.

Jemaat dan pengunjung membutuhkan akses yang mudah ke profil gereja, pengumuman, pesan pastoral, kelompok Mezbah Keluarga, serta pendaftaran kegiatan. Data lama dari Firebase juga perlu dipindahkan ke database terpusat tanpa duplikasi, sambil mempertahankan akses login aplikasi mobile.

## Target Users

- Jemaat: mengakses informasi gereja, mengirim permohonan doa, dan mendaftar kegiatan.
- Pengunjung dan calon jemaat: mengenal gereja, menemukan kontak serta Mezbah Keluarga, dan mengakses layanan publik.
- Admin gereja: mengelola data jemaat, konten, kegiatan, dan pengaturan sesuai izin yang diberikan.
- Pastor dan pelayan doa: mengambil tanggung jawab permohonan doa, memperbarui status, dan mencatat hasil doa.
- Petugas kegiatan: memverifikasi tiket peserta dan mencatat kehadiran sesuai izin akses.
- Super Admin: mengelola akun admin, role, permission, pengaturan website, dan audit aktivitas.
- Tim teknis: mengintegrasikan aplikasi mobile, melakukan migrasi Firebase, dan menjalankan operasional sistem.

## Core Features

- Website publik: halaman utama, profil gereja, kontak, pengumuman, Pastor Message, Mezbah Keluarga, kebijakan privasi, dan ketentuan penggunaan; dilengkapi sitemap untuk penemuan konten.
- Pengelolaan konten: membuat, mengubah, menghapus, dan menerbitkan pengumuman serta Pastor Message, termasuk konten rich text, gambar, status publikasi, dan penandaan konten unggulan.
- Pengelolaan data jemaat: profil, nomor anggota otomatis, kontak, foto, status keanggotaan, informasi baptisan, status aktif, pencarian, filter, dan ekspor CSV.
- Pelayanan Prayer Request: pengiriman dari website atau API dengan nomor referensi, kategori, opsi anonim, opsi rahasia, dan persetujuan privasi. CMS menyediakan papan status Open → Sedang Didoakan → Selesai, pencarian, filter kategori, penanggung jawab, hasil doa wajib saat penyelesaian, dan ekspor CSV sesuai akses. Permohonan yang sudah diambil hanya dapat dibuka dan ditindaklanjuti oleh penanggung jawabnya; akses permohonan rahasia memerlukan izin khusus.
- Pengelolaan Mezbah Keluarga: nama kelompok, deskripsi, jadwal, lokasi, peta, penanggung jawab, kontak, gambar, urutan tampil, dan status aktif.
- Pengelolaan event: membuat dan menerbitkan kegiatan dengan jadwal, deskripsi, gambar, serta formulir pendaftaran yang field-nya dapat dikonfigurasi. Peserta memperoleh tiket dengan kode unik dan QR yang dapat diunduh; petugas dapat mencari peserta, memindai atau memverifikasi tiket, serta mencatat check-in.
- Dashboard operasional: ringkasan jemaat, konten, dan permohonan doa; aktivitas terbaru; distribusi status; serta tren pertumbuhan jemaat dan permohonan doa bulanan.
- Autentikasi dan otorisasi admin: login, logout, reset password, pemeriksaan akun aktif, role Super Admin/Admin/Pastor, permission per modul dan tindakan, serta audit log aktivitas.
- Pengaturan website: konfigurasi informasi dan konten website melalui CMS dengan validasi, sanitasi rich text, dan pembaruan cache pengaturan.
- REST API v1 dan Firebase Auth Bridge: akses konten publik, konfigurasi, halaman utama, pengiriman permohonan doa, sinkronisasi sesi mobile, serta profil jemaat berdasarkan Firebase UID yang terverifikasi. Endpoint daftar mendukung pagination dan filter; data profil jemaat bersumber dari database Laravel.
- Migrasi dan operasional: import Firebase JSON yang dapat dijalankan ulang tanpa duplikasi, dry-run, filter modul, pemrosesan bertahap, log import, serta migrasi gambar opsional. Project juga menyediakan rate limiting, security headers, cache publik, queue/scheduler, dokumentasi API, dan paket deployment serta backup VPS.

## Success Metrics

Metrik berikut merupakan usulan evaluasi produk, bukan laporan capaian yang sudah diukur. Baseline, target angka, dan periode evaluasi perlu ditetapkan bersama pengelola gereja setelah pengukuran operasional tersedia.

- Efisiensi publikasi konten: waktu median sejak konten mulai disiapkan di CMS hingga terbit dan dapat diakses melalui website/API.
- Kualitas data jemaat: persentase profil aktif yang memiliki data wajib dan kontak lengkap, serta jumlah duplikasi yang ditemukan.
- Kecepatan tindak lanjut doa: waktu median dari permohonan masuk hingga berstatus Sedang Didoakan, serta jumlah permohonan Open yang belum ditangani.
- Penyelesaian pelayanan doa: persentase permohonan yang selesai dengan hasil doa tercatat dalam periode evaluasi.
- Keberhasilan pendaftaran dan kehadiran event: persentase pengiriman formulir valid yang menghasilkan tiket, serta rasio peserta check-in terhadap total pendaftaran per event.
- Keandalan website dan integrasi mobile: uptime, waktu respons, tingkat kegagalan API, dan tingkat keberhasilan akses sesi/profil dengan token Firebase yang valid serta mapping jemaat yang sesuai.
- Keberhasilan migrasi: persentase record yang berhasil diimpor, jumlah kegagalan dan konflik mapping, serta tidak adanya penambahan duplikasi saat import yang sama diulang.
- Kepuasan pengguna: penilaian jemaat, admin, pastor, dan petugas terhadap kemudahan mengakses informasi, mengelola data, serta menyelesaikan tugas pelayanan.

# Rencana Migrasi Website LSP FIT ke Laravel

## Tujuan dan batasan

Memindahkan kode website LSP FIT dari HTML/JavaScript dan endpoint PHP mandiri ke struktur Laravel agar routing, tampilan, validasi, autentikasi, dan pengelolaan konten lebih tertata. **Database MySQL yang digunakan saat ini tetap dipakai beserta data, nama tabel, dan nama kolomnya.** Migrasi ini adalah migrasi aplikasi, bukan migrasi skema database.

Dokumen ini disusun dari kode dalam repositori pada 9 Oktober 2026. Sebelum implementasi, cocokkan `database/schema.sql` dengan struktur database yang benar-benar aktif di server, karena database produksi dapat berbeda dari berkas lokal.

## Fitur yang sudah ada

### Halaman publik

| Halaman | Fitur saat ini | Sumber data |
| --- | --- | --- |
| Beranda (`/`) | Hero, profil singkat, bagian kegiatan, instruktur/struktur, mitra, CTA; teks, gambar, visibilitas, dan urutan bagian dapat diatur | `site_settings`, serta data modul terkait |
| Tentang (`/aboutus`) | Profil lembaga, visi dan misi, sambutan pimpinan, foto pimpinan, video profil | `site_settings` |
| Struktur (`/anggota`) | Daftar anggota/tim yang aktif | `anggota` |
| Instruktur & asesor (`/instruktur`, `/asesor`) | Daftar tenaga profesional dan aset foto | `instruktur` serta aset statis yang masih dipakai halaman |
| Skema sertifikasi (`/kegiatan`) | Daftar skema/kegiatan, pengelompokan, highlight, dan detail melalui `?id=` | `kegiatan` |
| Galeri (`/gallery`) | Halaman galeri yang sudah tersedia sebagai HTML | Aset halaman |
| Kontak (`/kontak`) | Informasi kontak dan formulir yang membuka WhatsApp dengan pesan terisi | `site_settings` dan JavaScript; **tidak menyimpan pendaftaran ke database** |

Catatan: nama tabel `kegiatan` saat ini dipakai juga untuk konten **skema sertifikasi**. Nama tabel tetap `kegiatan` walaupun nama modul/tampilan dapat disebut “Skema Sertifikasi”. Klaim “pendaftaran” pada deskripsi umum proyek tidak berarti sudah ada alur pendaftaran tersimpan, akun peserta, atau transaksi di kode saat ini.

### Admin

| Modul | Fitur saat ini |
| --- | --- |
| Login dan sesi | Login dengan `admin_users.username` dan `password_hash`, cek sesi, logout, catat `last_login_at` |
| Dashboard | Navigasi ke enam modul pengelolaan konten |
| Homepage | Edit hero, CTA, teks bagian, gambar hero, tampil/sembunyi bagian, urutan bagian |
| Skema/kegiatan | Tambah, edit, hapus, unggah gambar, ubah highlight, dan pratinjau detail |
| Struktur/anggota | Tambah, edit, hapus, unggah foto, urutan tampil, aktif/nonaktif |
| Instruktur & asesor | Tambah, edit, hapus, unggah foto, urutan tampil, aktif/nonaktif |
| Profil website | Edit profil, visi, misi, kontak, sambutan pimpinan, foto pimpinan, dan video profil |
| Mitra | Tambah, edit, hapus, unggah logo, URL situs, urutan tampil, aktif/nonaktif |

`admin_users.role` sudah memiliki nilai `super_admin`, `admin_konten`, `editor`, dan `viewer`, tetapi endpoint tulis saat ini hanya memeriksa status login melalui `require_admin()`. Tidak ada pembatasan hak akses per role yang aktif. **Media Library** dan **User Admin** baru ditampilkan sebagai modul “Dalam Persiapan” di dashboard; keduanya bukan fitur CRUD yang sudah tersedia.

## Database yang dipertahankan

| Tabel | Fungsi | Penyesuaian Laravel yang diperlukan |
| --- | --- | --- |
| `admin_users` | Akun admin, role, status, waktu login | Model autentikasi memakai tabel ini dan kolom password `password_hash`; jangan membuat/memakai tabel `users` bawaan |
| `kegiatan` | Konten skema/kegiatan, slug, gambar, highlight, status, urutan | Model `Kegiatan`; pertahankan slug, `image_url`, `is_active`, `is_highlight`, dan urutan |
| `anggota` | Nama, jabatan, foto, status, urutan anggota | Model `Anggota`; petakan `role_label` dan `photo_url` |
| `instruktur` | Nama, foto, status, urutan instruktur/asesor | Model `Instruktur`; tetap menggunakan tabel tunggal ini |
| `partners` | Nama mitra, logo, URL, status, urutan | Model `Partner`; petakan `logo_url` dan `website_url` |
| `site_settings` | Pasangan `setting_key`/`setting_value` untuk konten situs | Model/service pengaturan dengan primary key string `setting_key`, tanpa auto increment atau kolom `created_at` |

Kolom `id` di tabel konten dan admin adalah integer unsigned auto increment. Sebagian besar tabel memiliki `created_at` dan `updated_at`; `site_settings` hanya memiliki `updated_at`. Periksa konfigurasi timestamp Eloquent pada setiap model agar Laravel tidak mencoba menulis kolom yang tidak ada.

**Aturan database:** gunakan koneksi MySQL ke database lama melalui `.env`; jangan jalankan migration bawaan `users`, `password_reset_tokens`, `sessions`, `cache`, atau `jobs` ke database ini. Pada tahap awal gunakan penyimpanan sesi dan cache berbasis file serta queue sinkron jika diperlukan. Jangan jalankan `database/schema.sql`, seed, atau migration SQL lama pada database aktif. Semua perubahan data tetap melalui tabel/kolom yang sudah ada. Backup database dan direktori upload sebelum cutover; uji pemulihan backup di lingkungan terpisah.

## Pemetaan kode ke Laravel

| Kode sekarang | Tujuan di Laravel |
| --- | --- |
| `router.php`, `.htaccess`, halaman `*.html` | `routes/web.php`, controller halaman publik, Blade view dan layout bersama; web root diarahkan ke `public/` |
| `api/_bootstrap.php` | Konfigurasi DB Laravel, model, middleware autentikasi, helper/resource respons |
| `api/auth.php` | Controller login/logout dan guard admin berbasis `admin_users` |
| `api/kegiatan.php` | Controller publik dan admin untuk skema/kegiatan, request validasi, model `Kegiatan` |
| `api/anggota.php`, `api/instruktur.php`, `api/partners.php` | Controller per modul, request validasi, model masing-masing |
| `api/homepage.php`, `api/profile.php` | Controller pengaturan, service untuk membaca/menyimpan `site_settings` |
| `assets/js/*-renderer.js` dan HTML publik | Blade untuk render dari server atau JS yang dipertahankan sementara dengan respons API kompatibel |
| `assets/js/admin-*.js` dan HTML admin | Blade admin, layout/sidebar bersama, form dan validasi server; JS dipindahkan bertahap |
| `assets/uploads/*` | Berkas lama tetap tersedia dengan URL `/assets/uploads/...`; unggahan baru mengikuti path yang sama selama transisi |

Usulan struktur awal: `app/Models`, `app/Http/Controllers/Public`, `app/Http/Controllers/Admin`, `app/Http/Requests`, `app/Services`, `resources/views/layouts`, `resources/views/public`, `resources/views/admin`, dan `public/assets`. Pecah pengelolaan `site_settings` menurut kelompok `home_*`, `about_*`, `contact_*`, `vision`, dan `mission`, tetapi tetap simpan ke tabel key/value yang sama.

## Kontrak yang harus tetap bekerja

1. Pertahankan URL publik saat ini (`/`, `/aboutus`, `/anggota`, `/instruktur`, `/asesor`, `/kegiatan`, `/kegiatan-detail?id=...`, `/gallery`, `/kontak`) dan URL `.html` lama melalui redirect yang sesuai. Pastikan tautan lama serta mesin pencari tetap mencapai konten yang sama.
2. Selama frontend lama masih dipakai, pertahankan endpoint `/api/*.php`, metode HTTP, parameter seperti `action`, `all=1`, `id`, dan bentuk JSON (`ok`, `items`/`item`, nama field camelCase seperti `isActive`, `sortOrder`). Setelah semua pemanggil pindah, endpoint kompatibilitas boleh dihentikan terencana.
3. Pertahankan referensi gambar/video yang sudah tersimpan sebagai URL `/assets/...`; salin direktori aset dan upload secara utuh, termasuk foto instruktur yang saat ini dapat dibuat di `assets/uploads/instruktur/`.
4. Login menggunakan hash lama dari `password_hash` (PHP `password_hash`/`password_verify` kompatibel dengan pengaturan autentikasi yang tepat). Migrasi sesi memerlukan login ulang admin setelah cutover. Wajib verifikasi `is_active = 1`.
5. Pertahankan perilaku konten aktif dan urutan: `is_active`, `sort_order DESC`, serta highlight. Jangan mengubah nilai `site_settings` saat memindahkan pembacaan ke Laravel.
6. Validasi upload minimal setara dengan saat ini: gambar umumnya maksimal 4 MB, video profil MP4 maksimal 50 MB. Periksa MIME, simpan nama acak, dan hanya hapus file yang berada di direktori upload milik aplikasi.

Laravel menambahkan perlindungan CSRF pada form web. Ketika endpoint lama tetap dilayani, perbarui JavaScript admin agar mengirim token CSRF atau rancang endpoint kompatibilitas dengan autentikasi dan proteksi yang setara. Jangan membiarkan operasi tulis terbuka hanya untuk mempertahankan perilaku frontend lama.

## Urutan pelaksanaan

1. **Inventaris dan baseline.** Ambil backup database dan upload. Catat jumlah baris tiap tabel, contoh record, daftar URL aktif, serta hasil respons endpoint publik/admin. Cocokkan skema server dengan `database/schema.sql` dan migration `20260928_create_instructors.sql`.
2. **Siapkan Laravel.** Buat proyek Laravel di lingkungan/staging terpisah, konfigurasi `.env` untuk database yang sama pada salinan staging, web root `public/`, sesi/cache file, dan model sesuai tabel lama. Jangan jalankan migration Laravel bawaan.
3. **Pindahkan halaman publik.** Buat layout Blade, pindahkan CSS/JS/aset, lalu implementasikan beranda, profil, struktur, instruktur, skema/detail, galeri, dan kontak. Pastikan URL lama tetap hidup.
4. **Pindahkan autentikasi dan admin.** Gunakan `admin_users` sebagai sumber akun, tambah middleware admin, lalu pindahkan keenam modul CRUD/pengaturan. Cocokkan respons API selama JS lama masih digunakan.
5. **Uji kesetaraan.** Bandingkan hasil halaman dan endpoint terhadap baseline: login/logout, baca publik, tambah/edit/hapus, tampil/sembunyi, highlight, pengurutan, upload/ganti/hapus file, dan form WhatsApp. Verifikasi tidak ada tabel atau kolom baru di database staging.
6. **Cutover.** Bekukan perubahan konten sejenak, backup ulang database/upload, salin upload terbaru, ubah document root ke `public/`, aktifkan Laravel, uji URL utama serta login admin. Siapkan rollback ke kode lama dan upload/backup yang sesuai jika pemeriksaan gagal.

## Di luar cakupan tahap migrasi

Pendaftaran peserta yang tersimpan, akun peserta, pembayaran, Media Library, dan CRUD User Admin belum tersedia sebagai fitur aktif. Jika kelak ditambahkan, rancang terpisah karena sebagian membutuhkan tabel/kolom baru dan bertentangan dengan batasan **database tidak diubah** pada migrasi ini. Pembatasan akses per role dapat direncanakan memakai kolom `role` yang sudah ada, tetapi perlu keputusan hak akses yang jelas sebelum diaktifkan.

## Kriteria selesai

- Seluruh halaman publik dan enam modul admin yang aktif bekerja di Laravel dengan data lama.
- URL serta media lama tetap dapat dibuka; tidak ada data atau berkas upload yang hilang.
- Skema database tetap sama: tidak ada tabel/kolom yang ditambah, dihapus, atau diubah.
- Login admin lama berhasil, operasi tulis terlindungi autentikasi dan CSRF, serta konten publik menghormati status aktif.
- Backup, langkah cutover, dan rollback telah diuji di staging.

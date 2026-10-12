# Update produksi: kategori skema dan pembayaran Midtrans

Panduan ini untuk menerapkan commit `6c20901` (atau commit yang lebih baru di `main`) pada server Laravel yang sudah berjalan. Jalankan perintah shell di server, dari direktori aplikasi `/var/www/lsp-laravel`. Jika lokasi instalasi berbeda, sesuaikan path pada perintah `cd` dan konfigurasi `APP_DIR` untuk `deploy.sh`.

## Sebelum deploy

1. Jadwalkan waktu update dan pastikan akses admin serta akses ke dashboard Midtrans tersedia.
2. Pastikan `.env` produksi tetap berada di server dan **jangan mengganti `APP_KEY`**. Server Key dan Client Key Midtrans disimpan terenkripsi dengan key aplikasi tersebut; menggantinya membuat nilai yang sudah tersimpan tidak dapat dibaca.
3. Pastikan `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` memakai alamat HTTPS publik yang benar, dan konfigurasi email produksi sudah siap. Email konfirmasi pembayaran dikirim melalui `MAIL_MAILER`; nilai `log` hanya menulis email ke log.
4. Pastikan working tree kode bersih. Skrip deploy menolak perubahan lokal pada file yang dilacak.

```bash
cd /var/www/lsp-laravel
git status --short
./deploy.sh doctor
```

Jika `doctor` gagal, perbaiki pemeriksaan yang dilaporkan sebelum melanjutkan. Skrip deploy membuat backup database dan upload sebelum menarik kode.

## Deploy kode dan database

```bash
cd /var/www/lsp-laravel
./deploy.sh deploy
php artisan migrate --path=database/migrations/2026_10_12_095736_create_scheme_payment_settings_and_payment_transactions_tables.php --force --no-interaction
git rev-parse --short HEAD
```

Perintah migrasi terakhir sengaja diulang: Laravel akan melewati migrasi yang sudah tercatat. Ini memastikan dua tabel baru, `scheme_payment_settings` dan `payment_transactions`, dibuat meskipun proses deploy dimulai dengan versi `deploy.sh` lama yang belum menjalankan migrasi tersebut. **Jangan jalankan `php artisan migrate` tanpa `--path`** pada database lama; aplikasi ini memakai migrasi terpilih untuk tabel pendamping.

`deploy.sh deploy` melakukan backup database dan upload, menarik `origin/main`, memasang dependensi Composer, memperbarui cache Laravel, mengatur izin, dan memeriksa halaman utama serta API kegiatan. Pembaruan ini hanya menambah view dan kode PHP, sehingga tidak memerlukan build frontend baru.

## Atur Midtrans setelah deploy

1. Login sebagai admin dan buka menu **Midtrans**. Pilih **Sandbox** untuk uji awal, lalu isi Merchant ID, Server Key, dan Client Key dari dashboard Midtrans yang sesuai dengan environment tersebut. Isi key melalui form admin, bukan di Git atau dokumen ini. Server Key dan Client Key disimpan terenkripsi di database; Merchant ID disimpan sebagai nilai biasa.
2. Salin **Payment Notification URL** yang ditampilkan di halaman pengaturan admin ke dashboard Midtrans. Pastikan alamat itu memakai HTTPS publik dan dapat diakses Midtrans. Endpoint menerima `POST /midtrans/notification` dan memverifikasi tanda tangan menggunakan Server Key.
3. Periksa kembali `APP_URL` dan konfigurasi HTTPS sebelum mencoba pembayaran. URL kembali setelah pembayaran dibuat aplikasi untuk setiap transaksi.
4. Aktifkan Midtrans hanya setelah konfigurasi benar. Pada modul skema, isi harga dan aktifkan pembayaran untuk skema yang akan dijual. Skema tanpa pembayaran aktif tidak menampilkan alur checkout.
5. Lakukan satu transaksi uji Sandbox dari halaman skema: pastikan pembeli diarahkan ke Midtrans, notifikasi mengubah status transaksi, transaksi muncul di menu **Pembayaran**, dan email konfirmasi terkirim setelah pembayaran berhasil. Baru setelah uji selesai, ganti ke **Production** dengan key Production yang sesuai dan ulangi pemeriksaan menggunakan transaksi yang disepakati tim.

## Jika ada masalah

- Jika `deploy.sh deploy` gagal pada health check, skrip mencoba rollback kode otomatis. Periksa hasil akhirnya dan log aplikasi di `storage/logs`.
- Jika perlu rollback manual, jalankan `./deploy.sh rollback` dari direktori aplikasi. Perintah ini mengembalikan kode ke commit sebelum deploy terakhir, **tidak** mengembalikan database atau upload.
- Jangan menjalankan `migrate:rollback` untuk migrasi pembayaran jika sudah ada transaksi: migrasi balik menghapus kedua tabel pembayaran. Simpan backup dari `deploy.sh`, matikan pembayaran sementara, dan tentukan pemulihan data secara terpisah.
- Jika webhook atau email gagal, periksa pengaturan Midtrans, alamat publik, `APP_URL`, konfigurasi mail, dan log aplikasi. Jangan menyalin Server Key, Client Key, atau isi `.env` ke tiket atau log percakapan.

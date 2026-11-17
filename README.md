# Teras Ngulik

Platform belajar interaktif lokal, dibuat dari spesifikasi TERAS_NGULIK_FULL_SYSTEM_CODEX. Laravel 13, PHP 8.3+, React 19, TypeScript, Inertia 3, Vite 7. Seluruh katalog awal berisi **contoh materi lokal**, bukan katalog komersial terverifikasi.

## Jalankan di komputer ini

Klik dua kali `MULAI.cmd`, lalu buka `http://127.0.0.1:8000`. Alternatif: jalankan `powershell -ExecutionPolicy Bypass -File .\start-local.ps1`. Proses ini menggunakan PHP 8.3 Laragon dan aset frontend hasil build; tidak memerlukan server Node.

Database lokal menggunakan SQLite agar proyek bisa langsung dicoba tanpa menyalakan MySQL. Migrasi juga mendukung MySQL/MariaDB. Gunakan direktori `public/` sebagai document root jika mengaktifkan virtual host Apache/Nginx Laragon; gunakan PHP >=8.3. Jangan jadikan root repository sebagai document root karena berisi `.env`, database, dan berkas privat.

## Akun demo lokal

| Peran | Email | Kata sandi |
|---|---|---|
| Admin | admin@teras.test | NgulikDemo!2026 |
| Pelajar | belajar@teras.test | NgulikDemo!2026 |

Akun demo hanya untuk lingkungan lokal. Registrasi baru tetap memerlukan verifikasi email. Secara lokal email ditulis ke `storage/logs/laravel.log` karena `MAIL_MAILER=log`; tidak ada email sungguhan yang dikirim. Jangan gunakan kredensial demo untuk produksi.

## Pengembangan

1. Instal PHP >=8.3 dengan ekstensi pdo_mysql/pdo_sqlite, mbstring, openssl, fileinfo, xml, curl, intl, zip; Composer; Node >=20.19.
2. `composer install`, salin `.env.example` menjadi `.env`, `php artisan key:generate`.
3. Buat `database/database.sqlite` atau konfigurasikan koneksi MySQL ke database baru yang khusus aplikasi ini.
4. `php artisan migrate`, dan hanya pada lokal: `php artisan db:seed`.
5. `npm ci --ignore-scripts` lalu `npm run build`. Untuk mengubah UI: `npm run dev`.
6. `php artisan test`. Pada lingkungan Codex Windows, PHPUnit memerlukan proses di luar token sandbox karena pemeriksaan `is_readable` Windows ACL.

## Konfigurasi layanan

- Midtrans: isi `MIDTRANS_SERVER_KEY` dan `MIDTRANS_CLIENT_KEY` sandbox. Webhook HTTPS `POST /webhooks/midtrans`. Snap dipasang dengan mode embedded; status frontend tidak pernah menerbitkan akses. Backend memverifikasi tanda tangan, nominal dan status API Midtrans. Belum ada transaksi provider yang diuji tanpa kredensial.
- Google: isi `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` dan daftarkan callback `/auth/google/callback` pada console provider.
- Email: atur `MAIL_MAILER=smtp`, SMTP host, port, username, password dan alamat pengirim. Uji verifikasi, pemulihan akun dan OTP sebelum rilis.
- Telegram/WhatsApp/LinkedIn API: tidak tersambung. Sertifikat dapat dibagikan melalui tautan resmi Add to Profile; ini bukan penerbitan API otomatis.
- Runtime Python, SQL, R di playground memuat pustaka browser melalui CDN saat dipilih dan membutuhkan jaringan/perangkat kompatibel. HTML/CSS/JS berjalan lokal dalam iframe sandbox. Tidak ada eksekusi kode pengguna di PHP.

## Hosting PHP + MySQL

Gunakan `APP_ENV=production`, `APP_DEBUG=false`, `APP_DEMO=false`, HTTPS, dan app key unik. Konfigurasikan MySQL `utf8mb4`, jalankan `php artisan migrate --force`, lalu `php artisan optimize`. Deploy `vendor/`, `public/build/`, dan kode; Node hanya diperlukan saat membangun aset. Folder storage dan bootstrap/cache harus writable. Simpan dokumen privat di luar `public/`. Jangan jalankan seed demo di produksi.

Cron: `* * * * * cd /path/aplikasi && php artisan schedule:run >> /dev/null 2>&1`. Antrian: `php artisan queue:work --stop-when-empty --max-time=50`. Perintah `teras:expire-orders` mengembalikan reservasi stok/coupon pesanan kedaluwarsa secara idempoten.

Backup database dan storage terenkripsi di luar webroot; simpan app key secara terpisah karena laporan whistleblowing dienkripsi. Untuk SQLite gunakan koneksi backup SQLite atau hentikan proses tulis sebelum menyalin. Untuk MySQL gunakan mysqldump transaction-consistent. Uji pemulihan ke database terpisah sebelum migrasi produksi. Migrasi rollback bukan mekanisme memulihkan transaksi pembayaran.

Baca `IMPLEMENTATION_STATUS.md` untuk kemampuan yang sudah diuji dan cakupan spesifikasi yang belum lengkap.

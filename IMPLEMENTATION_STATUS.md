# Status implementasi Teras Ngulik

Diperbarui 2 November 2026 (Asia/Jakarta). Target lokal: `D:\laragon\www\Teras Ngulik`. ZIP sumber berisi spesifikasi, bukan aplikasi; salinan acuannya dipertahankan di `docs/specification/`.

**Aplikasi lokal sudah berfungsi dan memiliki backend nyata. Seluruh visi produk dalam ZIP belum selesai.** Tabel ini membedakan alur yang dapat dipakai, integrasi yang belum dikonfigurasi, dan pengembangan lanjutan. Tidak ada transaksi Midtrans sukses, pengiriman newsletter massal, atau koneksi OAuth produksi yang direkayasa.

| Domain | Status | Kemampuan aktual dan batas |
|---|---|---|
| Fondasi lokal | DONE | Laravel13/PHP8.3, React19/TypeScript, Inertia3, aset Vite, SQLite lokal, migrasi MySQL-compatible secara struktur; helper MULAI.cmd dan start-local.ps1. MySQL belum diuji dengan server database nyata. |
| Brand dan navigasi | DONE | Logo sumber, palet, mega menu, mobile drawer, dark mode, walkthrough, skeleton, SEO dasar, search. 67 halaman diverifikasi melalui HTTP. Tampilan browser belum diperiksa karena runtime alat browser gagal. |
| Aksesibilitas | IN_PROGRESS | Skip link, fokus, label, pembesaran teks, kontras tinggi, reduced motion, dialog focus trap. Belum audit WCAG independen/screen reader menyeluruh. |
| Bahasa | IN_PROGRESS | Indonesia utama; navigasi dan sebagian beranda Inggris. Konten, dashboard, admin dan pesan backend belum diterjemahkan lengkap. |
| Identitas dan profil | IN_PROGRESS | Registrasi, login, verifikasi email, reset, password OTP expiring/sekali pakai, email self-service tidak dapat diubah, avatar SVG, profil/portofolio privat/publik, ekspor data. Riwayat alias username, penutupan akun dan guardian consent belum lengkap. |
| Email dan Google OAuth | NEEDS_CONFIGURATION | Socialite Google + state, transport Laravel mail, tautan signed dan OTP. Lokal menggunakan log, bukan SMTP sungguhan. Perlu client ID/secret dan SMTP yang diuji. |
| LMS | IN_PROGRESS | Lima akademi, course/materi campuran, CMS lesson, entitlement, enrollment, resume/progres, evaluasi server, tugas + file privat, review manual, sertifikat HTML + ekspor PNG dan tautan LinkedIn. Modul memakai module_title; versioning/editing enterprise, transkrip dan automasi penerjemahan belum lengkap. |
| Quiz/diagnostic | IN_PROGRESS | Kuis course dan mandiri terpisah, opsi variabel, soal+jawaban dirahasiakan sampai alur yang tepat, timer server/attempt limit opsional, hasil dan penjelasan, diagnostic sederhana dan radar dari evidence quiz. Belum assessment adaptif canggih/anti-cheat high stakes. |
| Learning path | IN_PROGRESS | Enrollment path, urutan course tersimpan, launch memeriksa prasyarat course sebelumnya, admin dapat mengatur urutan. Belum sertifikat path/kurikulum berversi. |
| Notebook/review | DONE | Catatan/bookmark pribadi, ekspor Markdown, kartu recall, rating dan jadwal review historis. Algoritma interval sederhana, bukan FSRS penuh; kartu dari salah kuis belum otomatis. |
| Scrim | IN_PROGRESS | Rekaman perubahan file bertimestamp + checkpoint, audio opsional, play/pause/seek/speed, edit/copy/run saat pause, resume timeline asli, save fork, revisi terbit immutable. Editor textarea; belum Monaco, unggah chunk resumable, waveform/cursor replay atau studio multi-scene. |
| Playground/proyek | IN_PROGRESS | Run HTML/CSS/JS gratis; opaque iframe, JS Worker, stop/reset/copy/download, proyek akun privat/publik, quota Free/Pro, snapshot/version, request review. Python/SQL/R memakai CDN/WASM pinned, belum diuji menjalankan runtime lewat browser. HTML loop tanpa akhir masih dapat membebani tab. |
| Lab/challenge/game | IN_PROGRESS | Latihan browser rendah risiko, kuis coding dan logika, simulasi cybersecurity defensif, game puzzle sederhana dan review instruktur. Bukan judge kode tersembunyi tepercaya; game lanjutan/penilaian kode server tidak ada. |
| Runtime server lanjut | INFRA_BLOCKED | Tidak menjalankan kode arbitrer, exploit, VM/container atau Stata executable di shared hosting. Infrastruktur judge terisolasi memerlukan provisioning terpisah. |
| Konten/media | IN_PROGRESS | CMS artikel multi-kategori/penulis/filter; ebook, podcast, shorts, galeri, help/legal, jadwal terbit, tier akses, media eksternal dan lampiran privat. Materi seed adalah contoh; player tidak ditampilkan jika belum ada media. Rich text/revisi/transcoding/caption otomatis belum ada. |
| Komunitas | IN_PROGRESS | Post/showcase dewasa terverifikasi, komentar beranak, parent harus satu topik, report/moderasi, notifikasi balasan dan profil publik. Anak privat dan tidak boleh posting publik. Poll/follow/mentions dan role moderator granular belum lengkap. |
| Program/classroom | IN_PROGRESS | Event, bootcamp, mentoring, kompetisi, karier/partner via CMS; registrasi idempoten, quota/slot transaction, kelas terdaftar, materi classroom privat, submission dan feedback admin. Multi-cohort, attendance, kalender, tim/appeal/job matching belum lengkap. |
| Commerce | IN_PROGRESS | Produk kelas/bundle/langganan/merch/preorder, voucher, stok, snapshot harga, satu pembayar N akun terverifikasi, order/cancel/expiry, alamat terenkripsi, resi, refund request/review dan revoke dari refund provider. Checkout ditandai belum tersambung tanpa key; partial refund, kalkulasi ongkir/pajak dan refund inisiasi API belum lengkap. |
| Midtrans | NEEDS_CONFIGURATION | Snap embedded adapter, signature SHA512, GET status API, nominal terverifikasi, atomik+idempoten grants, refund penuh. Telah diuji dengan HTTP fake, belum sandbox provider nyata. Biaya dan operasional provider mengikuti akun resmi. |
| Langganan | IN_PROGRESS | Grant berjangka dan perpanjangan invoice manual memperpanjang akhir aktif. Quota Pro memakai entitlement aktif. Tidak ada auto-debit recurring. |
| XP/Bara/reward | IN_PROGRESS | Ledger terpisah, XP tidak dapat ditukar, kelulusan idempoten memberi100XP/25Bara, debit Bara+stok atomik, grant kelas/pemenuhan merch, leaderboard hanya profil publik dewasa. Season, weekly challenge/check-in, kebijakan hadiah CMS dan antifraud lanjutan belum lengkap. |
| Newsletter | NEEDS_CONFIGURATION | Consent/double opt-in, unsubscribe, draft/approval/queue, batch bounded, suppress opt-out dan retry manual. Real send default nonaktif. Bounce/complaint provider dan advanced segmentation belum ada. Tidak ada newsletter sungguhan dikirim selama pengerjaan. |
| Support/whistleblowing | IN_PROGRESS | Tiket/conversation privat terenkripsi, laporan whistle anonim opsional, jalur admin terpisah dan audit. Case-specific role, anonymous reply tracking dan SLA automation belum lengkap. |
| Admin/audit/docs | IN_PROGRESS | CRUD course/lesson/konten/program/produk/voucher, stok/pengiriman, pengguna/peran, moderasi, newsletter, review, laporan aktual dan audit. Peran yang dikelola UI admin/learner; matriks izin organisasi/instructor/partner granular belum lengkap. API aktual /api/v1/content dan docs visual tersedia. |
| Integrasi komunitas | NOT_STARTED | Telegram, WhatsApp Business dan LinkedIn API belum diimplementasikan/terhubung. LinkedIn hanya tautan Add to Profile resmi; bukan auto-publish API. |

## Bukti validasi

- `php artisan test`: **52 passed / 407 assertions**, setelah perbaikan dan pemformatan kode (hasil akhir dicatat juga dalam panduan serah terima bila angka berubah).
- `node --test tests/Unit/scrim-engine.test.mjs`: **4 passed**, mencakup fork immutable, seek unicode/multifile, checkpoint dan urutan timestamp sama.
- `npm run build`: TypeScript + Vite **PASS**, aset produksi dan lazy chunks Learning/Content tersimpan di public/build.
- Laravel Pint: kode PHP diformat. Tidak ada layanan produksi yang dihubungi tes; mail/files/HTTP provider memakai doubles.
- HTTP lokal dengan cookie+CSRF login akun demo: **67/67** halaman publik, learner dan admin memberi200. Satu respons transient500 saat manifest sedang ditulis ulang build diverifikasi kembali menjadi200 sesudah build selesai.
- Unit backup SQLite menguji API backup dan pemulihan baris ke database terpisah di memori; **belum** bukti restore deployment lengkap beserta file/media/MySQL.
- Browser visual/E2E: **belum terverifikasi**. Kedua runtime browser/Node UI gagal `failed to write kernel assets ... path specified`. Tidak mengklaim screenshot/browser pass.
- MySQL concurrency, SMTP, Google callback nyata, Midtrans sandbox, runtime WASM dan perangkat mobile nyata belum diuji.

## Lanjutan berdasarkan dependensi

1. Tinjau hasil lokal dan lakukan browser E2E pada lingkungan dengan alat browser aktif, termasuk mobile, Scrim/audio, file download dan sandbox runtimes.
2. Uji migrasi+transaksi concurrency pada MySQL terpisah; uji backup/restore DB+storage+app key.
3. Konfigurasikan SMTP, Google dan Midtrans sandbox; jalankan acceptance provider sebelum produksi. Jangan gunakan akun demo di produksi.
4. Lengkapi peran granular, guardian/privacy workflows, konten Inggris, editorial/media, cohorts/program lanjut, kebijakan reward, billing/refund/ongkir sebelum mengklaim seluruh cakupan produk selesai.

Dokumentasi domain aktual: `docs/learning-runtime.md`, `docs/CONTENT_AND_NEWSLETTER.md`, `README.md`, dan `DECISIONS.md`.

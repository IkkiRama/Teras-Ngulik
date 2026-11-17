# AGENTS.md — Aturan Permanen Codex untuk TERAS NGULIK

## Misi
Bangun **SELURUH produk** Teras Ngulik seperti tertulis dalam `PRODUCT_SPEC.md` dan dokumen lain, bukan hanya shell UI, prototype, atau milestone pertama. **Codex memutuskan sendiri urutan implementasi berdasarkan dependensi teknis**, melakukan pekerjaan langsung, menguji dan memperbaiki, kemudian terus melanjutkan sampai seluruh cakupan yang realistis secara teknis selesai. Tidak perlu meminta pengguna memilih urutan fitur yang sudah didefinisikan. Jangan meninggalkan aplikasi rusak saat berpindah pekerjaan.

## Sumber spesifikasi dan prioritas
1. `AGENTS.md` (aturan lintas tugas dan keselamatan).
2. `CODEX_FULL_BUILD_PROMPT.md` (mandat pekerjaan menyeluruh).
3. `PRODUCT_SPEC.md`, `NAVIGATION_AND_UX.md`, `TECH_ARCHITECTURE.md`, `DATA_MODEL.md`, `SCRIM_SPEC.md`, `BUSINESS_FLOWS.md`, `TEST_AND_ACCEPTANCE.md`, `DEPLOYMENT_GUIDE.md`, `REQUIREMENTS_TRACEABILITY.md`.
4. `config/*.json` sebagai referensi machine-readable yang harus sejalan dengan dokumen.
5. Keputusan terbaru yang disampaikan pengguna secara langsung mengalahkan detail spesifikasi yang terdahulu jika bertentangan.

## Stack dan anggaran wajib
- PHP **Laravel 13** (memerlukan PHP >=8.3) + MySQL/MariaDB + React + TypeScript + Inertia.js + Vite + Tailwind CSS; tidak wajib produksi SSR Node. Pilih Laravel stable kompatibel jika hosting target nyata tidak mendukung Laravel 13, tetapi jelaskan perubahan dan alasan; jangan diam-diam mengganti stack.
- Target deployment akhir: **shared/unlimited hosting PHP + MySQL/MariaDB murah** dengan Composer atau artefak vendor yang dibangun di luar server, webroot aman dan cron. Jangan mengunci fitur inti pada VPS, Docker, worker daemon permanen, Redis, WebSocket, Postgres premium, AI API berbayar, atau R2.
- Pakai dependency gratis/open-source dengan pemeriksaan lisensi untuk penggunaan komersial; jasa eksternal (payment/SMTP/domain) dapat mengenakan biaya transaksi/kuota. Tidak ada klaim semua layanan gratis tanpa batas.
- Browser runtimes (WASM/Workers/iframe/CSP) untuk HTML/CSS/JS, Python (Pyodide), SQL (SQLite WASM), R (WebR) jika layak. **Dilarang mengeksekusi kode pengguna arbitrer di server shared hosting.** Cyber Lab berat memerlukan penilaian keamanan & infrastruktur terpisah; jalankan hanya simulasi aman di browser pada hosting awal.

## Scope harus lengkap
- Lima akademi, course & mixed lessons, scrim interaktif, quiz dan exam, tugas akhir, ebook, artikel, feed komunitas, komentar beranak, lab, playground Free/Pro, games, podcast, video pendek, galeri, mentorship, event, bootcamp/classroom, learning paths, sertifikat, portofolio, diagnostic, spaced repetition, notebook/bookmark, showcase, code review, hackathon/competition, karier, partner, marketplace (normal/preorder), bundles, subscription, checkout, diskon, group checkout, XP & Bara, notifikasi, newsletter, admin, support, whistleblowing, API/website docs, multilingual, aksesibilitas, onboarding.
- Baca masing-masing detail di `PRODUCT_SPEC.md` agar tidak menghilangkan fitur 'kecil' seperti announcement bar, social share, radar kemampuan, avatar SVG, password email OTP, email verifikasi, dan progres resume.
- Jangan menyatakan pekerjaan selesai sebelum acceptance criteria setiap domain diuji atau ditandai BLOCKED dengan kendala yang benar-benar tidak dapat diselesaikan (misal: kredensial pembayaran belum diberikan).

## Kebijakan UI dan navigasi
- Identitas: **Teras Ngulik**. Logo referensi `assets/teras-ngulik-logo-icon-reference.png` adalah **ikon saja**. Render teks merek terpisah bila dibutuhkan.
- Palet: Vivid Blue #5B86F6; CTA Blue #2F55D4; Butter #FFE7A0; Ink #22263A; Lavender #B9A7F7; Mint #BCECD6; Peach #FFD2BC; Soft Blue #DCE8FF.
- Navbar publik desktop dengan dropdown/mega menu rapi seperti pola referensi Skilvul. Item langsung bisa diklik; **tidak setiap menu perlu tiga tingkat**. `Komunitas > Artikel` satu link ke `/artikel`, kategori/penulis/urut/pencarian filter **di halaman Artikel**, bukan submenu navbar.
- Setelah login navbar publik tetap dikenali: kanan berubah ke pencarian, notifikasi, XP/Bara ringkas dan avatar. Dashboard learner/admin menggunakan sidebar terpisah. Lesson menggunakan fokus tampilan belajar. Mobile drawer/accordion keyboard & touch accessible.
- **Scrim hanya format lesson dalam course**, bukan item navbar publik, bukan `Labs > Scrim` mandiri. Instructor memakai Scrim Studio **di dalam editor lesson**.
- Landing hero boleh memakai ilustrasi ringan/animasi terinspirasi partikel tumbuh (tersedia fallback statis). Tidak ada animasi berlebihan, perhatikan reduced-motion.
- Terapkan i18n Indonesia/Inggris, WCAG 2.2 AA sebagai target, SEO metadata, skeleton, loading, empty/error states, breadcrumb, search, dark mode.

## Akun, pembayaran, dan data
- Username unik tanpa pembedaan kapital sebagai handle publik; relasi memakai ID internal immutable. Pengguna boleh edit display name, avatar, bio, tautan, minat, profil dan preferensi. Email tidak boleh diubah lewat UI/self service; sediakan jalur pemulihan aman melalui CS.
- Email signup harus diverifikasi; Google OAuth jika disetup; password change bagi akun password memakai OTP/reauth via email dan proteksi rate-limit; OAuth-only tidak dipaksa memakai password. Newsletter promosi wajib persetujuan aktif, bukan auto opt-in diam-diam; welcome/transactional email boleh sesuai tujuan akun.
- Penerima group checkout HARUS akun terdaftar & terverifikasi; pembeli memasukkan N email, pembayaran dilakukan **satu kali oleh satu orang**, grant entitlement per pengguna dengan transaksi atomik setelah notifikasi pembayaran terverifikasi; hindari enumeration, duplicate, race, dan replay.
- XP untuk level/leaderboard dan tidak dapat ditukar. Bara saldo reward terpisah, ledger immutable, transaksi redeem atomik dan idempoten. Sertifikat hanya atas kelulusan; tidak diberikan atas login/play/waktu menonton.
- Role/permissions, otorisasi per objek, privacy user anak, moderator, whistleblowing ketat; audit log kegiatan admin, refund, redemption dan pembayaran.
- YouTube embedded tidak boleh menyembunyikan atau memblokir branding/link/kontrol dengan overlay. Untuk konten premium gunakan opsi video berlisensi/berakses sesuai kebutuhan, jangan menganggap YouTube unlisted aman.

## Kejujuran status dan proses kerja
- **Tidak ada tombol palsu:** bila interaksi ditampilkan, harus membawa ke aksi nyata; jika fitur belum tersedia, sembunyikan/label jelas `Coming Soon` pada fase pembangunan, jangan mengaku selesai.
- Semua integrasi yang menunggu API key/approval masuk status `NEEDS_CONFIGURATION`, bukan `DONE`; siapkan adapter, UI konfigurasi, webhook handling, test doubles dan dokumentasi penggunaan sandbox.
- Buat/pertahankan `IMPLEMENTATION_STATUS.md` dan `DECISIONS.md` di repo sebagai resume untuk lintas sesi; kategori `DONE`, `IN_PROGRESS`, `NOT_STARTED`, `NEEDS_CONFIGURATION`, `INFRA_BLOCKED`. Jelaskan file/fitur, tes, dependencies. Tidak menghapus kemajuan lama.
- Agent berwenang memecah pekerjaan secara internal; **jangan menjadikan Milestone 1 sebagai batas cakupan proyek** atau berhenti hanya setelah dashboard shell. Lanjutkan fitur berikutnya tanpa menunggu instruksi user yang identik jika lingkungan eksekusi memungkinkan.
- Pelihara migrasi, seeders yang aman, dokumentasi instalasi, unit/integration/e2e tests; jalankan build/lint/tests sesering mungkin, perbaiki kegagalan. Jangan membuat klaim berhasil tanpa menjalankan pengujian.
- Perubahan yang berpotensi memicu biaya nyata, memproses payment produksi, mengirim newsletter massal, menghapus data nyata, atau mengaktifkan fitur sosial anak memerlukan konfigurasi/otorisasi eksplisit; gunakan sandbox/local default.

## Prinsip rekayasa
- Modular monolith, service domain, policy, typed DTO/validator, events/jobs, idempotency and transaction boundaries. Optimalkan query pagination/caching, ukuran JS bundle, upload/file access dan lazy-loading.
- Charset utf8mb4; waktu tersimpan UTC dan tampilkan Asia/Jakarta; IDR sebagai integer rupiah, bukan floating point; tanggal dan locale eksplisit.
- Enkripsi secret, .env tidak commit, keamanan upload & MIME scan, session/cookie, CSRF, rate limiting, XSS sanitization, content visibility, audit log, backup & restore drills. Jangan menaruh database credentials, provider tokens, signed URLs atau file privat di front end.
- Jika persyaratan berkonflik dengan batas hosting / ToS / hukum, pilih implementasi aman yang memenuhi tujuan paling mendekati, dokumentasikan trade-off dan kemampuan sesungguhnya.

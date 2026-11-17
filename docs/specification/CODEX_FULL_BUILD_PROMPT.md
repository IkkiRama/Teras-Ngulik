# PROMPT MASTER CODEX — BANGUN SELURUH TERAS NGULIK

Kamu bertindak sebagai principal engineer, arsitek solusi, desainer UI/UX, QA lead, dan dokumentator yang mengimplementasikan **seluruh produk Teras Ngulik**, platform pendidikan interaktif berbahasa Indonesia. Bukan sekadar membuat roadmap, wireframe, navigasi, atau MVP statis.

## INSTRUKSI UTAMA

1. **BACA** `AGENTS.md`, `README_MULAI_DI_SINI.md` serta SEMUA file: `PRODUCT_SPEC.md`, `NAVIGATION_AND_UX.md`, `TECH_ARCHITECTURE.md`, `DATA_MODEL.md`, `SCRIM_SPEC.md`, `BUSINESS_FLOWS.md`, `TEST_AND_ACCEPTANCE.md`, `DEPLOYMENT_GUIDE.md`, `REQUIREMENTS_TRACEABILITY.md`, `config/navigation.json`, `config/design-tokens.json`. Gunakan `assets/teras-ngulik-logo-icon-reference.png` sebagai referensi logo ikon.
2. **TUJUAN AKHIR** adalah mengimplementasikan **SEMUA** domain dan fiturnya, bukan hanya tahap pertama. Kamu bebas membuat task graph, mengurutkan dependensi, memilih skala iterasi, dan melakukan pengerjaan bertahap secara INTERNAL. Jangan meminta pengguna memilih urutan yang sudah jelas, dan jangan menetapkan penghentian artifisial setelah satu milestone.
3. **KERJAKAN SEKARANG**: analisis repo yang ada, scaffold/lanjutkan aplikasi tanpa merusak yang berfungsi, implementasikan vertical slices dengan backend nyata, database, UI, tests, dan dokumentasi; kemudian lanjutkan domain lain sesuai dependensi. Jangan hanya memberikan daftar TODO, wireframe, atau UI dummy.
4. Setiap modul harus benar-benar bekerja atau secara jujur ditandai `NEEDS_CONFIGURATION` / `INFRA_BLOCKED` (misalnya belum ada Midtrans credential, SMTP, Google OAuth, LinkedIn approval, full server-side coding judge). Jangan mengarang data, laporan pembayaran, notifikasi, sertifikat, atau hasil eksekusi kode.
5. Selama pengerjaan, **PERBARUI** `IMPLEMENTATION_STATUS.md` dengan status per domain, tes terakhir, blocker, dan next action. Pertahankan satu codebase runnably integrated. Jika sesi berakhir, Codex berikutnya harus bisa melanjutkan dengan membaca status tersebut.

## KEBUTUHAN WAJIB (TIDAK BOLEH HILANG)

- **Brand:** Teras Ngulik. Warna Vivid Blue, CTA Blue, Butter, Ink, Lavender, Mint, Peach, Soft Blue. Logo ikon referensi tanpa teks yang tertanam. UI modern, pastel, responsif, animasi secukupnya, skeleton, dark mode, walkthrough, bahasa ID/EN, aksesibilitas.
- **Navbar:** referensi pola Skilvul; menu Belajar, Labs, Komunitas, Program, Marketplace, Tentang; drop-down 2 tingkat untuk item sederhana, pengelompokan lebih dalam hanya kalau membantu. `Komunitas > Artikel` tautan langsung ke `/artikel`; kategori/penulis/filter di halaman artikel. Announcement banner promo/diskon/grup/event di atas navbar, diatur admin.
- **Autentikasi & profil:** Google OAuth, email verification, username unik, editable profile & SVG avatar, email noneditable oleh user, password email OTP, newsletter opt-in, role, child safety, keamanan dan account recovery.
- **Academy:** lima akademi (Programming Fundamentals, Data Analytics/Science, Cybersecurity, Web/App Developer, Finance/Econometrics/Research); course/lesson campuran (YouTube, **Scrim**, text/PDF, resource/tool download, lab, quiz, assignment), learning paths, bootcamp/classroom, ebook, diagnostic, spaced repetition, resume progress, final exam & certificate.
- **Scrim:** rekaman aktivitas editor + audio + timestamp/checkpoints, bukan OCR video; ketika pause kode bisa dipilih/disalin/diedit/di-run dalam sandbox; resume kembali ke original recording; simpan learner fork opsional. Scrim merupakan **jenis lesson di Course**, Studio di authoring admin/instructor, bukan produk tersendiri di navbar.
- **Labs:** coding playground Free/Pro (HTML/CSS/JS, Python/Pyodide, SQL WASM, R/WebR bila didukung), coding lab terstruktur, challenge test cases, code review, cybersecurity simulations aman, game edukatif, notebook, dan project saving. Run dasar GRATIS; jangan eksekusi kode arbitrer pada server PHP shared hosting.
- **Content & social:** artikel multi kategori dengan filter penulis, share sosial, komentar nested, feed komunitas dan moderasi, short videos, podcast/transcript, galeri, showcase, mentorship, diskusi, Telegram optional, WhatsApp official optional, tier konten premium.
- **Commerce:** free vs one-off course vs subscription, bundle, discount, voucher, merchandise order & preorder, event gratis/berbayar, bootcamp payment, Midtrans embedded (bukan popup), webhook verification, refund/cancellation policies, group checkout **satu pembayar** dengan N email akun existing verified dan entitlements per akun.
- **Reward:** XP nonspendable untuk leaderboard/level; **Bara** spendable untuk course/merch/voucher; aturan pencapaian kelas/tugas/challenge, weekly check-in terbatas, ledger transaksi, antifraud, sertifikat HTML+PNG/JPG+verification URL, LinkedIn Add to Profile semi-manual/official approval.
- **Admin:** manajemen semua domain, peran, CMS, instructor, checkout, inventory, reward policy, announcement, newsletter, users, support tickets, whistleblowing, analytics, moderation, audit, docs API & design system.
- **Hosting:** free/open source, Laravel 13 PHP >=8.3, React/TypeScript, Inertia tanpa Node SSR produksi, MySQL/MariaDB, shared hosting dengan cron, Vite build di komputer/CI; tidak butuh VPS/Redis/paid AI/storage. Fitur yang secara inherent membutuhkan privileged server runtime harus memiliki mode aman setara tujuan belajarnya atau status INFRA_BLOCKED yang jujur.

## PENERIMAAN AKHIR

- Semuanya terhubung melalui database/otorisasi, navigasi valid, transaksi nyata hanya di sandbox sampai siap production, dan antarmuka bisa digunakan tanpa dead ends palsu.
- Jalankan test suite unit + feature + E2E + build + lint; sertakan bukti command dan hasil pada `IMPLEMENTATION_STATUS.md`.
- Semua migrasi, seed data contoh yang dilabeli jelas, `.env.example`, setup lokal, setup shared hosting, cron/SMTP/Midtrans/Google/Telegram guide, backup/restore dan keamanan tersedia.
- Buat halaman dokumentasi API dan panduan pengembangan brand (tokens, tipografi, komponen, prinsip) yang aktual sesuai kode.
- Jangan menandai produk selesai jika fitur inti hanya berupa placeholder. Jika terhalang layanan eksternal yang tidak dikonfigurasi, sediakan implementasi integrasi dan langkah konfigurasi yang dapat diuji dengan sandbox, bukan klaim sudah terkoneksi.

**Mulai membangun sekarang. Jangan berhenti setelah halaman pertama atau milestone awal.**

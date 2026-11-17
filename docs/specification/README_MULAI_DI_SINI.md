# TERAS NGULIK — Paket Instruksi Sistem Penuh untuk Codex

**Versi:** 2 November 2026  
**Tujuan:** Berikan kepada Codex **cakupan seluruh produk**, bukan hanya prototipe / milestone pertama.

## Cara menggunakan

1. Ekstrak isi ZIP ini ke **root repository** proyek `teras-ngulik` (atau minta Codex mengekstraknya). Pertahankan struktur folder dan nama berkas.
2. Minta Codex membaca `AGENTS.md` terlebih dahulu, lalu seluruh spesifikasi dan lampiran di paket ini.
3. Tempel pesan berikut di Codex:

> Baca `AGENTS.md` dan `CODEX_FULL_BUILD_PROMPT.md` serta semua berkas spesifikasi dalam repository. Bangun **SELURUH sistem Teras Ngulik** sesuai cakupan produk, tidak berhenti pada Milestone 1. Kamu sendiri yang mengatur urutan dan dependensi pengerjaan secara internal, mengimplementasikan, menguji, memperbaiki, dan melanjutkan ke fitur berikutnya. Pertahankan aplikasi yang bisa dijalankan sepanjang proses. Jangan menganggap halaman kosong atau tombol palsu sebagai fitur selesai. Catat progres dan keterbatasan nyata secara jujur. Gunakan aset logo referensi yang tersedia.

4. Jika menggunakan Codex dalam sesi yang berakhir karena konteks atau batas eksekusi, lanjutkan dengan: `Baca AGENTS.md, PRODUCT_SPEC.md, dan IMPLEMENTATION_STATUS.md; lanjutkan pembangunan semua fitur yang belum selesai, jangan ulang dari nol.`
5. **Tidak ada klaim fitur sudah jadi hanya karena spesifikasi lengkap.** ZIP ini berisi dokumen dan logo referensi, **belum** source code aplikasi penuh. Codex diminta membuat aplikasi.

## Peta dokumen

| Berkas | Isinya |
|---|---|
| `AGENTS.md` | Instruksi tetap, prioritas produk, standar keamanan, dan batas shared hosting |
| `CODEX_FULL_BUILD_PROMPT.md` | Instruksi eksekusi seluruh sistem, bukan milestone terbatas |
| `PRODUCT_SPEC.md` | Seluruh fitur, akses, aturan bisnis, dan lima akademi |
| `NAVIGATION_AND_UX.md` | Sitemap, navbar mega menu fleksibel, dashboard, layout, responsif, aksesibilitas |
| `TECH_ARCHITECTURE.md` | Teknologi gratis, modul aplikasi, API, runtime browser, arsitektur scrim |
| `DATA_MODEL.md` | Entitas/tabel, relasi, constraints, keamanan, pengindeksan |
| `SCRIM_SPEC.md` | Rekaman editor interaktif, pemutaran, jeda/edit/copy/run, resume, studio |
| `BUSINESS_FLOWS.md` | Alur autentikasi, checkout, group purchase, rewards, sertifikat, dan lain-lain |
| `TEST_AND_ACCEPTANCE.md` | Skenario pengujian & definition of done seluruh subsistem |
| `REQUIREMENTS_TRACEABILITY.md` | Pemetaan 33 fitur awal + tambahan agar tidak ada yang terlupakan |
| `DEPLOYMENT_GUIDE.md` | Cara rancang agar cocok dengan shared hosting PHP + MySQL murah |
| `config/navigation.json` | Konfigurasi referensi menu publik + dashboard |
| `config/design-tokens.json` | Palet warna dan token dasar UI |
| `assets/teras-ngulik-logo-icon-reference.png` | Referensi logo ikon tanpa wordmark, bukan SVG siap produksi |

## Keputusan produk wajib

- **Brand:** Teras Ngulik, bahasa Indonesia dahulu, opsi Inggris.
- **Lima akademi:** Programming Fundamentals; Data Analytics & Data Science; Cybersecurity; Web & Application Development; Finance, Econometrics & Research.
- **Navbar:** pola dropdown/mega menu seperti referensi Skilvul, **tidak memaksakan 3 tingkat**. `Komunitas > Artikel` langsung ke `/artikel`; kategori/penulis/filter disajikan dalam halaman itu.
- **Scrim:** jenis materi di dalam Course (alternatif/pendamping video YouTube), **bukan menu publik atau produk mandiri**.
- **Coding Playground:** menu di Labs dengan paket Free/Pro; fitur Run dasar tetap gratis; eksekusi browser saja di shared hosting.
- **Group checkout:** **satu pembayar** mengisi email peserta yang sudah terdaftar, **satu pembayaran**, grant akses ke tiap akun setelah webhook terverifikasi.
- **Gamifikasi:** XP untuk level/leaderboard; **Bara** dompet reward yang bisa ditukar course/merch/voucher. Dua saldo/ledger yang terpisah.
- **Logo:** gunakan file ikon referensi; jangan otomatis menambah tulisan ke dalam ikon. Pada header bisa tampil ikon + teks merek sebagai komponen terpisah.
- **Hosting:** utamakan perangkat lunak gratis/open source, Laravel + React + MySQL pada shared hosting PHP; tidak mewajibkan VPS, server Node permanen, Docker, Redis, API AI berbayar atau R2.
- **Integrasi komersial:** Midtrans, Google, SMTP, Telegram, LinkedIn hanya benar-benar berfungsi setelah kredensial/izin resmi tersedia. Tidak boleh dipalsukan.

## Catatan kapasitas

Shared hosting bertarif ratusan ribu **bukan sumber daya komputasi tanpa batas**. Simulasi keamanan dan runtime kode browser harus sesuai batas nyata. Dokumentasikan bila fitur membutuhkan fasilitas lebih besar daripada hosting target; sediakan mode alternatif yang benar-benar aman dan fungsional.

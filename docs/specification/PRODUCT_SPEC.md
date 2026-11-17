# TERAS NGULIK — FULL PRODUCT REQUIREMENTS DOCUMENT

**Status:** source-of-truth seluruh cakupan akhir produk. Tidak boleh dipersempit hanya menjadi Milestone 1.  
**Platform:** Indonesia-first; dapat diperluas ke Inggris; desktop dan mobile; PWA opsional.  
**Misi:** tempat menyenangkan untuk belajar, mengulik, berlatih, membuat karya, dan berkembang dari anak SD hingga profesional/peneliti.

## 1. Prinsip produk dan pengguna

1. Mayoritas materi awal/intro **gratis dan benar-benar bermutu**; kelas berbayar adalah upgrade dengan nilai jelas, bukan tombol Run dasar dibatasi.
2. Interaktivitas menyesuaikan **usia** dan **kemampuan** sebagai dua atribut independen. SD: permainan, blok, cerita, sangat visual; SMP: jembatan blok ke kode teks; SMA: coding/project; mahasiswa/umum: kasus nyata dan alat riset/profesional. Jangan memaksa pengguna pemula dewasa memakai tema kanak-kanak.
3. Pembelajaran berbasis praktik: course -> lesson (YouTube/scrim/dokumen/resource/lab/kuis/tugas) -> proyek -> evaluasi -> sertifikat bila lulus.
4. Kinerja mobile, aksesibilitas, privasi anak, keamanan transaksi, keterbacaan, dan biaya operasional merupakan persyaratan inti.
5. Pengguna: guest, learner (termasuk mode anak sesuai kebijakan), parent/guardian, instructor, mentor, editor, moderator, support, partner, admin, superadmin. Satu akun bisa memiliki beberapa role, tetapi izin tetap granular.
6. Artefak pengguna (catatan, fork scrim, proyek, portofolio, sertifikat, progres) milik akun pengguna, dengan hak ekspor sesuai ketentuan.
7. Konten dengan visibilitas `public`, `registered`, `subscribers`, `purchased`, `cohort`, `admin/instructor` harus dikontrol di backend, termasuk API/download.

## 2. Lima akademi & learning path

| Academy | Contoh topik | Praktik |
|---|---|---|
| Programming Fundamentals | Computational thinking, block programming, Python, JavaScript, algoritma | Puzzle blok, kode, debugging, mini-game |
| Data Analytics & Data Science | Excel, SQL, Python, R, data cleaning, statistik, visualisasi, machine learning | SQL sandbox, notebooks sederhana, analisis dataset |
| Cybersecurity | Digital safety, networking, Linux theory, keamanan web, secure coding, CTF defensif | Simulasi lokal/browser yang aman, log analysis; tanpa target internet nyata |
| Web & Application Development | HTML, CSS, JS, TS, React, backend/API konsep, DB, deployment | Live preview, komponen, project website |
| Finance, Econometrics & Research | Literasi keuangan, financial modeling, statistik, Stata, R, panel/time series, causal inference, laporan penelitian | Dataset latihan, R/WebR, do-file Stata downloadable, reproducible report |

- Akademi adalah taxonomy utama. Course dapat terhubung ke beberapa topik/tag, tetapi memiliki akademi primer yang jelas.
- Learning path = kurasi course dengan urutan, prasyarat, milestone, project dan completion rules. Sertifikat path opsional setelah semua prasyarat terpenuhi.
- Diagnostic onboarding memilih rekomendasi akademi & level (tidak menjadi penghalang mengeksplorasi course lain).
- Per jenjang: quiz pilihan A-D default SD/SMP; A-E default SMA/kuliah; admin bebas menambah/mengurangi opsi; dukung satu/jamak jawaban benar, urutan acak dan penjelasan.

## 3. Akun, autentikasi, profil, pengaturan

- Registrasi email+password dengan verifikasi email; login email+password; Google OAuth jika client credentials dikonfigurasi; password reset; logout/session management.
- Username unik, normalized & case-insensitive, misal `@terasku`; display name tidak harus unik. Gunakan immutable user ID internal; username bisa berubah terbatas dan redirect alias profil lama ketika perlu.
- Email privat dan **tidak dapat diedit mandiri** di UI/API; perubahan hanya lewat workflow pemulihan akun support dengan autentikasi kuat dan audit. Untuk password accounts, perubahan password memerlukan OTP email/re-authentication baru dengan expiry, rate limit, invalidation dan anti brute force. Pengguna OAuth-only dapat membuat password via alur verified bila diizinkan.
- Edit foto/avatar (termasuk pilihan avatar SVG generatif yang deterministic, aman dan bisa diubah), display name, username, bio, minat, link website/GitHub/LinkedIn, pronouns hanya jika opsional dan benar-benar perlu, bahasa, preferensi notifikasi dan privacy.
- Profil orang dewasa boleh public/limited/private; untuk anak default private. Jangan mengekspos email atau data sensitif pada profil. Parental/guardian account & consent/age appropriate restrictions mengikuti aturan aktual.
- Onboarding interaktif pertama: pilih jenjang/tujuan, diagnostic opsional, tampilkan lokasi course/lab, izinkan skip & replay walkthrough. Progress/capability radar harus berasal dari skor/skill evidence yang nyata, bukan sekadar menonton.
- Halaman settings: akun, keamanan, sesi, profil, email notifikasi, newsletter, bahasa ID/EN, theme, accessibility prefs, privasi, ekspor data dan penutupan akun (sesuai ketentuan retensi).

## 4. Course, LMS, materi dan penyelesaian

- Course: slug, judul, cover, ringkasan, academy, tingkat, target usia, prerequisites, bahasa, silabus, durasi perkiraan, pengajar, harga/hak akses, tanggal publikasi, versi konten, draft/review/published/archived.
- Modul berurut; lessons berurut; satu course **boleh mencampur** `youtube_video`, `scrim`, `rich_text`, `document/pdf`, `download_resource`, `lab`, `quiz`, `assignment`, `external_resource`.
- Admin/instructor bebas memilih format dari tombol `Tambah Materi` (tanpa memerlukan developer mengedit kode). WYSIWYG/Markdown, file upload, link tool resmi, lampiran dengan perizinan, preview, draft autosave, scheduling, revisi, reviewer.
- Video YouTube iframe mengikuti ToS; brand/logo/link/kontrol tidak ditutup; ambient glow hanya luar frame. Video privat premium memerlukan provider/solusi yang benar-benar mengontrol akses (tidak menganggap Unlisted YouTube private).
- Scrim = **editor recording** interaktif dalam lesson (bukan link menu), detail di `SCRIM_SPEC.md`.
- Pelajar: pause/resume, progress, autoplay next lesson opt-in, resources, catatan per timestamp, bookmark, diskusi per course, laporkan masalah, transkrip/caption bila video.
- Progress Resume per lesson: scroll/timestamp, code snapshot, completion state, quiz state, terakhir dilihat, timestamp dan versi course; menangani repeat/watch differences; tidak mempercayai durasi video sebagai bukti tunggal kelulusan.
- Completion mengikuti rule per course: lessons wajib, kuis minimal score, lab validated, assignment/final project dinilai, kebijakan retake, peer review optional; sertifikat hanya setelah validasi.
- Kuis dalam course dan kuis mandiri tidak sama; bank soal dapat direuse dengan aturan izin dan paket soalnya masing-masing.
- Tugas akhir: upload file, link repo/demo, teks, file hasil; rubric & feedback; resubmit, plagiarism review manual, deadline bootcamp bila berlaku.

## 5. Learning diagnostic, spaced repetition, notebook, bookmark

- Diagnostic: tes awal adaptif ringan berdasarkan akademi, usia & level; hasil kategori dan rekomendasi tanpa mengunci kelas; record per assessment dan retest.
- Spaced repetition: kartu konsep/recall dari lesson dan kesalahan quiz; scheduler FSRS implementasi TypeScript OSS atau fallback algoritma lokal bila dependency tidak kompatibel; rating Lupa/Sulit/Ingat/Mudah, scheduling `due_at`, historical review; manual card creation; pilihan menonaktifkan reminder. Gunakan latihan debugging recall coding, bukan hanya flashcard.
- Notebook: halaman pribadi berisi rich text/Markdown, code snippet, tautan lesson, timestamp scrim/video, pencarian, tags, ekspor Markdown; bookmarks course/article/video/lab, koleksi pribadi.
- Tidak memberi sertifikat dari review harian; XP/Bara jika ada harus mengikuti challenge config & antiabuse, bukan per klik tanpa batas.

## 6. Coding Playground (free dan premium)

- Halaman publik `/labs/playground` dengan pilihan bahasa/mode, editor, file manager terbatas, run/console/preview, reset, copy, download, share apabila user setuju, autosave lokal/akun.
- **Free:** `Run` untuk HTML/CSS/JS, Python (Pyodide), SQL (SQLite WASM), R (WebR) sesuai dukungan perangkat dan pilihan bundel; snippet+template inti, sedikit saved projects, unduh kode; dukung guest run terbatas yang aman.
- **Pro:** lebih banyak proyek tersimpan, multi-file yang didukung runtime, version history, private sharing, template tambahan, dataset uploads dalam quota, test runner advanced, collaborative features hanya bila ada infrastruktur real-time yang benar-benar mendukung. Jangan memonopoli tombol Run dasar untuk Pro.
- Kode tidak dieksekusi di server shared PHP; gunakan sandbox iframe asal terpisah untuk HTML/JS bila memungkinkan, Worker + WASM & batas ukuran/time/memory; CSP, message validation, disable same-origin privileges yang tidak perlu. Berikan tombol stop jika runtime bisa dihentikan.
- Tidak boleh berjanji menjalankan seluruh pip/npm/package arbitrer, Docker, Linux root, Node server, Stata executable atau R full environment jika runtime tidak tersedia. Jelaskan batas runtime yang valid di UI.
- Projekte menyimpan bahasa, manifest, file snapshots, visibility, title/description, forks, share slug, thumbnail (jika aman), version history berdasarkan paket.

## 7. Coding Lab, Code Review, challenge, game, cyber

- Coding Lab terstruktur: instruksi, starter code, test cases, solution/rubric, hints, output, submissions, skor, feedback. Test case publik dan hidden pada judge tepercaya; jika hanya browser, hidden tests tidak benar-benar rahasia dan tidak boleh digunakan untuk credential exam yang high stakes.
- Code Review: automated linter/static checks lokal untuk bahasa kompatibel, feedback test case, peer review/moderation dan instructor review; komentar baris kode, status review, rubrik.
- Challenge: individual/tracks, difficulty, daily/weekly, tags, submission result, anti-spam, achievement XP/Bara configured; non-compute-intensive judge pada browser dan verify trusted nonarbitrary tasks di backend.
- Game: Code Maze, Bug Hunter, Algorithm Rush, SQL Detective, Cyber Escape Room dan mini game logic; harus ada target keterampilan, tetapi **game santai** tidak otomatis XP/Bara. Tampilkan mode umur dan akses keyboard jika memungkinkan.
- Cyber Lab pada shared hosting: vulnerable simulations isolated browser yang tidak menembus infra, dataset log/skenario CTF defensif; **tidak** mengekspos target rentan nyata atau eksekusi eksploitasi pada server. Lab VM/container lebih lanjut berstatus INFRA_BLOCKED sampai terpisah secara aman.
- Data Lab: dataset CSV di browser, SQL, Python/R WASM; batas file/performa jelas. Finance/Research Lab: pengajaran Stata melalui dataset/do-file, run R via WebR jika didukung.

## 8. Artikel, media dan konten berbayar

- Artikel berstatus draft/review/publish/archive; richtext, cover, slug SEO, metadata, tags dan **banyak kategori** (`many-to-many`), by-author. Index `/artikel`: filter kategori/tag/penulis, search, sort, featured, pagination. **Jangan membuat submenu `Kategori Artikel`, `Artikel Penulis`, `Konten Baru Artikel`**; semua filter dalam page.
- Artikel: comment nested, report, bookmark, share via Web Share / direct social links, OG preview, related articles, table of contents, code blocks, protected premium sections with backend entitlement.
- Ebook: metadata, reader (jika file lisensi mengizinkan), download policy, covers; premium gated. Podcast: audio player, transkrip, chapter, kategori, bookmark, show notes. Video pendek: feed vertikal edukatif, caption, CTA ke course; tidak menduplikasi engagement manipulatif. Galeri: karya & dokumentasi dengan consent/perizinan. Semua media dapat gratis / subscriber / purchased sesuai rules.
- File downloads melalui server authorization/signed route; tidak memberi link publik langsung kepada orang tanpa entitlement bila materi privat.

## 9. Komunitas, showcase dan komunikasi

- Post feed komunitas: tipe pertanyaan, diskusi, pengumuman, showcase, polling; topik/tag, follow topic, feed relevant, pagination, sorting, reactions opsional (bukan keharusan like/dislike).
- Komentar beranak seperti YouTube; reply to parent, expand conversation, UI depth maksimal ergonomis (data tree tetap penuh); edit/delete own comments, report, mention yang aman, notifikasi, anti-spam, rate-limit, moderation.
- Project Showcase: user submit proyek (repo, demo URL, screenshot, teknologi, role, deskripsi), aksesibilitas, feedback dan publikasi sesuai privacy; moderasi outbound URLs. Terhubung ke Portfolio Builder.
- Telegram Bot resmi opsional: kirim notifikasi posting/highlight ke grup/channel yang mengotorisasi bot, punya throttle/digest dan pengaturan admin. WhatsApp hanya jalur resmi/consent/approval dan kebutuhan biaya nyata; **tidak wajib untuk running lokal**.
- Akun anak: tidak otomatis ikut feed sosial publik dan tidak memiliki DM bebas dengan orang asing; guardian flows/moderated space.

## 10. Program & mentorship

- Bootcamp: program -> cohort/batch -> classroom -> modules/lessons/attachments/assignments/attendance/grades/schedule/members/instructors/announcements. **Classroom berada di dalam Bootcamp**, bukan produk menu publik tersendiri.
- Mentorship: profil mentor, skill/topic, slot/availability, request, booking, cancellation, consent, notes, review dan payment opsional (jika monetized); jangan menjanjikan video conference server sendiri, gunakan link eksternal resmi.
- Event gratis/berbayar: listing, ticket type, quotas, signup, invoices, check-in QR opsional, online/offline details, reminders, cancellations/refund.
- Hackathon & Competition: event, rules, registration individual/tim, qualification, deadlines, submission (project/repo/URL), judges, rubric, leaderboard, prizes, appeals, audit.
- Career: lowongan kurasi, application/outbound job links, portfolio matching sederhana, career resources; moderasi posting. Partner: form proposal kegiatan/kolaborasi, admin workflow, docs/attachment.

## 11. Monetisasi: produk, checkout, subscription, patungan, marketplace

- Product catalog unified (`course`, `bundle`, `subscription`, `event`, `bootcamp`, `ebook`, `merch`, `preorder`, `reward`). User sees transparent pricing, free/premium status, what included, validity, tax/fee, refund terms.
- Access types: FREE, ACCOUNT, PURCHASED, ACTIVE_SUBSCRIPTION, BOOTCAMP/CLASSROOM, ADMIN_GRANT; use independent entitlement service backend; course purchased remains accessible sesuai terms meski subscription berakhir.
- Midtrans Snap **embedded** (bukan popup), dengan desain halaman outer yang custom; provider UI internals tidak dipalsukan; sandbox default, protected API keys server only; signed/validated webhook; order and settlement states; refund and expiration policies.
- Voucher/promo: percent/fixed, min basket, maximum discount, total/per-user usage, start/end UTC/local display, segment, products, stacking rules, scheduled announcement bar di ATAS navbar. Jangan gunakan countdown stok palsu.
- **Group checkout/patungan**: pembeli memilih course dan N peserta, memasukkan N **email akun existing verified**, validasi duplikat, kursi, akses sebelumnya & consent; bila ada non-user, **suruh ganti email**, bukan otomatis mengundang/menagih peserta. SATU pembeli membayar sekali; setelah webhook settlement terverifikasi, grant entitlement ke N penerima secara atomik; notifikasi tiap akun. Kasus failed/expired/refund tidak memberi/mencabut akses sesuai aturan yang jelas.
- Subscription Free/Plus/Pro atau harga final ditentukan admin, tidak hardcode contoh harga. Period renewal dan entitlement dapat dijalankan melalui status payment verified, scheduler cron for expiration, notifications, grace, cancellation.
- Marketplace: merchandise fisik dengan stok/inventory/varian/ongkir/alamat & fulfilment, preorder dengan tanggal perkiraan/cap/fulfilment/refund, digital goods tanpa ongkir; jangan mengklaim kurir/ongkir otomatis tanpa API resmi.
- Reward Bara bisa menukarkan course, kupon, merchandise; jika inventory terbatas kunci stok transaksional dan buat order reward terpisah.

## 12. XP, Bara, sertifikat, leaderboard, social profile

- XP = perolehan reputasi **tak bisa ditukar** untuk level, rank, seasonal leaderboard, per academy/age-band grouping; ledger event XP dan antifraud. XP dapat diperoleh dari lulus course/proyek dan challenge tertentu; weekly check-in kecil dibatasi sebagai kebiasaan belajar. Game fun tidak otomatis XP.
- Bara = **separate spendable wallet**, sumber reward dari kelulusan/challenge/promo yang disetujui, transaksi immutable debit/credit, idempotency, balance yang aman, tidak diuangkan/ditransfer antar pengguna secara default. Nama teknis `bara_balance` boleh menjadi derived cache dari ledger, bukan sumber kebenaran tunggal.
- Reward shop: courses, merchandise, discount coupons, limited reward, cost Bara, eligibility, stock, fulfilment, cancellation/reversal, audit.
- Badge hanya atas prestasi/kelulusan; certificate course/bootcamp berdasarkan evaluated completion, verification URL, credential ID unik, HTML template dan PNG/JPG export dengan browser capture yang layak; status revoked/valid. Download sertifikat bukan bukti akreditasi yang tidak dimiliki.
- Integrasi LinkedIn: tombol/share credential, copy title/issuer/id/verification URL dan alur Add to Profile; jangan menjanjikan otomatis menulis credential ke profil tanpa official API approval.
- Portfolio Builder: profil proyek, link GitHub/demo, stack, thumbnail, cerita proses, sertifikat terverifikasi, skill radar berdasarkan asesmen; akses public/private dan anak default private.

## 13. Newsletter, notifikasi, support & whistleblowing

- Email transaksional: signup verify, OTP/reset, enrolment, receipt/order, event reminders, cert issued, account security. Custom SMTP dari hosting atau free-tier provider saat tersedia; fail safely if not configured.
- Newsletter: welcome email transaksional tanpa marketing, pilihan **explicit opt-in** saat onboarding/subscription, unsubscribe token, categories/frequency, admin campaign builder/drafts/approval/schedule/segments, send queue cron batching, suppression list, unsubscribe link.
- In-app notifications: replies, mentions, course announcements, reward, payment status, deadlines; unread/read/filter, preferences, email digest. Telegram official optional; WhatsApp official optional.
- Customer Service: FAQ/help center, searchable knowledgebase, contact form, tickets, categories, priorities, status, attachments, user conversation, CS staff permissions; no personal data in public tickets.
- Whistleblowing: separate secure channel, anonymous option if legal/operationally supported, restricted case worker access, attachment protections, non-indexed URLs, escalation process, audit, data retention. Never dump reports into public CRM or community.

## 14. Admin, analytics, policy, docs

- Admin dashboards: learner active count, conversion, course completion, revenue, chargebacks/refunds, content queue, moderation queue, ticket SLA, challenge participation; time filters & privacy-aware aggregation; no invented live charts.
- Roles: superadmin/admin/instructor/editor/moderator/support/mentor/partner/learner/guardian; per-object access control. Admin modules: catalog, academy, course/lesson & Scrim Studio, learning paths, questions, labs, events, commerce, community, market inventory, newsletter, rewards, certificates, legal content, branding, navigation, audit.
- SEO/sitemap, site search, route redirects, robots, canonical/hreflang. Translations for UI `id`/`en` JSON or DB config; article/course per-locale content with editorial review. Auto translation via external service **not mandatory** and must not rely on paid API.
- Docs site: public API docs (only documented safe endpoints), authentication/errors/rate limits/examples, theme tokens, typography, icons, UI component catalog, development principles, deployment, security and privacy statements.
- Accessibility: WCAG 2.2 AA aspirational with keyboard, screen reader, captions/transcripts, text alternative, reduced-motion, touch targets, accessible contrast, skip links and accessible game alternatives.
- Legal: Terms, Privacy, Refund, community guidelines, cookies, accessibility, complaint/whistleblowing, rights for children/guardian, disclosures for course certificates and AI features.

## 15. Pengembangan UI/UX berorientasi pengguna

- Homepage: hero ilustrasi ringan (inspirasi animasi tumbuh/partikel yang menjadi bentuk, tidak wajib video besar), 5 akademi, cara kerja, coba playground gratis, course unggulan, learning path, komunitas/showcase, program, opsi membership, FAQ/footer.
- Panel multi-step onboarding di awal, reversible, no traps. Pastel + vivid blue; Butter untuk reward; Ink untuk teks/dark shell. Dashboard user/admin polished, bukan menumpuk semua komponen.
- Skeleton loading, real empty states, progressive enhancement, accessible mobile drawer, search omnibar, notifications, activity toast, microinteraction, error recovery, retry, offline detection.
- Footer & legal dapat dicapai tanpa login; monetisasi transparan; no fake reviews/testimonials/user counts. Subscriptions & premium label jelas.

## 16. Non-functional and definition of done

- Production viability with PHP shared hosting using SQL database, cron, filesystem and precompiled React assets. Test setup pada sample hosting capability checklist; big media and runtime quotas documented.
- Automated tests for authentication, roles, access, payments, group entitlements, XP/Bara, progress resume, Scrim pause/fork/resume, quiz scoring, race/idempotency, media gates, child privacy, file uploads, frontend navigation and E2E critical paths.
- Logging, basic analytics, rate limiting, backup/restore, configuration via `.env`, seed examples, migrations, API documentation, responsive and accessibility checks.
- **Lingkup seluruh produk tetap tertera hingga selesai; Codex yang merencanakan urutan internalnya.** Implementasi bisa memerlukan banyak sesi, tetapi jangan menghapus modul yang belum selesai dari daftar cakupan.

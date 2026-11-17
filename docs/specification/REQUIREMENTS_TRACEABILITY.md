# Requirements Traceability — Jangan Ada Fitur Terlewat

Dokumen ini adalah daftar cakupan **seluruh produk**, bukan urutan pengerjaan. Codex wajib menjaga status tiap baris pada `IMPLEMENTATION_STATUS.md` (`DONE`, `IN_PROGRESS`, `NOT_STARTED`, `NEEDS_CONFIGURATION`, `INFRA_BLOCKED`). Tidak boleh menandai `DONE` berdasarkan UI shell saja.

## Daftar fitur permintaan awal pengguna

| ID | Fitur pengguna | Lokasi antarmuka | Aturan/implementasi utama |
|---|---|---|---|
| F01 | Course + pilihan video, dokumen, link tools & materi | Belajar > Course; admin Course Builder | mixed lessons, akses berjenjang, PDF/resource, scrim sebagai lesson |
| F02 | Artikel kategori jamak, per user | Komunitas > Artikel | `/artikel` direct link, multi-category, filter penulis di halaman |
| F03 | Komunitas seperti sosmed | Komunitas > Diskusi | feed/posts/questions/polls/showcase/moderation |
| F04 | Komentar beranak di artikel/komunitas | detail artikel/post | tree comments, reply/edit/delete/report, expand replies |
| F05 | Detail course seperti video platform, embed YouTube | Course lesson reader | fokus content/player, playlist modul, ambient glow luar iframe, legal ToS |
| F06 | Ebook | Belajar > Ebook | reader/download access/free/premium |
| F07 | Dashboard Admin | `/admin` | full CMS/payments/moderation/analytics/system settings |
| F08 | Kuis terpisah dari course | Belajar > Kuis Mandiri | quiz bank & attempts terpisah dari course quiz |
| F09 | Lab | Labs > Coding Lab | safe browser runtimes, structured exercises/test feedback |
| F10 | Kontak | Tentang > Kontak | form + CS routing |
| F11 | Midtrans embedded custom checkout | checkout | Snap embedded, provider backend webhook, outer custom UI |
| F12 | Marketplace normal + preorder | Marketplace | merchandise/SKU/stock, preorder/fulfilment |
| F13 | Event gratis/berbayar | Program > Event | tickets/registration/paid gates |
| F14 | Bundling kelas | Marketplace > Bundling | bundle purchase entitlement per course |
| F15 | Subscription, purchase, diskon, patungan | Marketplace + Checkout | plan/coupon/promo/banner, **one payer N existing-account emails** |
| F16 | Bootcamp | Program > Bootcamp | cohorts and Classroom internal to bootcamp |
| F17 | Learning path | Belajar > Learning Path | ordered courses prerequisites/progress |
| F18 | Reward | Dashboard > Bara & Reward | Bara redeem shop + ledger |
| F19 | Karier | Program > Karier | vetted job listings/portfolio |
| F20 | Partner | Program > Partner | partnership inquiry workflow |
| F21 | Whistleblowing | Footer + protected case | restricted reports/ACL/audit |
| F22 | Syarat Ketentuan | Footer | versioned legal pages |
| F23 | Kebijakan Privasi | Footer | privacy & child policies |
| F24 | Bantuan | Tentang > Bantuan | Help/FAQ + ticket |
| F25 | Bahasa Inggris | Settings/header | UI ID/EN and authored content translation, no automatic dependency |
| F26 | Customer service | Bantuan / Dashboard | ticket conversation + admin inbox |
| F27 | Accessibility tools | user preferences/site | WCAG 2.2 AA goal, keyboard/reduced motion/captions |
| F28 | Sertifikat HTML export JPG/PNG | Dashboard > Sertifikat | completion verified + credential URL/LinkedIn |
| F29 | Dokumentasi API & website/design principles | Tentang > Dokumentasi | real OpenAPI + design tokens/component docs |
| F30 | Video pendek seperti reels | Belajar > Video Pendek | educational feed + CTA, subscriber gates |
| F31 | Game | Labs > Game; Arena component | games by age/skills, no XP farming |
| F32 | Podcast | Belajar > Podcast | audio/transcript/tier access |
| F33 | Galeri | Komunitas > Galeri | portfolio/events images + privacy |

## Tambahan pengguna yang telah disetujui

| ID | Tambahan | Lokasi/ketentuan |
|---|---|---|
| X01 | Scrim Scrimba-like | **Dalam lesson Course**, editor+audio original timeline; pause/edit/run/copy, resume original, save fork |
| X02 | Portfolio Builder | Dashboard Profil & Portofolio |
| X03 | Code Review | Labs > Code Review + review instructor |
| X04 | Progress Resume | Lesson/course reader dan Dashboard lanjutkan belajar |
| X05 | Notebook & Bookmark | Dashboard > Catatan, tombol di artikel/course/scrim |
| X06 | Learning Diagnostic | Onboarding + rekomendasi path |
| X07 | Spaced Repetition | Dashboard > Review Harian (FSRS), concept cards + coding recall |
| X08 | Project Showcase | Komunitas > Project Showcase |
| X09 | Mentorship | Komunitas > Mentorship |
| X10 | Hackathon & Competition | Program > Kompetisi |
| X11 | Classroom dalam Bootcamp | Program > Bootcamp > Cohort > Classroom, bukan produk mandiri |
| X12 | Announcement bar diskon/patungan | Atas navbar, admin controls schedule and audience |
| X13 | Email verification & username | Auth + Settings; email read-only, password email OTP |
| X14 | Newsletter | explicit opt-in, editor/campaign/queue/unsubscribe, not forced marketing |
| X15 | XP & Bara terpisah | XP leaderboard/level, Bara redeem course/merch; completion & challenge rules |
| X16 | LinkedIn certificate action | Credential page: title/ID/URL copy, user-initiated Add to Profile; official write API optional |
| X17 | Share artikel | article detail Web Share/URL/social, OG tags |
| X18 | Palet Butter + Ink + biru kuat | `config/design-tokens.json`, light/dark/accessibility |
| X19 | Finance/Econometrics/Research | Akademi ke-5, R/WebR, Stata do-file/dataset, finance models |
| X20 | Google login | Auth Google OAuth with official configuration |
| X21 | SVG avatar generation | Settings/Profile, secure generated variants |
| X22 | Skill radar 5 sumbu | Dashboard/profile from verified skill assessments |
| X23 | Walkthrough pengguna baru | first login onboarding, skip/replay help |
| X24 | Coding Playground Free/Pro | Labs > Playground; free basic Run, Pro quotas/history/extra templates |
| X25 | Grup satu bayar banyak email | group checkout one charge, requires all recipient emails existing & verified |
| X26 | Navigasi publik submenu fleksibel | mostly two levels; Artikel direct page, filtering internal; reference Skilvul pattern |
| X27 | Logo ikon tanpa wordmark | `assets/teras-ngulik-logo-icon-reference.png`; header wordmark separate text |
| X28 | Hosting murah & software gratis | Laravel+React+MySQL on compatible PHP shared hosting, browser WASM, no mandatory VPS/paid AI |

## Cakupan pengujian

Acuan pengujian per domain terdapat di `TEST_AND_ACCEPTANCE.md`. Dokumen ini harus ditinjau ulang setiap kali product scope berubah; jangan menghapus item tanpa keputusan pengguna.

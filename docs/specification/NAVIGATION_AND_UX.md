# Navigation, Sitemap & UI/UX — Teras Ngulik

## 1. Kerangka navigasi umum

**Referensi UX:** navbar desktop bergaya platform belajar Indonesia seperti Skilvul: brand kiri, menu horizontal tengah, search & auth kanan, dropdown/mega menu kategori yang mudah dilihat. **Default dua tingkat**, tidak seluruh submenu harus punya submenu lagi. Bila perlu panel kanan untuk promosikan course unggulan, tampilkan hanya konten nyata, hindari redundansi.

### Navbar publik (pengunjung dan pengguna login)

`[Announcement Bar — optional]`  
`[Logo icon + TERAS NGULIK] Belajar ▾ | Labs ▾ | Komunitas ▾ | Program ▾ | Marketplace ▾ | Tentang ▾ | Search | [Masuk] [Daftar]`

Saat login, sisi kanan menjadi: `[Search] [Notifikasi] [XP] [Bara] [Avatar ▾]`; pada layar kecil prioritaskan search, notification, avatar, dan menu drawer. Ada link dashboard dari avatar. Jangan memunculkan sidebar dashboard pada homepage biasa.

**BELAJAR:** Course (`/belajar/course`), Akademi (`/belajar/akademi`), Learning Path (`/belajar/learning-path`), Ebook (`/belajar/ebook`), Kuis Mandiri (`/belajar/kuis`), Podcast (`/belajar/podcast`), Video Pendek (`/belajar/video-pendek`). Lima akademi dapat ditampilkan dalam kolom rekomendasi dropdown jika benar-benar membantu, tetapi bukan keharusan hierarki baru.

**LABS:** Coding Playground (`/labs/playground`), Coding Lab (`/labs/coding-lab`), Coding Challenge (`/labs/challenge`), Code Review (`/labs/code-review`), Game (`/labs/game`).

**KOMUNITAS:** Artikel (`/artikel`), Diskusi (`/komunitas/diskusi`), Project Showcase (`/komunitas/showcase`), Mentorship (`/komunitas/mentorship`), Galeri (`/komunitas/galeri`). **`Artikel` direct link ke indeks; tidak ada submenu `Kategori Artikel`, `Artikel Penulis`, `Konten Baru Artikel`, `Semua Artikel`**, karena filtering berada di page itu sendiri.

**PROGRAM:** Bootcamp (`/program/bootcamp`), Event (`/program/event`), Hackathon & Kompetisi (`/program/kompetisi`), Karier (`/program/karier`), Partner (`/program/partner`). Classroom ada dalam detail Bootcamp, bukan item publik.

**MARKETPLACE:** Kelas Premium (`/marketplace/kelas`), Bundling (`/marketplace/bundling`), Langganan (`/marketplace/langganan`), Merchandise (`/marketplace/merchandise`), Preorder (`/marketplace/preorder`), Promo (`/marketplace/promo`).

**TENTANG:** Tentang Kami (`/tentang`), Bantuan (`/bantuan`), Kontak (`/kontak`), Dokumentasi (`/dokumentasi`).

**Announcement bar:** diletakkan di atas navbar, satu promo terlihat pada satu waktu. Target/priority/date & dismiss per user/session sesuai preferensi. Sumber boleh voucher, bundle, group purchase, event, kelas baru. Anti-false scarcity.

### Footer publik

Tentang, visi/tujuan, bantuan, FAQ, customer service, ketentuan (`/syarat-ketentuan`), kebijakan privasi (`/kebijakan-privasi`), refund (`/kebijakan-refund`), aturan komunitas (`/aturan-komunitas`), aksesibilitas (`/aksesibilitas`), whistleblowing (`/whistleblowing`), cookie preferences, media sosial, newsletter opt-in, bahasa ID/EN. Tidak perlu memaksakan setiap legal page sebagai nav utama.

## 2. Halaman publik dan layout

### `/` Homepage
1. Hero: judul mengundang, CTA `Mulai Belajar Gratis` dan `Coba Playground`; ilustrasi yang menceritakan ide tumbuh/berkembang; animasi ringan, reduced-motion.
2. Highlight belajar sambil praktik & Scrim (sebagai format lesson, bukan menu tersendiri).
3. Pilih 5 akademi sesuai usia dan minat.
4. Demo Coding Playground ringan gratis.
5. Course gratis dan unggulan berdasarkan data nyata.
6. Learning Path & langkah penyelesaian.
7. Showcase karya/komunitas (konten publik yang benar-benar ada).
8. Bootcamp/event, harga langganan dan nilai tambah premium.
9. FAQ dan footer.

### Halaman index
- `/belajar/course`: search, academy, usia, level, harga/free, bahasa, sort, card course.
- `/belajar/akademi`: lima card akademi, detail `/belajar/akademi/{slug}` dengan course/path/lab rekomendasi.
- `/belajar/learning-path`: urutan jalur kompetensi.
- `/belajar/ebook`, `/belajar/kuis`, `/belajar/podcast`, `/belajar/video-pendek`: listing masing-masing, search/filter inside page.
- `/artikel`: kategori multi-select, tag, penulis, tanggal, pencarian dan sort **di DALAM page**; detail `/artikel/{slug}`; optional locale paths.
- `/komunitas/diskusi` + `/komunitas/diskusi/{slug}`; `/komunitas/showcase`, `/komunitas/showcase/{slug}`, `/komunitas/mentorship`, `/komunitas/galeri`.
- `/labs/playground` editor dan project flow; `/labs/coding-lab`, `/labs/challenge`, `/labs/code-review`, `/labs/game`.
- Program dan marketplace index/detail sesuai taxonomy; `/cari` hasil global dengan tab dan permissions.

### Detail course + focused lesson reader
- Detail `/belajar/course/{slug}`: hero course, pengajar, rating bila terukur, level, bahasa, silabus, materi preview, manfaat, akses, harga, CTA enroll/buy, FAQ, review yang dimoderasi.
- Reader `/belajar/course/{slug}/lesson/{lessonSlug}`: bagian utama **satu player/editor** sesuai lesson kind; daftar modul + progres kanan sebagai sidebar desktop, drawer di mobile; tab ringkasan, lampiran, diskusi, catatan; tombol next/prev, resume, bookmark, lab link bila relevan.
- `youtube_video`: iframe compliant, ambient glow di luar frame; `scrim`: custom synchronized editor/audio player; `rich_text/document`: pembaca; `lab`: sandbox/lab detail; `quiz/assignment`: evaluasi. Lesson tidak memaksakan semua tab untuk setiap jenis.
- Code interactive UI: pause -> edit/copy/run/save fork -> resume original replay. Navigasi course tidak menampilkan `Scrim` sebagai menu top-level.

### Detail artikel
- Article header: title, tags, writer, waktu, share, save; body with code blocks/toc, comments/replies, related articles, premium entitlement if needed. CTA share near title/end and Web Share API on mobile.

## 3. Dashboard pengguna & profil

### `/dashboard` shell sidebar
- Ringkasan `/dashboard`
- Kelas Saya `/dashboard/kelas`
- Learning Path `/dashboard/learning-path`
- Bootcamp & Classroom `/dashboard/bootcamp`
- Lab & Proyek `/dashboard/proyek`
- Notebook & Bookmark `/dashboard/catatan`
- Review Harian `/dashboard/review`
- XP & Leaderboard `/dashboard/leaderboard`
- Bara & Reward `/dashboard/reward`
- Sertifikat `/dashboard/sertifikat`
- Profil & Portofolio `/dashboard/profil`
- Pengaturan `/dashboard/pengaturan`

**Ringkasan:** kartu lanjutkan lesson terakhir; progress 2–3 course, next review due, learning diagnostic recommendation, kalender bootcamp jika ada, challenge/XP/Bara, notifikasi penting. Tidak semua fitur harus dipasang di ringkasan.

**Profil:** header avatar SVG/photo, display name, username unik, bio, social links, publikasi proyek, sertifikat valid, radar kemampuan berdasarkan data assessment. Default privacy konservatif untuk anak. Edit lengkap dari settings, email read-only.

**Pengaturan:** tabs Profile, Security, Notifications, Privacy, Newsletter, Language, Appearance, Accessibility. Password email OTP untuk akun password, recovery via support for email, no direct change email. Onboarding re-opened from help.

## 4. Dashboard admin & instructor

`/admin` dilindungi permission; sidebar grouped:
- Overview (analytics/queue)
- Learning (akademi, course, modules, lessons, learning paths, questions, exams, certificates; Scrim Studio di `Edit Lesson > Scrim`)
- Labs (playground templates, projects, tasks, tests, game, cyber simulation)
- Content (articles, ebook, podcast, video pendek, gallery)
- Community (posts, nested comments, moderation, showcase, reports)
- Programs (bootcamp/cohorts/classroom, mentor, event, hackathon, competition, careers, partner)
- Commerce (products, orders, payments, subscriptions, group checkout, bundles, discounts, merch, preorder, refunds)
- Gamification (XP/Bara rules, ledgers, rewards catalog, leaderboard, certificates)
- Users & Support (accounts, roles, privacy, CS tickets, audit, whistleblowing **separate access**)
- Settings (branding/theme, navbar, announcement bar, locale, mail, integrations, legal, docs)

Instructor mendapat dashboard `/instructor` dengan subset kursus, cohort, lesson, Scrim Studio, assignment, feedback; tidak bisa mengakses pembayaran sensitif/akun lain.

Admin CMS editor `Tambah Materi`: Video YouTube | Scrim | Artikel/Dokumen | File/Link Tool | Lab | Kuis | Tugas; wizard minimal field, drag reorder, preview, publish review. Role access bukan hanya menyembunyikan menu, tetapi server policy.

## 5. UI komponen, design tokens

- Warna wajib:
  - `primary`: `#5B86F6` (Vivid Blue)
  - `primary-strong`: `#2F55D4` (CTA Blue)
  - `butter`: `#FFE7A0` (highlight XP/Bara/rewards)
  - `ink`: `#22263A` (font utama/dark surfaces)
  - `lavender`: `#B9A7F7`
  - `mint`: `#BCECD6`
  - `peach`: `#FFD2BC`
  - `soft-blue`: `#DCE8FF`
- Putih/off-white untuk surface, tint pastel pada area tertentu. Jangan gunakan butter atau pastel sangat muda sebagai teks di atas putih. Test rasio contrast AA.
- Font UI: Plus Jakarta Sans (open-license check), code: JetBrains Mono; fallback system. Icon Lucide. Logo PNG icon referensi sebagai gambar tanpa latar bila ber-alpha; teks brand terpisah di navbar. Buat aset favicon derivative yang terjaga keterbacaan dan lisensi.
- Gaya: playful minimal, rounded 10–16px, cards tidak overdecorated, gentle layered shadow, spacing konsisten, bento section bila berguna, hover transform halus, tidak ada animation overload.
- Skeleton pada content/menus/data, empty/error/retry, optimistic actions hanya setelah route/API mendukung, notifications. Dark mode: Ink background + putih off-white text + primary blue/butter accent.
- Storybook atau internal component gallery hanya jika tidak menambah kebutuhan server daemon produksi.

## 6. Responsive/interaction behavior

- Desktop: horizontal header; dropdown accessible hover/focus/click dengan Escape/click outside; heading yang memiliki submenu dapat diklik dan diatur supaya tidak salah navigasi; jangan hover-only.
- Tablet: layout dua kolom bila cukup, header compact; mega menu tidak melampaui viewport.
- Mobile: hamburger menu drawer, accordion untuk menu, item langsung tetap link; bottom nav opsional **di dashboard saja** 4–5 fungsi, bukan dua nav berkompetisi. Course reader: player full width, modules in drawer, lesson controls reachable thumb.
- Keyboard & assistive tech: skip link, focus trap appropriate, aria-expanded, sensible tab order, semantic buttons, focus-visible; reduced motion; captions/transcripts; accessible alternative for drag-only games; WCAG 2.2 AA target.
- Page analytics tidak menampilkan data kosong sebagai metrik palsu; gunakan genuine DB aggregate, loading dan zero-state.

## 7. UX acceptance

1. Header bisa dipakai sepenuhnya tanpa mouse; mobile drawer berfungsi.
2. Menu Artikel langsung membuka `/artikel`, bukan kategori/penulis submenu.
3. Semua nav target benar-benar resolve ke halaman yang memiliki maksud jelas, tidak ada `href="#"` palsu.
4. Navbar guest/login konsisten, dashboard learner/admin punya sidebar berbeda dan role checks.
5. Course menyatukan Youtube/Scrim/PDF/Quiz/Lab tanpa Scrim nav mandiri.
6. Branding warna dan logo benar pada light/dark dan breakpoint mobile.
7. Skeleton/loading/error/empty, announced focus, aria-label dan klik target memadai.

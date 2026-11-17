# Arsitektur Teknis Lengkap — Laravel + React di Shared Hosting

## 1. Keputusan arsitektur

**Modular monolith** Laravel 13 (PHP >= 8.3) + React/TypeScript + Inertia.js tanpa SSR Node permanen + Vite build + Tailwind + MySQL/MariaDB. Build JS lokal/CI lalu deploy compiled assets. Laravel mengelola authentication, role policies, domain services, migrations, mail, scheduler, logging, file authorization, API controllers. React mengelola user experiences interaktif, Scrim Player/Recorder, Coding Playground, admin dan dashboard. Blade server rendered dapat dipakai untuk artikel dan halaman SEO-kritis agar isi pokok tetap terindeks tanpa runtime SSR Node.

Sistem utama harus berjalan tanpa VPS, Docker, queue daemon permanen, Redis, Postgres, paid cloud, paid AI, atau browser automation server sebagai komponen wajib. External providers butuh kredensial dan bisa punya fee terpisah (Midtrans, SMTP, domain). Jangan mengklaim pengujian integrasi produksi tanpa provider credentials.

## 2. Struktur direktori referensi

```text
app/
  Domain/
    Identity/ Learning/ Scrim/ Labs/ Content/ Community/
    Programs/ Commerce/ Rewards/ Credentials/ Notifications/
    Support/ Analytics/ Platform/
      Actions/ DTOs/ Services/ Events/ Policies/
  Http/Controllers/ Web/Api/Requests/
  Jobs/ Mail/ Notifications/ Policies/ Console/
config/
  terasngulik.php, auth.php, filesystems.php, queue.php, services.php
  navigation.php, permissions.php
resources/
  js/
    app.tsx
    components/ui/ components/navigation/
    layouts/PublicLayout.tsx DashboardLayout.tsx AdminLayout.tsx LessonLayout.tsx
    pages/ Public/ Account/ Dashboard/ Admin/ Instructor/ Lesson/ Labs/
    features/scrim/ features/playground/ features/learning/
    i18n/id.json i18n/en.json
  css/app.css
  views/seo/ views/emails/
routes/web.php api.php console.php
storage/app/private/ public/ backups/ (must not expose secrets)
database/migrations/ factories/ seeders/
tests/Feature/ Unit/ E2E/
public/build/ (compiled via Vite)
docs/ api/ design/ operations/
```

Use conventional Laravel service providers, use PSR-4, dependency injection, typed classes, request validation, policies, transactions, interfaces for external services, database index strategies, and locale helpers. Names are illustrative; don't force if existing repo follows a coherent alternative.

## 3. Modul backend and boundaries

| Domain | Service / boundary | Important invariant |
|---|---|---|
| Identity | Auth, UserProfile, Username, Guardians, Roles | username normalized unique; only verified email for transaction recipients |
| Learning | Academy, Course, Module, Lesson, Enrollment, Progress, Quiz, Exam, Assignments, LearningPaths | completion evaluated server-side; access via entitlement |
| Scrim | ScrimSession, EventStore, Snapshots, Media, LearnerFork | instructor timeline immutable after publish; pause branch separate |
| Labs | Projects, RuntimeDescriptor, Challenge, Submission, Review | no untrusted server execution |
| Content | Article, Ebook, Podcast, Shorts, Gallery, Categories | multi-category; entitlement on private content |
| Community | Post, NestedComment, Moderation, Reports, Showcase | privacy/visibility checked at object level |
| Programs | Bootcamp, Cohort, Classroom, Mentoring, Events, Competitions, Careers, Partners | classroom scoped to cohort |
| Commerce | Catalog, Pricing, Discounts, Orders, GroupCheckout, Subscription, PaymentWebhook, Inventory, Entitlement | idempotent verified payment; one payer N recipients |
| Rewards | XP ledger, Bara immutable ledger, Season, Redemption, Reward Catalog | XP not spendable; Bara atomic debit |
| Credentials | Certificate Issuance, Validation, Social Share | certificate only after eligible completion |
| Support | HelpCenter, CS Tickets, Whistleblowing | privileged case-specific access |
| Platform | Navigation config, Localization, Announcements, Audit, Newsletters, Settings, Analytics | no sensitive data leak |

Core domain events (internal): `UserRegistered`, `EmailVerified`, `CourseEnrolled`, `LessonCompleted`, `QuizPassed`, `AssignmentApproved`, `CourseCompleted`, `CertificateIssued`, `PaymentSettled`, `EntitlementsGranted`, `BaraCredited`, `RewardRedeemed`, `CommentReplied`, `NewsletterSubscribed`, `EventRegistered`, `ScrimPublished`.

Jobs run via database-backed queue & cron. Workers must be **bounded** using `queue:work --stop-when-empty --max-time=...` or `queue:work --once` if hosting permits; no always-running daemon. Provider webhook processing should respond quickly; for critical grants, use durable transactional outbox or reliable sync + retry when queue unavailable.

## 4. Routes, controllers and API strategy

- Public web routes and Inertia/Blade views with CSRF/sessions. Separate prefix `/api/v1` for controlled JSON endpoints with auth/rate limits/scopes; avoid exposing write APIs for private admin unnecessarily. Laravel policies gate all reads/writes.
- Auth: `/register`, `/login`, `/auth/google`, `/auth/google/callback`, `/verify-email`, `/forgot-password`, `/reset-password`, `/account/security/*`.
- Read: `/belajar`, `/belajar/course/{slug}`, `/artikel`, `/komunitas`, `/program`, `/marketplace`, `/labs` as document navigation.
- Private learner: `/dashboard/*`, `/projects/*`, `/quiz/attempts/*`, `/scrim/forks/*`, `/orders/*`, `/checkout/*`.
- Instructor: `/instructor/*`; admin: `/admin/*`; webhook: `/webhooks/midtrans` only POST, signature verification/no CSRF exemption beyond verified endpoint; authenticated static callbacks for OAuth and subscribed mailing list confirmations.
- API docs based on actual routes/OpenAPI source, not imagined public endpoints. Use cursor/page pagination, sort/filters whitelist, request IDs and standard error format. All response shapes typed with TS generated/manual contract.

## 5. Storage and media

- Laravel Storage abstraction with disks `public_assets`, `private_materials`, `scrim_media`, `uploads`, `temp` and test dummy. Start local filesystem inside hosting quota; future S3-compatible optional (e.g. R2) only with configuration; no hard dependency.
- Private materials stored **outside document root** and served through authorized streamed controller with short-lived signed URL if supported; guard path traversal and asset entitlements. Public course covers/avatar validated MIME & dimensions, generated safe filenames, quotas. HTML/script/svg uploads must be sanitized/rejected or processed using trusted pipeline. Generated SVG avatars must be safely rendered (do not inject untrusted raw SVG).
- Scrim media may consist of compressed narration audio + event/chunk JSON and checkpoints. Upload chunking/retry/storage quotas; avoid putting thousands of events individually in one database row where it becomes huge. Evaluate gzip/brotli and object file chunking.
- Video regular via YouTube compliant embed; cannot block YouTube links/logo controls. Premium media may require separate licensed host/provider; clearly mark provider-specific limitations.

## 6. Scrim authoring & playback

Details in `SCRIM_SPEC.md`. A complete Scrim has metadata, editor initial tree, immutable instructor events, versioned checkpoints, optional audio narration, player with seek/resume, learner forks, code runner integration. It must not be implemented as frame OCR or a text snippet displayed on pause. Use monotonic time from browser recording sessions, reconcile audio-clock drift; pause for practice; resume original timeline; save forks separately.

## 7. Playground runtimes and threat model

- HTML/CSS/JS interactive page should run in a **separate-origin sandbox iframe** where possible; if `srcdoc`, set restrictive sandbox attributes, CSP and blocked access to parent. Never set combinations that allow scripts to escape same-origin boundaries. Sanitize project titles/file names and preview links; do not trust postMessage origins or types.
- Python via Pyodide Worker, SQL via SQLite WASM Worker/iframe, R via WebR Worker; loading lazy and cached appropriately. Runtime version pinned; allowlist package imports or show unsupported packages gracefully; memory/time budgets with stop/terminate-worker. Avoid uncontrolled network capabilities; browsers/WASM are not a total security boundary for untrusted code. Maintain appropriate CSP and isolated browsing contexts.
- Free/basic editor/Run always available; Pro for saved capacity, private projects, versions, templates. Runtime separation of frontend view and backend project persistence.
- Cyber Lab server-side hacking/shell execution not safe on low-cost shared hosting; provide realistic safe simulation and defensively-scoped exercises; document infrastructure feature flags and future paid hosting prerequisite without claiming capability exists.
- Private challenge hidden tests can **not be trusted secret if shipped to browser**. Don't claim high-stakes anti-cheat. For grader that cannot safely run code at server, define low-stakes training tests & manual instructor grading. Other backends only if separately provisioned/approved.

## 8. Authentication, roles, notifications

- Laravel email verification, rate limiting, password hashing Argon2id or framework secure default, session rotation, CSRF, signed OAuth state; Google OAuth using official provider credentials only. Password OTP: hashed expiring token, one-time use, 5-10 minute expiry configurable; re-auth before password change and alert email. No end-user email-change endpoint; support workflow with verification and audit.
- Use a permission package open-source or direct gates/policies, permissions scoped to organization/cohort/course. Never rely on frontend hiding.
- Newsletter double opt-in preferred; initial consent check box unchecked, marketing preference separate from transactional. Batch send queue and unsubscribe suppression; avoid dumping user list into provider without legal basis.
- Telegram Bot official optional; WhatsApp Business only official opt-in and approved template. Web notification in DB baseline, email optional. No provider token client-side.

## 9. Commerce/payment architecture

- Order state machine: `draft`, `pending`, `paid`, `expired`, `failed`, `cancelled`, `refunded`, `partially_refunded` where relevant. Line items snapshot prices/discount/tax at checkout; independent from mutable product catalog.
- Midtrans Snap Embedded with backend-generated token; token/merchant private keys on server; webhook verified via signature + status/provider verification, deduplicated by provider event reference or state transition. Return status UX from own backend, never solely client callback.
- Group checkout: hold N recipient account IDs and typed verified email snapshot, single `order` and payment, atomic grant to N recipient accounts on verified paid, idempotent `entitlements` unique index. Reversal/refund policies explicit and logged.
- Subscription: record external provider transaction plan/cycle and payment status, cron expiration and entitlement recompute, admin grants separate. No automatic card recurring charges without explicitly supported provider mode/legal disclosure; can implement manual invoice renewal as fallback.
- Reward redemption and inventory transactional, protect against concurrent double-spend, cancel/refund reversals ledgered. Bara not legal tender; internal reward credit no cash-out.

## 10. Caching, search, performance, accessibility

- MySQL fulltext/search or Laravel Scout with database driver; indexed filters by academy/category/status/popularity/date, pagination. No ElasticSearch/Algolia requirement.
- Laravel `file` or `database` cache; session DB/file appropriate, never Redis dependency. Database queue with cron; no WebSocket requirement: use polling/SSE only if hosting limits allow (default polling backoff for notifications).
- Public article SEO rendered server-side; metadata OG and hreflang; structured data only for truthful published content. Sitemap and robots.
- Defer large JS modules: Monaco, Pyodide, WebR loaded only on relevant pages; optimize LCP and mobile. Image compression and dimensions, lazy loading, skeleton & error states, WCAG 2.2 AA target.

## 11. Privacy and security baseline

- Session/cookie Secure/HttpOnly/SameSite, strict input validation, spam protection, moderation, ownership checks, signed routes, upload filtering, CSP, clickjacking protections, CSRF and rate limits. Email enumeration controls, verification OTP invalidation, audit trail.
- Data privacy of minors: default private, guard parental consent, segregated social features, deletion/retention policy and incident response. Legal checks and controls required before enabling children public community or targeted outreach.
- Sensitive whistleblowing encrypted at rest where supported with strongly limited access, audit logs redact payload. Credentials/PII and payment tokens never logged unredacted.

## 12. External integration adapters

Define interfaces `PaymentGateway`, `TransactionalMailer`, `OAuthIdentityProvider`, `CommunityBroadcaster`, `CredentialShareProvider`, `MediaProvider`, and implementations `MidtransSandbox`, `SMTP`, `Google`, `Telegram`, `LinkedInShareUrl`, `YouTubeEmbed`. Provide local fake/test adapters ONLY in tests/dev with explicit UI label. Never silently switch fake adapter to production.

## 13. Deployment limitations

- PHP extension requirements, MySQL/MariaDB, writable `storage`, `bootstrap/cache`, cron, HTTPS, correct `public/` document root, app key/env secure. Check actual hosting requirements.
- Runtime browser cost depends on learner device; memory heavy WASM may not work on low-end phones: detect/recommend desktop or fallback exercise.
- Avoid large campaign email worker on small hosting; throttle queues and monitor sending and bounce handling; use external SMTP when available, possible charges beyond free quotas.
- Backup DB/files, restore proof, migration rollback strategy; observer cron for payments/subscriptions (idempotent), security updates.

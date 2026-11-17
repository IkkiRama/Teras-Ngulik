# Deployment & Operations — Shared/Unlimited Hosting Murah

## 1. Target

Satu **shared hosting PHP + MySQL/MariaDB** murah, tanpa VPS/Docker/Redis/Node daemon permanen. Semua perangkat lunak platform diupayakan gratis/open-source dan legal dipakai secara komersial. Paket hosting, domain, payment provider, SMTP atau limit penyimpanan bisa menimbulkan biaya nyata. Jangan mengasumsikan kata "unlimited" berarti sumber daya tak terbatas.

**Kompatibilitas minimum:** PHP >= 8.3 untuk Laravel 13, PHP extensions Laravel, MySQL/MariaDB dengan ukuran sesuai kebutuhan, HTTPS, `mod_rewrite` atau routing server yang kompatibel, document root bisa diarahkan ke `/public`, file manager/SSH/FTP, scheduled cron, `storage` writable dan quota inode/CPU/memory masuk akal. Jika provider tak memenuhi syarat keamanan (terutama webroot), **jangan deploy secara tidak aman**; pilih paket/provider yang sesuai.

**Dokumentasi resmi relevan (cek sebelum deploy):**
- Laravel 13 release notes: https://laravel.com/framework/docs/releases
- Laravel deployment requirements: https://laravel.com/framework/docs/deployment
- Laravel installation: https://laravel.com/framework/docs/installation
- Midtrans embedded Snap: https://docs.midtrans.com/docs/snap-snap-integration-guide
- YouTube policy embed: https://developers.google.com/youtube/terms/developer-policies-guide

## 2. Build artifacts dan layout hosting

Local dev/CI: PHP/Composer, Node npm, MySQL. Build `composer install --no-dev --optimize-autoloader`, `npm ci`, `npm run build`, `php artisan test`, `php artisan config:cache` **pada environment deployment yang tepat**. Don't cache config before production `.env` is available. Upload code + vendor + built `public/build`, **exclude node_modules and local secrets**.

In production server: document root must be Laravel `public/`. `app/`, `.env`, `storage/` private files and `vendor/` must be inaccessible from public HTTP. Prefer hosting-supported docroot setting rather than moving private files under `public_html`. If only `public_html` available and can't create safe outside-root symlinks/config, document impossibility rather than expose `.env`.

Generic release:
1. Check PHP version/extensions/database access/cron + storage quotas.
2. Create database user with least privilege and encrypted/safe stored production credentials.
3. Set `.env` with APP_ENV=production, APP_DEBUG=false, APP_URL=https, APP_KEY, DB creds, MAIL_*, MIDTRANS_* sandbox/production, GOOGLE_* and storage settings as available.
4. Verify `storage/` and `bootstrap/cache/` writable; create private disks not symlinked to public webroot.
5. Apply migrations with approved backup, check seeder/fixtures not accidentally injecting demo accounts or fake paid orders.
6. Deploy compiled public assets, run Laravel optimize/caches carefully, test HTTPS session/auth and CSRF.
7. Configure cron Laravel scheduler if supported (e.g. once per minute) and bounded queue draining; mail newsletter batches/notifications and payment reconciliation must be retry-safe.
8. Test actual OAuth authorized redirect domain, verify emails, test Midtrans sandbox webhook from publicly reachable HTTPS callback using provider's verified flow.
9. Enable security headers, backups, uptime/failed job monitoring; document rollback.

## 3. Cron & background work

Use Laravel scheduler with hosting cron and bounded DB queue workers, but **validate allowed commands and cron frequency** before relying on it. Jobs should be idempotent, retries bounded and dead-letter/failed job visibility. Expiring subscriptions, reconciling orders, review reminders, newsletter and certificate emails can run using schedulers. Avoid synchronous bulk newsletter work per HTTP request.

Do not promise instant realtime notifications without WebSocket infrastructure; polling while page active and scheduled email digest meets baseline goals. If provider cron unavailable, document the limits and offer non-deceptive fallback until host is upgraded.

## 4. Database, files, and backups

- MySQL/MariaDB included in host; use transaction indexes, pagination and periodic cleanup of derived caches; purge events only according to published privacy and credential retention policy.
- Local Storage for permitted small uploads; scrub MIME, max sizes, file type, quota, path authorization. Upload media to private disk if premium. YouTube only for ordinary compliant video embed; scrim chunks/audio may be stored in hosting within caps.
- **Cloudflare R2 is optional** future object storage (not prerequisite), never required for first deploy. Storage abstraction should allow moving later.
- Backup DB and private files regularly, encrypt copies, test restore, set retention. Backup/restore plan includes certs, orders, XP/Bara ledger, scrim forks.
- Media size and outbound bandwidth may become limiting before user count; monitor total file usage, inodes, storage latency and error rate.

## 5. Security and third-party integrations

- SMTP/deliverability requires proper DNS records (SPF, DKIM, DMARC as supported) and rate limits, never auto spam users. Verification/OTP flows use email queue; test on production domain before public registration.
- Google OAuth authorized domains/redirect URIs tied to environment; secrets stored in `.env`, callbacks require state validation.
- Midtrans **sandbox** for staging, server-to-server webhook verified, production enabled only after merchant account/callback/terms configured. The embedded UI is Midtrans-owned; custom styling around it, not spoofed controls/credentials.
- LinkedIn: use verifiable certificate URL and user-initiated Add to Profile/manual entry; official write API approval not assured. Telegram official Bot optional, WhatsApp Business official optional subject to cost/eligibility.
- YouTube no pointer-blocking overlay over player, no removing attribution, related videos, or native links to avoid platform policy violations.

## 6. Performance and resource budget

- Run JS/WASM code mostly on **user device**, not on shared hosting; Monaco, Pyodide, WebR lazy-import. WASM memory may fail on cheap phones. Provide feature detection and fallbacks.
- Do not serve self-hosted video libraries of enormous size without bandwidth/cost assessment. Scrims use compressed code events/audio with finite quotas.
- Use Cloudflare free CDN optional if helpful, but origin and app must still function without it.
- HTTP cache headers for static assets, hashed Vite asset names, image optimization & sizes, SQL query caching within available file/DB cache drivers.
- Explicit quotas in admin: upload size, number of saved projects by plan, max Scrim duration, file count/bytes, rate-limits.

## 7. Missing infrastructure / feature gating

Features that **can** be fully realized on shared host with browser execution: CMS, course, media metadata, article, forum, ecommerce/payment sandbox, customer support, profile, scrim authoring/playback with reasonable file sizes, labs with WASM, simple games, newsletters within email quotas, scheduling via cron, verifiable certificates.

Features that **cannot** be promised on one cheap shared host with no auxiliary infrastructure: arbitrary multi-language server judge with truly secret test cases, arbitrary Linux containers/real pentest VMs, livestream video transcoding/CDN at scale, always-on realtime collaborative WebSockets, unrestricted large AI tutor and high-volume bulk outbound email. For these, implement honest safe alternatives or label `INFRA_BLOCKED` with deployment plan; don't omit them from full-scope spec.

## 8. User-facing documentation to ship with code

- `docs/SETUP_LOCAL.md` with env placeholders and commands.
- `docs/DEPLOY_SHARED_HOSTING.md` with provider checklist and safe docroot.
- `docs/INTEGRATIONS.md` for SMTP, Google, Midtrans sandbox/live, Telegram, WhatsApp limitations, LinkedIn credential URL.
- `docs/BACKUP_RESTORE.md`, `docs/SECURITY.md`, `docs/ARCHITECTURE.md`, `docs/API.md`, `docs/DESIGN_SYSTEM.md`.
- `IMPLEMENTATION_STATUS.md` and `DECISIONS.md` accurate across Codex sessions.

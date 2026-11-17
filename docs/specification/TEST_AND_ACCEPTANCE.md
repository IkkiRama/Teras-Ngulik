# Quality Assurance, Acceptance & Release Gate — FULL Scope

**Definition of Done (DoD) per feature:** migrations + authorized server action + usable mobile/desktop UI + validation + meaningful success/error/empty states + automated tests or recorded manual tests + docs + no fake links/control. A static landing mock is NOT a completed feature. External service integration remains `NEEDS_CONFIGURATION` until tested with configured sandbox credentials.

## A. Foundation / Navigation / Branding

- [ ] `composer install`, `php artisan migrate`, `npm install`, `npm run build` run from clean setup, `.env.example` included, seeded dev only.
- [ ] Guest navbar shows Belajar, Labs, Komunitas, Program, Marketplace, Tentang; announcement bar above header, search, Masuk/Daftar.
- [ ] User logged in sees same base navbar + avatar/notif/XP/Bara; learner dashboard sidebar and admin sidebar are protected by role.
- [ ] `Komunitas > Artikel` direct link resolves `/artikel`; filtering categories/author is on that page, no redundant submenu levels.
- [ ] Desktop menu click/focus/hover and Escape; mobile hamburger/accordion, no overflow, 320px layouts.
- [ ] Logo icon is separated from wordmark text; primary/blues/butter/ink tokens correct light/dark.
- [ ] SEO title/meta/open graph on article/course, static fallback hero, loading skeleton, skip-to-content and keyboard focus.

## B. Authentication/Profile/Privacy

- [ ] Email signup, verify, login, reset, logout, resend verification with TTL/rate-limit, session protection.
- [ ] Google OAuth sandbox/dev configuration docs, stable link to identity, state validation; no local password assumed for OAuth-only.
- [ ] Unique case-insensitive username and change behavior, private email, edit display name/avatar/links/bio/theme/language; SVG avatar safe.
- [ ] No self-service email change via UI/API, safe support recovery workflow with audit.
- [ ] Change password needs recent verification/OTP, wrong/expired code blocked, brute force limited, session invalidation.
- [ ] Newsletter default opt-out and unsubscribe, transactional emails separate; consent stored/audited.
- [ ] Child private profile default, age-appropriate community restrictions and guardian flow; verify authorization with child accounts fixtures.

## C. Academy, Course, Quiz, Progress

- [ ] Five academies and their curriculum taxonomies; instructor/admin author course/modul/lesson.
- [ ] Course supports YouTube, Scrim, article/document, file/link resource, lab, quiz, assignment in arbitrary ordered mix.
- [ ] Lesson ordering/publish/draft/review, entitlement on paid content/downloads, no iframe YouTube overlays that block links/branding.
- [ ] Progress Resume remembers lesson/time/scroll and last enrolled course across sessions; completion rules server-validated.
- [ ] Quiz standalone vs course quiz separated; variable A-D/A-E/custom options, single/multiple answers, score/attempts/explanation, time limit and retry behavior.
- [ ] Final project file/link submission, rubric instructor review, pass/fail and course completion criteria; certificate only when passed.
- [ ] Learning Path includes course ordering/prerequisites/completion and progress dashboard.
- [ ] Diagnostic recommends level/path/academy; does not lock beginner adults into child interface.
- [ ] Spaced repetition due queue, Lupa/Sulit/Ingat/Mudah, scheduling and review history, disable reminders.
- [ ] Notebook/bookmark save/restore, private ownership, notes tied to timestamp and code snippet.

## D. Scrim Recorder and Interactive Player

- [ ] Instructor captures editor events + audio optional, chunks/retries, finalizes, previews, publishes immutable revision within Course lesson editor.
- [ ] Playback code matches recording; file switching/unicode edits/selection/seek/checkpoint deterministic.
- [ ] At paused timestamp code is **actual editable/copyable editor content**, can branch and run supported runtime.
- [ ] Resume returns to **original instructor timeline** not learner fork; save/open learner version optional.
- [ ] Refresh reopens authorized saved fork; unauthorized/premium asset requests blocked; audio drift and caption settings handled.
- [ ] Malformed/canceled upload cannot corrupt published recording; version updates preserve existing forks.

## E. Playground / Labs / Games

- [ ] Free HTML/CSS/JS can run in safe origin-isolated iframe/Worker; Python Pyodide and SQLite WASM supported with graceful unsupported fallback; R WebR feature detected & gracefully fallback.
- [ ] Basic Run free; copy/download and save limits; Pro upgrades quota/history/projects without locking core execution.
- [ ] User code cannot access parent secrets/cookies or trigger server code execution; file size, CPU/memory/time/rate limits tested; stop button works.
- [ ] Lab task submission and tests, code review comments/rubric, public/hidden tests policy honest about browser visibility.
- [ ] Educational games navigable via keyboard or alternative controls; gamification awards not tied to low-effort game play.
- [ ] Cyber simulation strictly sandboxed browser; no live exploitable shared hosting targets; Stata lessons only dataset/do-file unless licensed runtime available.

## F. Articles/Community/Media/Programs

- [ ] Article many categories, by-author filters **inside** `/artikel`, drafts/publish, SEO, share, comments nested, bookmark, premium gate.
- [ ] Comments can reply/edit/delete/report, nested pagination not overflow mobile; moderation and spam controls.
- [ ] Community post/feed, project showcase, portfolio link, media attachments authorized, children restrictions.
- [ ] Ebook download/reader, podcast audio/transcript, shorts, gallery; public/premium access; safe media handling.
- [ ] Bootcamp cohorts with classroom materials/assignments/schedule/attendance/grades/instructor role.
- [ ] Event free/paid registration, tickets; mentorship availability and booking; hackathon team/submission/judging/leaderboard; career jobs and partner contact.
- [ ] Telegram broadcast only when configured (real provider); WhatsApp official absent stays optional unavailable, not fake messaging.

## G. Commerce and Critical Money Flows

- [ ] Order created with price snapshot, discount/voucher valid, tax/fee and IDR integer math; user sees exact payable total.
- [ ] Midtrans Snap **Embedded** sandbox when configured. Clicking embedded payment doesn't grant access until server validates webhook.
- [ ] Webhook signature/provider verification, idempotent duplicate handling, out-of-order states, expiration/failure/refund, no secret logs.
- [ ] Single purchase grants entitlement; subscription only active for paid term; bundle grants each included item; free enrolment works.
- [ ] Group purchase ONE payer with N **existing verified user** emails, invalid email requires replacement before checkout; no invitation/individual payments.
- [ ] Paid group order atomically grants all N entitlements; duplicate webhook/no payment does not; race/repeated group purchases don't duplicate grants.
- [ ] Merch order/preorder stock/address/fulfilment visible, no over-selling or unfunded ship promises; admin can monitor/refund/reconcile.
- [ ] Announcement bar points to real valid promos; quotas/usage/stacking policies enforced server-side; no fake deadline.

## H. Rewards, certificates, leaderboard

- [ ] XP ledger incremental, cannot be spent; weekly/monthly/academy/age group leaderboard calculates verified achievements.
- [ ] Bara immutable credit/debit ledger and balance; redeemed Bara for eligible item works atomically; concurrent double redemption blocked.
- [ ] Credential issued only after verified completion; HTML certificate accessible to owner, PNG/JPG export, public verification route with minimal PII, revocation supported.
- [ ] LinkedIn share copies title/issuer/ID/URL, opens appropriate certificate flow; no fake write permission.
- [ ] Portfolio builder/project showcase/skill radar based on assessed skill evidence and privacy defaults.

## I. Email/Support/Admin/Legal

- [ ] SMTP configured supports verification/OTP/receipts with retry queue and real delivery test; marketing opt-in + unsubscribe tested.
- [ ] CS ticket state workflow + role-scoped reply; Help/FAQ pages, contact.
- [ ] Whistleblowing restricted and audited, anonymous mode safe if provided; cases not visible to ordinary support/community mods.
- [ ] Admin manages all modules with role checks, CMS approval, payments/refund logs, user moderation, announcement, email campaign, settings & API docs.
- [ ] UI strings ID/EN with fallback, localized content if published; no broken dynamic translation and a documented translation review workflow.
- [ ] Privacy, Terms, Refund, community rules, accessibility and consent pages available; data export/deletion flow respects financial retention.

## J. Nonfunctional / Release

- [ ] Test security: CSRF, XSS sanitized rich text, IDOR on resources, session security, throttling of email/group recipient lookup, webhook replay, cross-tenant classroom, file path traversal, malicious uploads, code sandbox escape assumptions.
- [ ] Performance: no heavyweight Monaco/WebR/Pyodide on homepage, critical content LCP, images optimized, pagination/indexes, graceful WASM limits on mobile.
- [ ] Accessible: contrast, alt text, captions, keyboard menus/dialogs, tab order, reduced motion, status announcements, screen reader descriptions.
- [ ] Shared-hosting deploy: PHP >=8.3, MySQL, correct docroot, app key, `.env` hidden, migrations, file permissions, cron/queue, `public/build` uploaded, HTTPS, backup/restore validated.
- [ ] `IMPLEMENTATION_STATUS.md` reports exact commands/results, known limitations, configuration gaps, pending infrastructure; `docs/` mirrors real code.

## Required automated test families

1. IdentityFeatureTest, PasswordReauthTest, NewsletterConsentTest, GuardianPolicyTest.
2. PublicNavigationTest, RouteAndSEOTest, ArticleCategoryFilterTest, LearnerAdminRoleTest.
3. CourseMixedLessonTest, EnrollmentEntitlementTest, LearningProgressTest, QuizScoringTest, CertificateEligibilityTest.
4. ScrimRecordReplayTest, ScrimPauseForkResumeTest, ScrimSeekDeterministicTest, ScrimAccessControlTest.
5. BrowserRuntimeIsolationE2E, PlaygroundFreeProQuotaTest, LabSubmissionTest, CodeReviewAuthorizationTest.
6. PaymentWebhookIdempotencyTest, GroupCheckoutOnePayerTest, GroupCheckoutAtomicGrantTest, SubscriptionExpiryTest, DiscountRulesTest, MerchandiseStockRaceTest.
7. XPLedgerTest, BaraLedgerDoubleSpendTest, RewardRedemptionTest, LeaderboardPeriodTest.
8. NestedCommentTest, ContentModerationTest, SupportCaseScopeTest, WhistleblowerPrivacyTest.
9. PWA/mobile smoke tests & Playwright E2E: guest browse, registration, free course, interactive Scrim, playground, checkout sandbox, dashboard, article share.

**Release standard:** critically important flows cannot be `DONE` with only unit tests that mock the entire business process. At least one integration/feature/E2E route for each critical path must be executed. Where external credentials absent, list real blocked integration and pass deterministic sandbox/mock tests without suggesting production verified.

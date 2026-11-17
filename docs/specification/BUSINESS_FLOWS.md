# Business Processes & Product Rules — End-to-End

## 1. Sign up, verify, Google OAuth, username

**Email registration**: learner -> form email, password, username, age band (or guardian flow) -> server uniqueness validation and password hash -> unverified account with allowed minimal actions -> send verification link (if SMTP configured) -> verify -> onboarding. Prevent enumeration from generic password reset/verification errors and throttle resend. Do not grant child community permissions before required guardian consent.

**Google login**: user -> OAuth provider consent -> backend validates state/nonce/provider-sub identity -> link existing account only after secure matching/proof -> create verified identity -> request username/profile fields if incomplete -> optional guardian flow. OAuth-only users do not have an assumed local password.

**Password change**: logged-in password user -> request recent email OTP -> rate-limited send -> hashed code verified and single use -> confirm new password with validation -> invalidate relevant sessions/tokens -> security notification. **Email change unavailable self-service**, support recovery documented with proof & audit; hidden controls are not a server authorization mechanism.

**Profile edit**: verified account -> settings -> display name, username, avatar/photo/generated SVG, bio, interests, social URLs, language/theme -> validator -> update -> reflect in public handle and private dashboard. Username unique case-insensitive; redirect old slug if changed. Do not expose email in public.

**Newsletter**: signup may receive **transactional welcome**. Marketing newsletter only with affirmative checkbox (default off), record consent/policy version, subscription preference; unsubscribe always available, suppression persist. Existing user can change topics/frequency. Do not silently auto-enroll promotional mail.

## 2. Course discovery to enrollment

Guest browses academy/course/learning path -> search/filter/preview -> chooses course:
- free: if guest, login/register; auto enroll after verified login if policy; if logged in, one-click enroll.
- single purchase: checkout, verified payment, entitlement `purchase` -> enroll.
- subscription: plan purchase -> active cycle/status -> subscription entitlement; access expires under rule, purchased course remains available.
- bundle: verified purchase -> entitlement to all included courses, with snapshot terms and validity.
- bootcamp: verified cohort enrollment and membership -> access to assigned classroom/course materials.

Access checker is server-side policy, one unified result that can explain `free`, `requires_login`, `requires_subscription`, `requires_purchase`, `cohort_only`, `expired`, `blocked`.

## 3. Lesson -> quiz/lab/final -> course pass

User opens module -> chooses lesson kind (YouTube/Scrim/rich text/download/lab/quiz/assignment) -> progress resume from last state. Document/video completion according to configured completion criteria; lab tests and quiz pass criteria; final task rubric grade/mentor review as needed. Server evaluates course completion **atomically** from all required evidence; emits `CourseCompleted` at most once for completion version; award XP and Bara via idempotent rules; issue signed/verified HTML certificate record. User can export PNG/JPG and share credential URL to LinkedIn. Course reset/update must not silently revoke already earned award; versioning and clear policy.

**Kuis mandiri** accessible outside course, separate attempt/score tracking and configurable point rules; may not trigger certificate automatically.

## 4. Scrim workflow (see SCRIM_SPEC.md)

Instructor chooses `Course > Lesson > Scrim` -> record code events + narration -> preview and publish revision; learner opens lesson -> play -> pause -> edit code directly -> copy/run/save fork -> resume original -> progress saved. Do not mistake regular YouTube video for Scrim, and do not claim OCR pixel extraction as functional equivalent.

## 5. Coding Playground free/pro

Guest opens Playground -> selects language -> code/edit/run via safe client WASM/iframe -> can copy/download/export. Logged-in free learner may save limited project quantity (admin configurable) & resume. Pro can save more, maintain versions/multi-file if runtime supported. Upgrade changes quotas and permissions but never blocks basic Run. Browser low resources -> explicit warning/fallback; no remote server code execution.

## 6. Group purchase/patungan — ONE payer (critical)

1. Payer logged in chooses eligible course/group package and N seats.
2. Enter **N recipient account email addresses**; payer may include their own account if desired.
3. Backend normalization, dedupe, verifies **all email belong to registered verified accounts**, checks each recipient's existing entitlement/seat validity, maximum seats, purchase eligibility. UI shows per-row valid/invalid and says to **replace email** if account not found. Do NOT automatically create accounts, invite unregistered addresses, or ask each recipient to pay.
4. Pricing engine calculates **single** order subtotal/discount/tax/fees, displays all recipients, confirms refund/terms. Hold order snapshot and recipient IDs; validate again at commit to avoid race.
5. Backend creates Midtrans token; embedded checkout displays once to payer, not recipients. No buyer-side client callback by itself activates courses.
6. Trusted webhook verifies signature/provider status -> idempotent transaction transitions `paid` -> entitlements granted **for all recipients atomically** -> enrollments -> notifications to recipients and invoice to payer.
7. Duplicate webhook no duplicate grant/XP; expired/failed no grants; refund follows disclosed access reversal policy; manual reconciliation available.
8. Checkout recipient lookup is protected and rate limited, minimizing account enumeration. No recipient emails displayed publicly; receipts show minimal sensitive data.

Example: course Rp100.000/seat with group rate Rp80.000, payer enters 3 existing users -> **one checkout Rp240.000**, not 3 invoices. Any email not found = buyer must replace before payment.

## 7. Normal checkout + subscription + promo + preorder

- Product selected -> cart/pricing snapshot -> apply voucher rules -> email/contact/shipping if merchandise -> order -> Midtrans embed token -> pending -> verified webhook -> fulfilment (entitlement/ticket/shipping).
- UI payment states: waiting, pending, failed/expired, paid, refund requested/processed; query backend for final, don't trust browser success.
- Subscription: create payment cycle, grant term entitlement after paid, schedule expiry/reminders by cron; cancel according plan; renew flow manual invoice if no officially supported recurring API. Buyer sees terms and costs transparently.
- Merch preorder: display realistic availability/estimated fulfilment, variant/size/stock, one payer, shipping address; don't collect payment if no delivery/returns process. Stock reserved/committed atomically.
- Admin discount banner reflects actual active promotion, one at a time; redeem and checkout consistent. Don't show fake countdown/stock.

## 8. XP versus Bara, redeemed rewards

Server rules (versioned/configurable) trigger after **verified** completion of course, final project, selected hackathon/challenge, limited weekly check-in. XP credits permanent reputation ledger, seasonal leaderboard computed within time window. Bara separate redeemable wallet ledger. Badge for achievement thresholds only; certificate for fulfilled course requirements only. No XP farming from repeatedly opening a game or replaying lesson.

**Bara redemption:** user chooses eligible reward -> backend checks account, Bara balance and inventory -> DB transaction locks resources -> append debit ledger and reward redemption/order -> success confirmation. Concurrent requests cannot redeem with same Bara twice. Failed fulfilment/refund handled by compensating **credit entry**, not deleting debit; only admin with permission can adjust and must audit reason.

## 9. Community/article/media

Admin publishes article with 1..many categories, tags, author, cover; index `/artikel` filters by category/author/tags internally. Viewer reads, bookmarks, shares via native share or links, comments in nested thread. Owner edits/deletes own comment within policy; moderator responds to reported content. Subscribers view gated article/podcast/ebook/short if entitled. Social notification for replies/mentions; Telegram channel alerts optional official bot. No child public profile/DM exposure by default.

## 10. Mentorship, bootcamp classroom, event, competition

- Bootcamp listing -> cohort -> seats -> purchase/free admission -> cohort membership -> Classroom tabs material/docs/schedule/attendance/assignments/discussion/evaluations, instructor feedback & announcements. Classroom **inside Bootcamp**.
- Mentorship: learner selects mentor -> available time slot -> booking request -> optional payment -> confirmation + link -> complete/feedback; prevent double booking, cancellation/refund.
- Events: register free or paid -> ticket/QR/confirmation -> notification and check-in. Hackathon: team creation/registration, rules, deadline, project submission, judge scores and leaderboard with audited changes and appeal.
- Partner form receives proposal and attachments, stores protected review ticket; careers shows verified roles and outbound apply or internal application as configured.

## 11. Support, moderation, whistleblowing

- Support ticket -> ownership auth -> priority/category -> staff assignment -> replies/attachments -> close/reopen. Kb/FAQ first, but not mandatory.
- Report community content -> moderator review -> policy-specific action (warn, hide, delete, suspend) -> appeal/history, avoid harassment and doxxing.
- Whistleblowing uses separate restricted ACL, minimize PII, optional anonymous case token, audit who accessed; notifications redact content; ensure retention & security, no public feed crossover.

## 12. Multilanguage, share, docs

- Interface strings via ID/EN locale keys; locale selection persisted. Contents (course/article) may have translation records, publish only reviewed variants. No paid translator API dependency; editor can add translations manually.
- Article share URL OG metadata correct; certificate LinkedIn sharing opens appropriate flow or copies credential details, not falsely promises automatic addition without official privileges.
- API docs generated from actual endpoints; design docs reflect actual component tokens/styles. Always have Contact, Terms, Privacy, Accessibility, Help and Refund pages accessible.

## 13. Admin content management / approval workflow

Create/update content as draft -> preview -> submit for review -> reviewer accepts/requests revision -> publish/schedule -> archive. Instructor can edit own course, reviewer approves; admin can oversee all. Content changes versioned and existing learner progress/certificates protected. Moderation actions require reason and audit, financial changes require authorization & ledger trail.

## 14. Notifications and error handling

Every action returns clear Indonesian error/action status. Email provider down -> retry queue + informative status, avoid granting access twice. Unauthorized -> 403, expired link -> resend option, unregistered group recipient -> replace email, out-of-stock -> reprice/reselect, unsupported WASM -> allow editor/download with fallback, rate limit -> safe retry instructions. No fake payment confirmation, fake email delivered, or test scores where engine is absent.

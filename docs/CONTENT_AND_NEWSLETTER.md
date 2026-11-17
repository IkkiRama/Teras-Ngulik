# Content, community, programs, and private materials

The local catalog is explicitly demo data. Seeded events, mentor slots, job applications, and bootcamps do not represent real schedules, employers, or staff availability. A podcast or short without uploaded media presents its text/transcript without a fake player. Legal documents are labeled drafts requiring review before production.

## Working flows

- `/artikel`: published/scheduled content, category and author filtering, search, pagination, direct detail links. The same CMS supports ebooks, podcasts, shorts, galleries, discussions, showcases, help, legal policies, events, bootcamps, mentors, competitions, careers, and partner information.
- `/komunitas/diskusi` and `/komunitas/showcase`: verified adult accounts can publish text and an optional HTTP(S) project link. Comments have up to six levels; parents must belong to the same item. Owners can remove comments; admins can moderate. Child accounts cannot publish to the public community. Reply notifications are private to the recipient.
- Program registration uses a database transaction and item row lock. Duplicate registration is idempotent; capacity and mentor slot conflicts are checked. Paid programs require a matching content entitlement. Cancellations release a mentor slot. Bootcamp classroom metadata is absent from responses until active registration. Bootcamp/competition/career text or URL submissions can receive manual admin feedback and a score.
- `/kontak`: private tickets and conversations. Body and conversation are encrypted with the Laravel application key. `/whistleblowing` stores a separate encrypted report, optionally without an account link. `/admin/whistleblowing` is separate from ordinary support and every case opening is audited. Anonymous reports receive a reference; anonymous public tracking is not implemented.
- `/admin/content`: real creation, editing, draft/review/published/archive state, scheduled publication, categories, metadata, access policies. Archiving preserves program history. Text is displayed as text; arbitrary HTML is not executed.
- Content attachments are stored using the private local disk, outside `public/`. The download controller checks the content parent, publication state, and entitlement on every request. Attachment paths are hidden from JSON. MIME/extension validation rejects HTML/SVG/code uploads; download responses use attachment disposition and `nosniff`. Admins can upload/delete PDF, text, CSV, ZIP, Office files, and raster images; maximum 20 per item and 10 MB per file by default.
- `/cari`: published course summaries and public/registered content available to the current account. `/dashboard/bootcamp` lists only the current user's registrations. `/dashboard/notifikasi` and its mark-read action are ownership-scoped.
- `/api/v1/content`: public published summaries only, read-only, pagination, kind and text filters, 60 requests/minute. No private file paths or full premium bodies. Actual API usage and design tokens are presented at `/dokumentasi`.

## Newsletter consent and batching

`POST /newsletter` requires an explicit consent field. A guest email remains `pending` until the mailbox owner confirms the token at `/newsletter/confirm/{token}`. GET previews the confirmation and POST activates it; the token expires after 24 hours and is consumed once. A verified account confirming its own email can subscribe immediately. Ordinary account email verification may also activate a previously consented subscription through the identity domain. Unsubscribing uses a stable random token: GET displays a confirmation and POST changes the state. Unsubscribe tokens are encrypted in storage and hashes are used for lookup. Account preferences offer a second way to opt out.

`/admin/newsletter` supports draft text, subject, optional earliest-send time (UTC), review/approval, recipient queue creation, cancellation, delivery counts, and explicit retries for failed deliveries. Preparing a queue does not send mail. Only subscribers with recorded consent are included; pending/unsubscribed recipients are excluded. Each batch rechecks consent so an opt-out after queue creation is suppressed.

Configuration defaults:

```dotenv
NEWSLETTER_SENDING_ENABLED=false
NEWSLETTER_BATCH_SIZE=25
MATERIAL_MAX_UPLOAD_KB=10240
```

PHP `upload_max_filesize` and `post_max_size` must also permit the intended upload size. Keep the local storage disk outside the webroot. The app key is required to decrypt tickets and unsubscribe tokens; preserve it securely with backups.

```powershell
php artisan newsletter:send --dry-run --limit=25
```

This command counts eligible delivery records without sending mail or changing records. Real sending requires a configured mail transport and the explicit sending flag. The sender refuses `log` and `array` transports as real delivery. Each invocation processes at most 100 records, with a default of 25, then exits, making it suitable for bounded shared-hosting cron jobs:

```text
* * * * * /path/to/php /path/to/app/artisan newsletter:send --limit=25
```

Use that cron only after configuring the mail provider and approving a campaign. No production newsletter was sent during implementation. Local transactional/confirmation emails use the development mail transport; with `MAIL_MAILER=log`, inspect the local development log to test confirmation links.

Delivery states are `queued`, `sending`, `sent`, `suppressed`, `failed`, or `cancelled`. A claimed `sending` record is not automatically retried after a process crash, because a crash after provider acceptance could otherwise duplicate mail. Operators must reconcile such records with provider logs. Failed deliveries require an explicit retry action; this remains at-least-once provider delivery without a provider-specific idempotency API. Logs contain delivery IDs and failure types, not campaign text or recipient addresses.

## Verification

`ContentTest` exercises paid-body/media privacy, future publication, cross-item comment rejection, verification/child gates, notifications, ownership, registration idempotency/capacity/cancellation, paid entitlements, slot conflicts, classroom privacy, encrypted tickets, anonymous whistleblowing/audit, CMS roles/archive history, consent/confirmation/unsubscribe, search/filter permission, private download gates, upload MIME rejection, campaign approval, dry-run, batch bounds, opt-out suppression, and replay safety. Files and email are replaced by Laravel test doubles in upload/sender tests.

## Scope still needing development or external configuration

- Multi-cohort bootcamp administration, attendance, classroom calendar/deadlines, rubric detail, team competitions, appeals, job matching, and automated bootcamp certificates.
- Rich text editing, content revision history, large media chunk upload, transcription/caption tooling, image processing, antivirus scanning, and external media storage.
- Fine-grained support/editor/moderator/mentor/partner permissions and guardian consent. Current privileged content operations use the single admin role; child public posting stays blocked.
- Provider-based newsletter bounce/complaint handling, email segmentation beyond consent, campaign analytics, and operational reconciliation for interrupted sends.
- Actual SMTP, Telegram, WhatsApp Business, mentor conferencing, partner approval, and payment-provider credentials; no connection is implied by seeded data.
- Full editorial English translations and independently audited accessibility conformance. Public legal drafts require operator review.

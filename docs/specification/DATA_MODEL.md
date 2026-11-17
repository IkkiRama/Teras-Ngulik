# Data Model & Database Contract — Teras Ngulik

**Engine:** MySQL/MariaDB on shared hosting, `utf8mb4`. Use Laravel migrations, FK constraints where supported, unique compound keys and indexes. Use BIGINT IDs or UUIDs consistently (public opaque IDs recommended; performance-conscious hybrid acceptable). Currency stored integer rupiah (or minor currency unit explicitly consistent); no floating-point money. UTC in DB; display Asia/Jakarta. Deletion semantics require preserving financial and audit history.

> This is a **logical full-scope schema**, not a command to create one massive migration. Codex may normalize and refine, but must map every product requirement to durable schema/transaction rules and keep the data dictionary updated.

## 1. Identity, roles, profile, consent

| Table | Required fields/relations/constraints |
|---|---|
| `users` | id; username_original, username_normalized **UNIQUE**; display_name; email_normalized **UNIQUE**; password_hash nullable for OAuth-only; verified_at; status; created_at/updated_at; banned_at nullable |
| `user_profiles` | user_id UNIQUE FK; avatar_file_id nullable; avatar_svg_seed; bio; locale; timezone; region optional; birth_band/minor_flag; skill level; profile_visibility; links JSON validated; accessibility prefs, theme |
| `auth_identities` | user_id; provider (google/local); provider_subject; verified_claim; UNIQUE(provider,provider_subject) |
| `password_reauth_tokens` | user_id; token_digest; expires_at; consumed_at; attempts; requester IP/device metadata hash; cascade expiry |
| `username_history` | user_id; prior_username; expires or redirect flag; UNIQUE(prior_username) |
| `roles`, `permissions`, `role_permissions`, `user_roles` | role-based authorization, scope and grant metadata; superadmin separate |
| `guardian_links` | guardian_user_id, child_user_id; verified consent status/timestamps; permissions/restrictions |
| `consent_records` | user_id/guardian; purpose (newsletter, analytics, community), version, opt_in_at, revoked_at, context |
| `user_sessions` (framework), `security_events` | session/reauth activity and audit without logging secrets |

**Account invariants:** no self-service email change; username casefold normalized unique; verified recipient required for group purchase; account archive does not corrupt orders/certificates. Child visibility/consent enforced on every social/profile read.

## 2. Taxonomy, academy, content libraries

| Table | Key fields |
|---|---|
| `academies` | slug UNIQUE, name, description, cover, sort_order, published |
| `topics`, `tags`, `categories` | slug/name; category type for article/course/podcast if needed |
| `course_academies`, `course_tags`, `article_categories`, `article_tags` | many-to-many with appropriate compound UNIQUE |
| `courses` | slug UNIQUE, academy_primary_id, author_id, title, summary, thumbnail_id, locale, age_band, level, requirements, outcomes, access_policy, publication_status, published_at, version |
| `course_modules` | course_id, title, order_index, status; UNIQUE(course_id,order_index) |
| `lessons` | course_module_id, kind enum (`youtube_video`, `scrim`, `rich_text`, `document`, `download_resource`, `lab`, `quiz`, `assignment`), title, body/metadata, video_id if relevant, order_index, required, access_rule, publication_status, content_version |
| `lesson_resources` | lesson_id, file_id/url, display_name, is_downloadable, access_rule, sort_order |
| `files` | uploader_id, driver/disk, object_path, mime, size, hash, visibility, size limits, scan_status, created_at; no untrusted public paths |
| `articles` | slug unique per locale, author_id, title, summary, body richtext sanitized, cover_id, status, published_at, access_policy, language, SEO; revisions table separate |
| `article_versions` | article_id, editor_id, prior body/metadata, change_at |
| `ebooks`, `podcast_episodes`, `short_videos`, `gallery_items` | author, title, slug, media/files, transcript/captions, status, access_policy, tags; separate catalog record where appropriate |

**Article categories:** multiple categories through `article_categories` join. The website's article categories are filters inside `/artikel`, never extra navbar hierarchy.

## 3. Learning records, quiz & exam

| Table | Key fields |
|---|---|
| `enrollments` | user_id, course_id, reason/access_reference, status, started_at, completed_at; UNIQUE(user_id,course_id) subject to historical semantics |
| `lesson_progress` | user_id, lesson_id, last_position_ms/scroll_position, completion_percent, completed_at, last_opened_at, lesson_version, UI state JSON; UNIQUE(user_id,lesson_id) |
| `learning_paths`, `path_courses`, `path_enrollments`, `path_progress` | path metadata, course order/prerequisite, user participation and completion |
| `question_banks`, `questions`, `question_options` | bank/owner, type, prompt, explanation, answer rubric; options many-to-one, correct flags; allow count A-D/A-E or custom |
| `quizzes`, `quiz_questions`, `quiz_attempts`, `attempt_answers` | settings: pass_score, time_limit, randomized, retry, scoring, manual review; attempts finalized immutable; access and attempt limits |
| `assignments`, `assignment_submissions`, `submission_files`, `assignment_reviews` | rubric, per-course/cohort binding, deadline, feedback, resubmit status |
| `diagnostics`, `diagnostic_attempts`, `diagnostic_results` | academy/age/level, skill scores & recommendation |
| `skills`, `course_skills`, `user_skill_evidence` | skill taxonomy, assessed confidence, source event; radar chart derived from assessments/evidence |
| `review_cards`, `review_history` | user_id, skill/concept, card content, due_at, stability/difficulty, last_review, review rating, scheduler_version; due index |
| `notebooks`, `notes`, `bookmarks` | user_id, content Markdown, lesson/time reference, target type/id, private metadata, collection/tag |
| `certificates`, `certificate_revocations` | user_id, course/path/program ref, credential_id UNIQUE, issued_at, verification_secret/slug, status valid/revoked, snapshot course title and student name; audit revocation |

**Completion rules:** grades/result/assignment approvals and required content checks must use server-trusted policies; never let browser mark 'passed' by just toggling progress.

## 4. Scrim event store and learner forks

| Table | Key fields |
|---|---|
| `scrims` | lesson_id UNIQUE, owner_id, title, schema_version, duration_ms, audio_file_id nullable, recording_state, published_revision, locale, created/published |
| `scrim_revisions` | scrim_id, revision_no UNIQUE per scrim, initial_file_tree_manifest, event_format, content_hash, immutable_published_flag |
| `scrim_event_chunks` | revision_id, seq_start/seq_end, t_start_ms/t_end_ms, file_id or compressed blob pointer, checksum, UNIQUE(revision_id,seq_start) |
| `scrim_checkpoints` | revision_id, t_ms, file_tree_snapshot_file_id, audio_offset_ms, hash, index(revision_id,t_ms) |
| `scrim_forks` | user_id, revision_id, from_t_ms, file_manifest_storage, visibility, title, saved_at, source_checkpoint_id, fork_versions table as needed |
| `scrim_analytics` | user_id, scrim_id, played_ms, paused_t_ms, first/last use, saved fork event counts aggregate, no code contents in analytics logs |

Recording source of truth is an **immutable original timeline**. Learner mutation only writes `scrim_forks`; never patches published instructor event chunks. Chunked event storage prevents massive SQL rows.

## 5. Labs, projects, reviews, games

| Table | Key fields |
|---|---|
| `playground_projects` | user_id (nullable guest local-only), slug/opaque share id, title, runtime, visibility, free/pro quota flag, latest_version, template_id, created/updated |
| `project_files`, `project_versions` | project_id, safe path, content/blob ref, hash, version no, size, lang, snapshot |
| `playground_templates` | name, runtime, starter files, metadata, access_policy, instructor/admin author |
| `labs`, `lab_tasks`, `lab_test_cases`, `lab_submissions` | skill target, starter code, expected outputs, visibility of tests, runtime constraints, evaluation status, score, user_id |
| `code_reviews`, `review_comments` | submission/project ref, reviewer_id, source line range, feedback, status, rubric |
| `coding_challenges`, `challenge_attempts` | difficulty, active window, source tests, max attempts/cadence, result, antiabuse identifiers |
| `games`, `game_levels`, `game_attempts` | game rules, levels, measured skill, completion; XP/Bara not auto-issued unless configured |
| `showcase_projects`, `showcase_comments` | user_id, title, description, media, links, privacy, moderation, association to portfolio/project |

## 6. Community, messaging, notices

| Table | Key fields |
|---|---|
| `community_posts` | user_id, type (discussion/question/poll/showcase/announcement), title/body, visibility, status, tags, published_at |
| `comments` | author_id, parent_id FK same table, root_type/root_id or dedicated polymorphic root, body sanitized, depth/path index, status, edit/delete timestamps; careful threaded pagination |
| `comment_reports`, `content_reports`, `moderation_actions` | reporter/moderator, reason, target, decision, timestamps, audit |
| `post_poll_options`, `post_poll_votes` | option/vote, UNIQUE(poll_id,user_id) |
| `follows`, `mentions` | allowed relationships and privacy-aware notifications |
| `notifications`, `notification_preferences` | user_id, type, subject target, payload safe, unread/read and channel settings |
| `announcements`, `announcement_segments` | campaign, active_at/expiry, priority, text, link, target, dismissible; one visible announcement bar at once |

No stranger-to-child unrestricted DMs as default. Hide children publicly unless all requirements have been reviewed and satisfied.

## 7. Programs, bootcamp, mentor, event, jobs

| Table | Key fields |
|---|---|
| `bootcamps`, `bootcamp_cohorts` | title, access/pricing, cohort start/end, roster capacity, teacher, class policy |
| `classroom_modules`, `classroom_lessons`, `classroom_memberships`, `classroom_assignments`, `attendance_records`, `classroom_announcements` | FK cohort, materials/assignment, assessments, announcements |
| `mentors`, `mentor_availability`, `mentorship_bookings`, `mentorship_sessions` | mentor user id, price if any, timezone, slot, booking status, appointment link; prevent double booking |
| `events`, `event_ticket_types`, `event_registrations`, `event_checkins` | free/paid, quota, ticket, registrant, paid state, check-in |
| `competitions`, `competition_teams`, `team_members`, `competition_submissions`, `competition_scores` | rules, deadline, rubric, judge, ranking, appeals |
| `jobs`, `job_applications`, `partner_inquiries` | employer/moderator, visibility, contact consent, attachments, workflow |

Classroom **belongs to Bootcamp/Cohort**, never separate top-level product.

## 8. Commerce, group order, subscription, reward

| Table | Key fields/critical constraints |
|---|---|
| `products` | id, product_kind, purchasable_id, title snapshot, active, currency IDR, base_price_integer, access_rule |
| `product_variants`, `stock_movements` | merch sizes/colors, SKU UNIQUE, available/reserved, movement audit |
| `carts`, `cart_items` | owner/session, product variant, quantity, price preview; final price recalculated server |
| `orders` | buyer_user_id, order_number UNIQUE, status, currency, subtotal, discount, tax, fees, total integer, created/paid/expired, coupon snapshot |
| `order_items` | order_id, product/variant, quantity, unit_price, discount, tax, total, product snapshot; cannot silently change historical order |
| `group_order_recipients` | order_id, beneficiary_user_id, normalized_verified_email_snapshot, seat_no, status; UNIQUE(order_id,beneficiary_user_id), UNIQUE(order_id,seat_no) |
| `payments`, `payment_events` | order_id, provider, provider_order_id UNIQUE, provider_transaction_id UNIQUE nullable, token reference, external status, signature verified_at, raw redacted payload, idempotency key |
| `entitlements` | user_id, subject_type/subject_id, source_type/source_id, start/end, status; prevent duplicate grants per source+subject, support multiple independent sources |
| `plans`, `subscriptions`, `subscription_cycles` | plan_code, terms version, user_id, external transaction, cycle state/period, cancellation, end; payment verified |
| `vouchers`, `voucher_redemptions`, `promotion_campaigns` | code, discount type/limit, product/segment, validity, per-user/global usage, order_id and UNIQUE guard |
| `bundles`, `bundle_items` | bundle parent, member product/course, start/end, price |
| `preorders`, `preorder_fulfillments`, `shipment_addresses`, `shipments`, `refunds` | pre-sale deposits/full pay, expected fulfilment, customer delivery, payment/reversal trail |
| `xp_events` | user_id, source_type/source_id, amount >=0, season/academy, issued_at, idempotency_key UNIQUE per rule source; no spending |
| `leaderboard_seasons` | academy, age_band, start/end, ranking rule; compute from xp_events |
| `bara_ledger` | user_id, direction `credit/debit/reversal`, amount >0 integer, source_kind/id, occurred_at, idempotency_key UNIQUE, balance_after optional; append-only |
| `reward_items`, `reward_redemptions` | sku/subject, Bara_cost, stock, eligibility, order/entitlement/shipment, redemption state, unique redemption key |

**Transaction critical:** write new payment success and **all N group entitlements** in one transaction with row locking. If one recipient fails validation, do not produce partial grants; reconciliation job should retry. `Bara` redemption: lock balance/ledger aggregate/inventory before debit. Handle duplicates, timeouts, refunds and concurrency.

## 9. Newsletter, support, docs and audit

| Table | Key fields |
|---|---|
| `newsletter_subscriptions` | normalized_email/user_id, consent_at, status, subscribed_at, unsubscribed_at, source, topics/frequency |
| `newsletter_campaigns`, `newsletter_deliveries`, `email_suppression` | recipient segments, scheduled_at, send_state, audit, unsubscribe, bounce/no marketing without consent |
| `support_tickets`, `support_messages`, `support_attachments` | user/agent, category, status, reply, visibility, SLA |
| `whistleblower_cases`, `whistleblower_messages`, `whistleblower_access_log` | separate highly restricted encrypted metadata/content, channel, status, accountable staff |
| `audit_logs` | actor, action, target, sanitized diff, IP hash, timestamp; index actor/action/time |
| `system_settings`, `translations`, `content_translations` | key, value, locale, version/approval and safe validation |
| `analytics_events` | user_id optional, event type, timestamp, minimized metadata, retention, consent for optional profiling |
| `api_clients`, `api_tokens`, `api_usage` | permissions/scopes, revocation, usage/rate limits if public API enabled |

## 10. FK/Indexes/consistency and migration strategy

- Create FK/indexes on each list/detail/search pattern: `lessons(course_module_id,order_index)`, `courses(academy_primary_id,status,published_at)`, `articles(status,published_at,author_id)`, `article_categories(category_id,article_id)`, `review_cards(user_id,due_at)`, `lesson_progress(user_id,lesson_id)`, `payments(provider_order_id)`, `orders(buyer_user_id,created_at)`, `entitlements(user_id,subject_type,subject_id,status)`, `comments(root_type,root_id,parent_id,created_at)`.
- Invariants must be tested with real DB transactions in addition to app validation. Avoid broad polymorphic relations for financial entries unless FK integrity can be maintained with explicit subject tables/services.
- Seed example academies/course/article/lab/demo users **only in non-production fixtures**. Never seed realistic private emails or fake payment successes to production.
- Migration grouping by domain; rollback and backfill plans for any schema changes; protect historical paid orders/rewards and user work from destructive migrations.

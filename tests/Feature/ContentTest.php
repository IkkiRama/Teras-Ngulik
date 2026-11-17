<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\ContentAttachment;
use App\Models\ContentItem;
use App\Models\NewsletterCampaign;
use App\Models\ProgramRegistration;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['app.asset_url' => 'http://test-assets.local']);
        $this->withHeader('X-Inertia-Version', hash('xxh128', 'http://test-assets.local'));
    }

    private function learner(array $fields = []): User
    {
        return User::factory()->create(['email_verified_at' => now(), ...$fields]);
    }

    private function item(array $fields = []): ContentItem
    {
        return ContentItem::create(['kind' => 'article', 'slug' => 'post-'.uniqid(), 'title' => 'A meaningful article', 'summary' => 'A useful summary', 'body' => 'Private body with carefully explained material', 'access' => 'public', 'status' => 'published', 'published_at' => now()->subMinute(), ...$fields]);
    }

    public function test_paid_content_and_future_publications_do_not_leak_body_or_assets(): void
    {
        $item = $this->item(['access' => 'purchased', 'metadata' => ['audio_url' => 'https://private.example.test/audio.mp3', 'classroom' => [['body' => 'secret-classroom']]]]);
        $this->get($item->public_url, ['X-Inertia' => 'true'])->assertOk()->assertJsonPath('props.data.item.locked', true)->assertJsonPath('props.data.item.body', null)->assertDontSee('private.example.test')->assertDontSee('secret-classroom');
        $future = $this->item(['published_at' => now()->addDay()]);
        $this->get($future->public_url)->assertNotFound();
        $this->getJson('/api/v1/content')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_comments_require_verification_and_parent_must_belong_to_same_item(): void
    {
        $user = $this->learner();
        $one = $this->item();
        $two = $this->item();
        $parent = Comment::create(['content_item_id' => $one->id, 'user_id' => $user->id, 'body' => 'Original comment']);
        $this->actingAs($user)->post('/content/'.$two->id.'/comments', ['body' => 'Trying cross-root reply', 'parent_id' => $parent->id])->assertNotFound();
        $user->forceFill(['email_verified_at' => null])->save();
        $this->actingAs($user)->post('/content/'.$one->id.'/comments', ['body' => 'Must verify first'])->assertRedirect('/verify-email');
        $this->assertDatabaseCount('comments', 1);
    }

    public function test_nested_reply_notifies_parent_owner_and_other_users_cannot_delete(): void
    {
        $one = $this->learner();
        $two = $this->learner();
        $item = $this->item();
        $parent = Comment::create(['content_item_id' => $item->id, 'user_id' => $one->id, 'body' => 'Can someone explain this?']);
        $this->actingAs($two)->post('/content/'.$item->id.'/comments', ['body' => 'Here is an explanation', 'parent_id' => $parent->id])->assertRedirect();
        $this->assertDatabaseHas('comments', ['parent_id' => $parent->id, 'depth' => 1, 'user_id' => $two->id]);
        $this->assertDatabaseHas('content_notifications', ['user_id' => $one->id]);
        $this->delete('/comments/'.$parent->id)->assertForbidden();
    }

    public function test_child_accounts_cannot_publish_to_public_community(): void
    {
        $child = $this->learner(['age_band' => 'child']);
        $item = $this->item();
        $this->actingAs($child)->post('/komunitas/posts', ['kind' => 'discussion', 'title' => 'Should remain private', 'body' => 'This child should not publish publicly'])->assertForbidden();
        $this->post('/content/'.$item->id.'/comments', ['body' => 'Public child comment'])->assertForbidden();
        $this->assertDatabaseCount('content_items', 1);
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_program_capacity_and_duplicate_registration_are_enforced(): void
    {
        $one = $this->learner();
        $two = $this->learner();
        $item = $this->item(['kind' => 'event', 'capacity' => 1]);
        $this->actingAs($one)->post('/programs/'.$item->id.'/register')->assertRedirect();
        $this->post('/programs/'.$item->id.'/register')->assertRedirect();
        $this->assertDatabaseCount('program_registrations', 1);
        $this->actingAs($two)->post('/programs/'.$item->id.'/register')->assertSessionHasErrors('registration');
        $registration = ProgramRegistration::first();
        $this->post('/programs/registrations/'.$registration->id, ['action' => 'cancel'])->assertForbidden();
        $this->actingAs($one)->post('/programs/registrations/'.$registration->id, ['action' => 'cancel'])->assertRedirect();
        $this->actingAs($two)->post('/programs/'.$item->id.'/register')->assertRedirect();
        $this->assertDatabaseHas('program_registrations', ['user_id' => $two->id, 'status' => 'active']);
    }

    public function test_paid_program_cannot_be_registered_without_entitlement(): void
    {
        $user = $this->learner();
        $item = $this->item(['kind' => 'bootcamp', 'price' => 100000]);
        $this->actingAs($user)->post('/programs/'.$item->id.'/register')->assertSessionHasErrors('registration');
        DB::table('entitlements')->insert(['user_id' => $user->id, 'kind' => 'content', 'subject_id' => $item->id, 'source' => 'test', 'source_id' => '1', 'created_at' => now(), 'updated_at' => now()]);
        $this->post('/programs/'.$item->id.'/register')->assertRedirect();
        $this->assertDatabaseCount('program_registrations', 1);
    }

    public function test_mentorship_prevents_double_booking_and_requires_valid_slot(): void
    {
        $one = $this->learner();
        $two = $this->learner();
        $item = $this->item(['kind' => 'mentorship', 'metadata' => ['slots' => ['Slot A', 'Slot B']]]);
        $this->actingAs($one)->post('/programs/'.$item->id.'/register', ['slot' => 'invented'])->assertSessionHasErrors('slot');
        $this->post('/programs/'.$item->id.'/register', ['slot' => 'Slot A'])->assertRedirect();
        $this->actingAs($two)->post('/programs/'.$item->id.'/register', ['slot' => 'Slot A'])->assertSessionHasErrors('slot');
        $this->post('/programs/'.$item->id.'/register', ['slot' => 'Slot B'])->assertRedirect();
        $this->assertDatabaseCount('program_registrations', 2);
    }

    public function test_bootcamp_classroom_only_exposed_to_active_registrants(): void
    {
        $item = $this->item(['kind' => 'bootcamp', 'metadata' => ['classroom' => [['title' => 'Private lesson', 'body' => 'registered-only-material']]]]);
        $this->get($item->public_url, ['X-Inertia' => 'true'])->assertOk()->assertDontSee('registered-only-material');
        $user = $this->learner();
        $this->actingAs($user)->post('/programs/'.$item->id.'/register');
        $this->get($item->public_url, ['X-Inertia' => 'true'])->assertOk()->assertSee('registered-only-material');
    }

    public function test_support_is_encrypted_owned_and_whistleblowing_separated_with_audit(): void
    {
        $one = $this->learner();
        $two = $this->learner();
        $admin = $this->learner(['role' => 'admin']);
        $this->actingAs($one)->post('/support/tickets', ['kind' => 'support', 'subject' => 'Cannot open my lesson', 'body' => 'Please help with the lesson issue in my account.'])->assertRedirect();
        $ticket = SupportTicket::firstOrFail();
        $this->assertStringNotContainsString('Please help', DB::table('support_tickets')->value('body'));
        $this->actingAs($two)->get('/kontak?ticket='.$ticket->id)->assertNotFound();
        $this->post('/support/tickets/'.$ticket->id.'/reply', ['body' => 'Not my ticket'])->assertForbidden();
        $this->actingAs($one)->post('/support/tickets', ['kind' => 'whistleblowing', 'subject' => 'Restricted case subject', 'body' => 'This is confidential case detail not for public support.', 'anonymous' => true])->assertRedirect();
        $case = SupportTicket::where('kind', 'whistleblowing')->firstOrFail();
        $this->assertNull($case->user_id);
        $this->get('/kontak', ['X-Inertia' => 'true'])->assertDontSee('Restricted case subject');
        $this->actingAs($admin)->get('/admin/whistleblowing?ticket='.$case->id, ['X-Inertia' => 'true'])->assertOk()->assertSee('This is confidential');
        $this->assertDatabaseHas('audit_logs', ['action' => 'whistleblowing.case_opened', 'target' => 'ticket:'.$case->id]);
    }

    public function test_cms_checks_server_role_and_archives_without_destroying_history(): void
    {
        $learner = $this->learner();
        $admin = $this->learner(['role' => 'admin']);
        $payload = ['kind' => 'article', 'title' => 'New article from CMS', 'body' => 'A long enough useful explanation', 'access' => 'public', 'status' => 'published', 'price' => 0, 'categories' => ['Learning']];
        $this->actingAs($learner)->post('/admin/content', $payload)->assertForbidden();
        $this->actingAs($admin)->post('/admin/content', $payload)->assertRedirect();
        $item = ContentItem::firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['action' => 'content.created']);
        $this->delete('/admin/content/'.$item->id)->assertRedirect('/admin/content');
        $this->assertSame('archived', $item->fresh()->status);
        $this->assertDatabaseCount('content_items', 1);
    }

    public function test_newsletter_requires_consent_and_unsubscribe_is_post_confirmed(): void
    {
        Mail::fake();
        $this->post('/newsletter', ['email' => 'newsletter@example.test'])->assertSessionHasErrors('consent');
        $this->assertDatabaseCount('newsletter_subscriptions', 0);
        $this->post('/newsletter', ['email' => 'newsletter@example.test', 'consent' => true])->assertRedirect();
        $this->assertDatabaseHas('newsletter_subscriptions', ['status' => 'pending']);
        DB::table('newsletter_subscriptions')->update(['confirmation_hash' => hash('sha256', 'confirmation-test')]);
        $this->get('/newsletter/confirm/confirmation-test')->assertOk();
        $this->assertDatabaseHas('newsletter_subscriptions', ['status' => 'pending']);
        $this->post('/newsletter/confirm/confirmation-test')->assertOk();
        $this->assertDatabaseHas('newsletter_subscriptions', ['status' => 'subscribed']);
        $this->post('/newsletter/confirm/confirmation-test')->assertNotFound();
        DB::table('newsletter_subscriptions')->update(['unsubscribe_hash' => hash('sha256', 'known-token')]);
        $this->get('/newsletter/unsubscribe/known-token')->assertOk();
        $this->assertDatabaseHas('newsletter_subscriptions', ['status' => 'subscribed']);
        $this->post('/newsletter/unsubscribe/known-token')->assertOk();
        $this->assertDatabaseHas('newsletter_subscriptions', ['status' => 'unsubscribed']);
    }

    public function test_article_filters_and_global_search_only_return_published_allowed_content(): void
    {
        $author = $this->learner();
        $this->item(['title' => 'SQL for analysts', 'categories' => ['SQL', 'Data'], 'author_id' => $author->id]);
        $this->item(['title' => 'Unpublished secret', 'status' => 'draft']);
        $this->item(['title' => 'Subscriber secret', 'access' => 'subscribers']);
        $this->get('/artikel?category=SQL&author='.$author->id, ['X-Inertia' => 'true'])->assertOk()->assertJsonPath('props.data.items.total', 1)->assertJsonPath('props.data.items.data.0.title', 'SQL for analysts');
        $this->get('/cari?q=secret', ['X-Inertia' => 'true'])->assertOk()->assertJsonCount(0, 'props.data.items');
        $this->get('/bantuan/faq', ['X-Inertia' => 'true'])->assertOk()->assertJsonPath('props.screen', 'content-list');
    }

    public function test_private_material_download_checks_content_parent_and_entitlement(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('materials/private.pdf', 'private pdf bytes');
        $item = $this->item(['access' => 'purchased']);
        $other = $this->item();
        $user = $this->learner();
        $attachment = ContentAttachment::create(['content_item_id' => $item->id, 'uploaded_by' => $user->id, 'original_name' => 'Guide.pdf', 'path' => 'materials/private.pdf', 'mime' => 'application/pdf', 'size' => 17]);
        $this->get('/content/'.$item->id.'/attachments/'.$attachment->id)->assertForbidden();
        $this->actingAs($user)->get('/content/'.$other->id.'/attachments/'.$attachment->id)->assertNotFound();
        $this->get($item->public_url, ['X-Inertia' => 'true'])->assertDontSee('Guide.pdf')->assertDontSee('materials/private.pdf');
        DB::table('entitlements')->insert(['user_id' => $user->id, 'kind' => 'content', 'subject_id' => $item->id, 'source' => 'test', 'source_id' => 'file', 'created_at' => now(), 'updated_at' => now()]);
        $this->get('/content/'.$item->id.'/attachments/'.$attachment->id)->assertOk()->assertDownload('Guide.pdf')->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_material_upload_rejects_executable_types_and_non_admin_users(): void
    {
        Storage::fake('local');
        $admin = $this->learner(['role' => 'admin']);
        $user = $this->learner();
        $item = $this->item();
        $file = UploadedFile::fake()->createWithContent('guide.pdf', "%PDF-1.4\nThis is a study guide.");
        $this->actingAs($user)->post('/admin/content/'.$item->id.'/attachments', ['file' => $file])->assertForbidden();
        $this->actingAs($admin)->post('/admin/content/'.$item->id.'/attachments', ['file' => UploadedFile::fake()->createWithContent('evil.html', '<script>alert(1)</script>')])->assertSessionHasErrors('file');
        $this->post('/admin/content/'.$item->id.'/attachments', ['file' => $file])->assertRedirect();
        $this->assertDatabaseCount('content_attachments', 1);
        $attachment = ContentAttachment::firstOrFail();
        Storage::disk('local')->assertExists($attachment->getRawOriginal('path'));
        $this->delete('/admin/attachments/'.$attachment->id)->assertRedirect();
        Storage::disk('local')->assertMissing($attachment->getRawOriginal('path'));
    }

    public function test_campaign_approval_creates_audience_only_from_consent_and_dry_run_sends_nothing(): void
    {
        Mail::fake();
        $admin = $this->learner(['role' => 'admin']);
        foreach (['subscribed', 'unsubscribed'] as $i => $status) {
            DB::table('newsletter_subscriptions')->insert(['email' => 'person'.$i.'@example.test', 'status' => $status, 'consent_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->actingAs($admin)->post('/admin/newsletter', ['title' => 'Study notes', 'subject' => 'A concept for this week', 'body' => 'An original educational newsletter for consenting readers.'])->assertRedirect();
        $campaign = NewsletterCampaign::firstOrFail();
        $this->assertSame('draft', $campaign->status);
        $this->post('/admin/newsletter/'.$campaign->id.'/queue')->assertSessionHasErrors('approved');
        $this->post('/admin/newsletter/'.$campaign->id.'/queue', ['approved' => true])->assertRedirect();
        $this->assertDatabaseCount('newsletter_deliveries', 1);
        $this->artisan('newsletter:send', ['--dry-run' => true])->assertSuccessful();
        $this->assertDatabaseHas('newsletter_deliveries', ['status' => 'queued']);
        Mail::assertNothingSent();
        $this->artisan('newsletter:send')->assertFailed();
        $this->assertDatabaseHas('newsletter_deliveries', ['status' => 'queued']);
    }

    public function test_newsletter_batch_is_bounded_suppresses_new_opt_outs_and_is_replay_safe(): void
    {
        Mail::fake();
        config(['newsletter.sending_enabled' => true, 'mail.default' => 'smtp']);
        $admin = $this->learner(['role' => 'admin']);
        for ($i = 0; $i < 3; $i++) {
            DB::table('newsletter_subscriptions')->insert(['email' => 'consent'.$i.'@example.test', 'status' => 'subscribed', 'consent_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        }
        $campaign = NewsletterCampaign::create(['created_by' => $admin->id, 'title' => 'Batch', 'subject' => 'A learning note', 'body' => 'Educational content sent only to consented recipients.']);
        $this->actingAs($admin)->post('/admin/newsletter/'.$campaign->id.'/queue', ['approved' => true]);
        DB::table('newsletter_subscriptions')->where('email', 'consent1@example.test')->update(['status' => 'unsubscribed']);
        $this->artisan('newsletter:send', ['--limit' => 1])->assertSuccessful();
        $this->assertSame(1, DB::table('newsletter_deliveries')->where('status', 'sent')->count());
        $this->artisan('newsletter:send', ['--limit' => 3])->assertSuccessful();
        $this->assertSame(2, DB::table('newsletter_deliveries')->where('status', 'sent')->count());
        $this->assertSame(1, DB::table('newsletter_deliveries')->where('status', 'suppressed')->count());
        $this->artisan('newsletter:send', ['--limit' => 3])->assertSuccessful();
        $this->assertSame(2,DB::table('newsletter_deliveries')->where('status','sent')->count());
        $this->assertSame('sent',$campaign->fresh()->status);
        $sub = DB::table('newsletter_subscriptions')->where('email','consent0@example.test')->first();
        $this->assertNotNull($sub->unsubscribe_token);
        $this->assertStringNotContainsString('http',$sub->unsubscribe_token);
    }
}

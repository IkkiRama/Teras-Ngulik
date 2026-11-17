<?php

namespace App\Console\Commands;

use App\Models\NewsletterCampaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendNewsletterBatch extends Command
{
    protected $signature = 'newsletter:send {--limit= : Maximum deliveries for this bounded invocation, 1–100} {--dry-run : Show eligible queue size without sending or changing records}';

    protected $description = 'Send one bounded batch of approved newsletters to still-consenting recipients';

    public function handle(): int
    {
        $limit = (int) ($this->option('limit') ?: config('newsletter.batch_size', 25));
        if ($limit < 1 || $limit > 100) {
            $this->error('Batch limit must be between 1 and 100.');

            return self::FAILURE;
        }
        $eligible = $this->eligibleQuery()->count();
        if ($this->option('dry-run')) {
            $this->info("Dry run: {$eligible} eligible delivery records; maximum {$limit} per invocation. No email sent and no records changed.");

            return self::SUCCESS;
        }
        if (! config('newsletter.sending_enabled') || in_array(config('mail.default'), ['log', 'array'])) {
            $this->error('NEEDS_CONFIGURATION: configure a real mail transport and explicitly enable NEWSLETTER_SENDING_ENABLED. No email sent.');

            return self::FAILURE;
        }
        $sent = 0;
        $skipped = 0;
        $failed = 0;
        for ($i = 0; $i < $limit; $i++) {
            $row = DB::transaction(function () {
                $delivery = $this->eligibleQuery()->select('newsletter_deliveries.*')->lockForUpdate()->first();
                if (! $delivery) {
                    return null;
                }
                DB::table('newsletter_deliveries')->where('id', $delivery->id)->where('status', 'queued')->update(['status' => 'sending', 'claimed_at' => now(), 'updated_at' => now()]);

                return $delivery;
            });
            if (! $row) {
                break;
            }
            $subscription = DB::table('newsletter_subscriptions')->where('id', $row->subscription_id)->first();
            $campaign = NewsletterCampaign::find($row->campaign_id);
            if (! $subscription || $subscription->status !== 'subscribed' || ! $subscription->consent_at || ! $campaign || $campaign->status !== 'queued') {
                DB::table('newsletter_deliveries')->where('id', $row->id)->update(['status' => 'suppressed', 'updated_at' => now()]);
                $skipped++;

                continue;
            }
            try {
                $token = DB::transaction(function () use ($subscription) {
                    $sub = DB::table('newsletter_subscriptions')->where('id', $subscription->id)->lockForUpdate()->first();
                    if ($sub->status !== 'subscribed') {
                        return null;
                    }
                    if ($sub->unsubscribe_token) {
                        return Crypt::decryptString($sub->unsubscribe_token);
                    }
                    $value = Str::random(48);
                    DB::table('newsletter_subscriptions')->where('id', $sub->id)->update(['unsubscribe_token' => Crypt::encryptString($value), 'unsubscribe_hash' => hash('sha256', $value), 'updated_at' => now()]);

                    return $value;
                });
                if (! $token) {
                    DB::table('newsletter_deliveries')->where('id', $row->id)->update(['status' => 'suppressed', 'updated_at' => now()]);
                    $skipped++;

                    continue;
                }
                $body = $campaign->body."\n\n—\nKamu menerima newsletter karena memberikan persetujuan.\nBerhenti berlangganan: ".url('/newsletter/unsubscribe/'.$token);
                Mail::raw($body, fn ($m) => $m->to($subscription->email)->subject($campaign->subject));
                DB::table('newsletter_deliveries')->where('id', $row->id)->update(['status' => 'sent', 'sent_at' => now(), 'failure_code' => null, 'updated_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                DB::table('newsletter_deliveries')->where('id', $row->id)->update(['status' => 'failed', 'failure_code' => 'transport_or_encryption_failure', 'updated_at' => now()]);
                Log::warning('Newsletter delivery failed', ['delivery_id' => $row->id, 'exception_type' => $e::class]);
                $failed++;
            }
        }
        NewsletterCampaign::where('status', 'queued')->where(fn ($q) => $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now()))->each(function ($campaign) {
            if (! DB::table('newsletter_deliveries')->where('campaign_id', $campaign->id)->whereIn('status', ['queued', 'sending', 'failed'])->exists()) {
                $campaign->update(['status' => 'sent', 'completed_at' => now()]);
            }
        });
        $this->info("Batch finished: {$sent} sent, {$skipped} suppressed, {$failed} failed. No addresses or message bodies logged.");

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function eligibleQuery()
    {
        return DB::table('newsletter_deliveries')->join('newsletter_campaigns', 'newsletter_campaigns.id', '=', 'newsletter_deliveries.campaign_id')->where('newsletter_deliveries.status', 'queued')->where('newsletter_campaigns.status', 'queued')->where(fn ($q) => $q->whereNull('newsletter_campaigns.scheduled_at')->orWhere('newsletter_campaigns.scheduled_at','<=',now()))->orderBy('newsletter_deliveries.id');
    }
}

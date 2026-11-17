<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\ContentAttachment;
use App\Models\ContentItem;
use App\Models\NewsletterCampaign;
use App\Models\ProgramRegistration;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AdminController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403);
    }

    private function audit(Request $request, string $action, string $target, array $metadata = []): void
    {
        DB::table('audit_logs')->insert(['user_id' => $request->user()->id, 'action' => $action, 'target' => $target, 'metadata' => json_encode($metadata), 'created_at' => now(), 'updated_at' => now()]);
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin($request);
        $counts = ['users' => DB::table('users')->count(), 'published_content' => ContentItem::where('status', 'published')->count(), 'draft_content' => ContentItem::where('status', 'draft')->count(), 'open_tickets' => SupportTicket::where('kind', '!=', 'whistleblowing')->where('status', 'open')->count(), 'reports' => DB::table('content_reports')->where('status', 'open')->count(), 'registrations' => ProgramRegistration::where('status', 'active')->count(), 'newsletter' => DB::table('newsletter_subscriptions')->where('status', 'subscribed')->count()];
        if (Schema::hasTable('courses')) {
            $counts['courses'] = DB::table('courses')->count();
        }
        if (Schema::hasTable('orders')) {
            $counts['orders'] = DB::table('orders')->count();
            $counts['paid_orders'] = DB::table('orders')->where('status', 'paid')->count();
        }

        return Inertia::render('App', ['screen' => 'admin', 'data' => ['counts' => $counts, 'activity' => DB::table('audit_logs')->latest()->limit(12)->get(['action', 'target', 'created_at']), 'recent_content' => ContentItem::latest()->limit(5)->get(['id', 'title', 'kind', 'status'])]]);
    }

    public function content(Request $request)
    {
        $this->authorizeAdmin($request);
        $items = ContentItem::query()->when($request->filled('kind'), fn ($q) => $q->where('kind', $request->input('kind')))->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->input('q').'%'))->latest()->paginate(20)->withQueryString();
        $editing = $request->filled('edit') ? ContentItem::with('attachments:id,content_item_id,original_name,mime,size')->findOrFail($request->integer('edit')) : null;

        return Inertia::render('App', ['screen' => 'admin-content', 'data' => ['items' => $items, 'editing' => $editing, 'kinds' => ContentController::LABELS, 'filters' => $request->only('q', 'kind')]]);
    }

    public function saveContent(Request $request, ?ContentItem $item = null)
    {
        $this->authorizeAdmin($request);
        $fields = $request->validate(['kind' => ['required', Rule::in(array_keys(ContentController::LABELS))], 'title' => 'required|string|min:3|max:220', 'slug' => ['nullable', 'string', 'regex:/^[a-z0-9\-]+$/', 'max:220', Rule::unique('content_items', 'slug')->ignore($item?->id)], 'summary' => 'nullable|string|max:1200', 'body' => 'required|string|max:100000', 'categories' => 'nullable|array|max:12', 'categories.*' => 'string|max:70', 'metadata' => 'nullable|array|max:30', 'access' => 'required|in:public,registered,subscribers,purchased,cohort', 'status' => 'required|in:draft,review,published,archived', 'price' => 'required|integer|min:0|max:100000000', 'capacity' => 'nullable|integer|min:1|max:1000000', 'published_at' => 'nullable|date']);
        $fields['slug'] = $fields['slug'] ?? (Str::slug($fields['title']).'-'.Str::lower(Str::random(5)));
        $fields['author_id'] = $item?->author_id ?? $request->user()->id;
        if ($fields['status'] === 'published' && empty($fields['published_at'])) {
            $fields['published_at'] = now();
        }
        $saved = $item ?? new ContentItem;
        $saved->fill($fields)->save();
        $this->audit($request, $item ? 'content.updated' : 'content.created', 'content:'.$saved->id, ['kind' => $saved->kind, 'status' => $saved->status]);

        return redirect('/admin/content?edit='.$saved->id)->with('success', 'Konten tersimpan.');
    }

    public function archiveContent(Request $request, ContentItem $item)
    {
        $this->authorizeAdmin($request);
        $item->update(['status' => 'archived']);
        $this->audit($request, 'content.archived', 'content:'.$item->id);

        return redirect('/admin/content')->with('success', 'Konten diarsipkan; riwayat pendaftaran dipertahankan.');
    }

    public function moderation(Request $request)
    {
        $this->authorizeAdmin($request);

        return Inertia::render('App', ['screen' => 'admin-moderation', 'data' => ['reports' => DB::table('content_reports')->join('content_items', 'content_items.id', '=', 'content_reports.content_item_id')->select('content_reports.*', 'content_items.title')->latest('content_reports.id')->limit(50)->get(), 'comments' => Comment::with('author:id,name,username')->latest()->limit(50)->get(), 'registrations' => ProgramRegistration::with('item:id,title,kind')->latest()->limit(50)->get()]]);
    }

    public function moderate(Request $request)
    {
        $this->authorizeAdmin($request);
        $fields = $request->validate(['type' => 'required|in:comment,report', 'id' => 'required|integer', 'status' => 'required|in:published,hidden,resolved,open']);
        if ($fields['type'] === 'comment') {
            abort_unless(in_array($fields['status'], ['published', 'hidden']), 422);
            Comment::findOrFail($fields['id'])->update(['status' => $fields['status']]);
        } else {
            abort_unless(in_array($fields['status'], ['resolved', 'open']), 422);
            abort_unless(DB::table('content_reports')->where('id', $fields['id'])->exists(), 404);
            DB::table('content_reports')->where('id', $fields['id'])->update(['status' => $fields['status'], 'updated_at' => now()]);
        }
        $this->audit($request, 'moderation.'.$fields['status'], $fields['type'].':'.$fields['id']);

        return back()->with('success', 'Moderasi tersimpan.');
    }

    public function reviewRegistration(Request $request, ProgramRegistration $registration)
    {
        $this->authorizeAdmin($request);
        $fields = $request->validate(['feedback' => 'required|string|max:5000', 'score' => 'nullable|integer|min:0|max:100']);
        $registration->update(['metadata' => array_merge($registration->metadata ?? [], ['feedback' => $fields['feedback'], 'score' => $fields['score'] ?? null, 'reviewed_at' => now()->toIso8601String(), 'reviewer_id' => $request->user()->id])]);
        $this->audit($request, 'program.reviewed', 'registration:'.$registration->id);

        return back()->with('success', 'Penilaian program tersimpan.');
    }

    public function support(Request $request)
    {
        $this->authorizeAdmin($request);
        $whistle = $request->route('whistle', false);
        $query = SupportTicket::query()->when($whistle, fn ($q) => $q->where('kind', 'whistleblowing'), fn ($q) => $q->where('kind', '!=', 'whistleblowing'));
        $ticket = $request->filled('ticket') ? (clone $query)->findOrFail($request->integer('ticket')) : null;
        if ($ticket && $whistle) {
            $this->audit($request, 'whistleblowing.case_opened', 'ticket:'.$ticket->id);
        }

        return Inertia::render('App', ['screen' => 'admin-support', 'data' => ['whistle' => $whistle, 'tickets' => $query->latest()->paginate(25)->through(fn ($t) => $t->only('id', 'reference', 'subject', 'status', 'kind', 'created_at')), 'ticket' => $ticket]]);
    }

    public function ticketStatus(Request $request, SupportTicket $ticket)
    {
        $this->authorizeAdmin($request);
        $fields = $request->validate(['status' => 'required|in:open,investigating,answered,closed']);
        $ticket->update($fields);
        $this->audit($request, 'ticket.status_changed', 'ticket:'.$ticket->id, ['status' => $fields['status']]);

        return back()->with('success', 'Status diperbarui.');
    }

    public function uploadAttachment(Request $request, ContentItem $item)
    {
        $this->authorizeAdmin($request);
        $request->validate(['file' => 'required|file|mimes:pdf,txt,csv,zip,docx,pptx,xlsx,png,jpg,jpeg,webp|max:'.(int) config('newsletter.max_attachment_kb', 10240)]);
        $file = $request->file('file');
        abort_if(in_array($file->getMimeType(), ['text/html', 'image/svg+xml', 'application/x-httpd-php', 'application/javascript', 'text/javascript']), 422);
        $path = null;
        try {
            DB::transaction(function () use ($item, $file, $request, &$path) {
                ContentItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
                if ($item->attachments()->count() >= 20) {
                    throw ValidationException::withMessages(['file' => 'Maksimal 20 lampiran per konten.']);
                }
                $path = $file->store('materials', 'local');
                $name = preg_replace('/[^\pL\pN_. \-]/u', '_', basename($file->getClientOriginalName()));
                $item->attachments()->create(['uploaded_by' => $request->user()->id, 'path' => $path, 'original_name' => mb_substr($name, 0, 180), 'mime' => $file->getMimeType(), 'size' => $file->getSize()]);
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            } throw $e;
        }
        $this->audit($request, 'material.uploaded', 'content:'.$item->id);

        return back()->with('success', 'Lampiran disimpan di penyimpanan privat.');
    }

    public function removeAttachment(Request $request, ContentAttachment $attachment)
    {
        $this->authorizeAdmin($request);
        Storage::disk('local')->delete($attachment->getRawOriginal('path'));
        $this->audit($request, 'material.deleted', 'attachment:'.$attachment->id);
        $attachment->delete();

        return back()->with('success', 'Lampiran dihapus.');
    }

    public function newsletter(Request $request)
    {
        $this->authorizeAdmin($request);
        $editing = $request->filled('edit') ? NewsletterCampaign::findOrFail($request->integer('edit')) : null;
        $campaigns = NewsletterCampaign::latest()->paginate(20);
        $deliveries = $editing ? DB::table('newsletter_deliveries')->where('campaign_id', $editing->id)->select('status', DB::raw('count(*) as count'))->groupBy('status')->get() : [];

        return Inertia::render('App', ['screen' => 'admin-newsletter', 'data' => ['campaigns' => $campaigns, 'editing' => $editing, 'deliveries' => $deliveries, 'subscribed' => DB::table('newsletter_subscriptions')->where('status', 'subscribed')->whereNotNull('consent_at')->count(), 'sending_enabled' => config('newsletter.sending_enabled'), 'mailer' => config('mail.default')]]);
    }

    public function saveCampaign(Request $request, ?NewsletterCampaign $campaign = null)
    {
        $this->authorizeAdmin($request);
        abort_if($campaign && $campaign->status !== 'draft', 422, 'Hanya draf yang dapat diedit.');
        $fields = $request->validate(['title' => 'required|string|min:3|max:180', 'subject' => 'required|string|min:3|max:180', 'body' => 'required|string|min:20|max:50000', 'scheduled_at' => 'nullable|date']);
        $saved = $campaign ?? new NewsletterCampaign;
        $saved->fill($fields);
        if (! $campaign) {
            $saved->created_by = $request->user()->id;
        }$saved->save();
        $this->audit($request, 'newsletter.draft_saved', 'campaign:'.$saved->id);

        return redirect('/admin/newsletter?edit='.$saved->id)->with('success', 'Draf newsletter tersimpan. Belum ada email yang dikirim.');
    }

    public function queueCampaign(Request $request, NewsletterCampaign $campaign)
    {
        $this->authorizeAdmin($request);
        $request->validate(['approved' => 'accepted']);
        DB::transaction(function () use ($campaign) {
            $locked = NewsletterCampaign::whereKey($campaign->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'draft', 422, 'Kampanye ini sudah masuk antrean.');
            DB::table('newsletter_subscriptions')->where('status', 'subscribed')->whereNotNull('consent_at')->orderBy('id')->chunkById(200, function ($rows) use ($locked) {
                $records = [];
                foreach ($rows as $s) {
                    $records[] = ['campaign_id' => $locked->id, 'subscription_id' => $s->id, 'status' => 'queued', 'created_at' => now(), 'updated_at' => now()];
                }if ($records) {
                    DB::table('newsletter_deliveries')->insertOrIgnore($records);
                }
            });
            $locked->update(['status' => 'queued']);
        });
        $this->audit($request, 'newsletter.approved_and_queued', 'campaign:'.$campaign->id);

        return back()->with('success', 'Antrean disiapkan. Pengiriman hanya berjalan melalui command operator dengan konfigurasi pengiriman aktif.');
    }

    public function cancelCampaign(Request $request, NewsletterCampaign $campaign)
    {
        $this->authorizeAdmin($request);
        $campaign->update(['status' => 'cancelled']);
        DB::table('newsletter_deliveries')->where('campaign_id', $campaign->id)->where('status', 'queued')->update(['status' => 'cancelled', 'updated_at' => now()]);
        $this->audit($request, 'newsletter.cancelled', 'campaign:'.$campaign->id);

        return back()->with('success', 'Kampanye dibatalkan. Email yang sudah terkirim tidak dapat ditarik.');
    }

    public function retryCampaign(Request $request, NewsletterCampaign $campaign)
    {
        $this->authorizeAdmin($request);
        $request->validate(['approved' => 'accepted']);
        abort_unless($campaign->status === 'queued',422);
        DB::table('newsletter_deliveries')->where('campaign_id',$campaign->id)->where('status','failed')->update(['status' => 'queued', 'failure_code' => null, 'claimed_at' => null, 'updated_at' => now()]);
        $this->audit($request,'newsletter.failed_deliveries_requeued','campaign:'.$campaign->id);

        return back()->with('success','Pengiriman gagal dijadwalkan ulang. Periksa log penyedia sebelum mengulang agar tidak mengirim email ganda.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\ContentAttachment;
use App\Models\ContentItem;
use App\Models\ProgramRegistration;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ContentController extends Controller
{
    public const LABELS = ['article' => 'Artikel', 'ebook' => 'Ebook', 'podcast' => 'Podcast', 'short' => 'Video pendek', 'discussion' => 'Diskusi', 'showcase' => 'Project showcase', 'gallery' => 'Galeri', 'mentorship' => 'Mentorship', 'event' => 'Event', 'bootcamp' => 'Bootcamp', 'competition' => 'Hackathon & kompetisi', 'job' => 'Karier', 'partner' => 'Partner', 'help' => 'Pusat bantuan', 'legal' => 'Kebijakan'];

    public function index(Request $request)
    {
        $kind = $request->route('kind', 'article');
        $query = ContentItem::query()->where('status', 'published')->where(function ($q) {
            $q->whereNull('published_at')->orWhere('published_at', '<=', now());
        });
        if ($kind !== 'all') {
            $query->where('kind', $kind);
        }
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->string('q').'%')->orWhere('summary', 'like', '%'.$request->string('q').'%');
            });
        }
        if ($request->filled('category')) {
            $query->whereJsonContains('categories', (string) $request->string('category'));
        }
        if ($request->filled('author')) {
            $query->where('author_id', $request->integer('author'));
        }
        $items = $query->with('author:id,name,username')->withCount(['comments' => fn ($q) => $q->where('status', 'published')])->orderByDesc('published_at')->paginate(12)->withQueryString();
        $items->through(fn ($item) => $this->summary($item));
        $categories = ContentItem::where('status', 'published')->when($kind !== 'all', fn ($q) => $q->where('kind', $kind))->pluck('categories')->flatten()->unique()->sort()->values();

        return Inertia::render('App', ['screen' => 'content-list', 'data' => ['kind' => $kind, 'title' => self::LABELS[$kind] ?? 'Jelajahi konten', 'items' => $items, 'categories' => $categories, 'authors' => DB::table('users')->whereIn('id', ContentItem::where('status', 'published')->pluck('author_id')->filter())->select('id', 'name', 'username')->get(), 'filters' => $request->only('q', 'category', 'author')]]);
    }

    public function show(Request $request, ContentItem $item)
    {
        abort_unless($item->status === 'published' && (! $item->published_at || $item->published_at->isPast()) || $request->user()?->role === 'admin', 404);
        $allowed = $this->canAccess($request, $item);
        $item->load('author:id,name,username');
        $data = $this->summary($item) + ['body' => $allowed ? $item->body : null, 'metadata' => $allowed ? $item->metadata : array_intersect_key($item->metadata ?? [], array_flip(['date', 'location', 'duration', 'level', 'stack'])), 'locked' => ! $allowed];
        $registration = $request->user() ? $item->registrations()->where('user_id', $request->user()->id)->first() : null;
        $program = in_array($item->kind, ['event', 'bootcamp', 'mentorship', 'competition', 'job']);
        $product = Schema::hasTable('products') && Schema::hasColumn('products', 'subject_id') ? DB::table('products')->where('subject_id', $item->id)->whereIn('kind', ['event', 'bootcamp', 'ebook', 'podcast', 'mentorship'])->first() : null;
        $comments = $allowed ? $item->comments()->where('status', 'published')->with('author:id,name,username')->orderBy('id')->limit(150)->get()->map(fn ($c) => ['id' => $c->id, 'user_id' => $c->user_id, 'parent_id' => $c->parent_id, 'body' => $c->body, 'depth' => $c->depth, 'author' => $c->author, 'created_at' => $c->created_at->toIso8601String()]) : [];
        if ($item->kind === 'bootcamp' && ! $registration && $request->user()?->role !== 'admin') {
            unset($data['metadata']['classroom']);
        }

        return Inertia::render('App', ['screen' => 'content-detail', 'data' => ['item' => $data, 'comments' => $comments, 'attachments' => $allowed ? $item->attachments()->get(['id', 'original_name', 'mime', 'size']) : [], 'registration' => $registration, 'isProgram' => $program, 'can_register' => $item->price === 0 || $request->user()?->role === 'admin' || ($request->user() && $this->contentEntitlement($request->user()->id, $item->id)), 'product_id' => $product?->id, 'registered_count' => $item->registrations()->where('status', 'active')->count(), 'related' => ContentItem::where('kind', $item->kind)->where('status', 'published')->where('id', '!=', $item->id)->limit(3)->get()->map(fn ($i) => $this->summary($i))]]);
    }

    public function comment(Request $request, ContentItem $item)
    {
        $this->socialAllowed($request);
        abort_unless($item->status === 'published' && $this->canAccess($request, $item), 403);
        $fields = $request->validate(['body' => 'required|string|min:2|max:4000', 'parent_id' => 'nullable|integer']);
        $parent = isset($fields['parent_id']) ? Comment::where('content_item_id', $item->id)->where('status', 'published')->findOrFail($fields['parent_id']) : null;
        if ($parent && $parent->depth >= 5) {
            throw ValidationException::withMessages(['body' => 'Balas pada tingkat diskusi sebelumnya; maksimal enam tingkat balasan.']);
        }
        $comment = $item->comments()->create(['user_id' => $request->user()->id, 'body' => $fields['body'], 'parent_id' => $parent?->id, 'depth' => $parent ? $parent->depth + 1 : 0]);
        if ($parent && $parent->user_id !== $request->user()->id) {
            DB::table('content_notifications')->insert(['user_id' => $parent->user_id, 'title' => $request->user()->name.' membalas diskusimu', 'url' => $item->public_url.'#komentar', 'created_at' => now(), 'updated_at' => now()]);
        }

        return back()->with('success', 'Komentar ditambahkan.');
    }

    public function deleteComment(Request $request, Comment $comment)
    {
        abort_unless($comment->user_id === $request->user()->id || $request->user()->role === 'admin', 403);
        $comment->update(['status' => 'removed', 'body' => '[Komentar dihapus]']);

        return back()->with('success', 'Komentar dihapus.');
    }

    public function post(Request $request)
    {
        $this->socialAllowed($request);
        $fields = $request->validate(['kind' => 'required|in:discussion,showcase', 'title' => 'required|string|min:5|max:180', 'body' => 'required|string|min:10|max:15000', 'category' => 'nullable|string|max:50', 'project_url' => 'nullable|url:http,https|max:500']);
        $item = ContentItem::create(['kind' => $fields['kind'], 'slug' => Str::slug($fields['title']).'-'.Str::lower(Str::random(7)), 'title' => $fields['title'], 'summary' => Str::limit($fields['body'], 180), 'body' => $fields['body'], 'categories' => isset($fields['category']) ? [$fields['category']] : [], 'metadata' => ['project_url' => $fields['project_url'] ?? null], 'author_id' => $request->user()->id, 'access' => 'public', 'status' => 'published', 'published_at' => now()]);

        return redirect($item->public_url)->with('success', 'Kiriman dipublikasikan.');
    }

    public function report(Request $request, ContentItem $item)
    {
        abort_unless($item->status === 'published' && $this->canAccess($request, $item), 403);
        $fields = $request->validate(['reason' => 'required|string|min:10|max:2000']);
        DB::table('content_reports')->insert(['user_id' => $request->user()->id, 'content_item_id' => $item->id, 'reason' => $fields['reason'], 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Laporan masuk antrean moderator.');
    }

    public function registerProgram(Request $request, ContentItem $item)
    {
        abort_unless($item->status === 'published' && in_array($item->kind, ['event', 'bootcamp', 'mentorship', 'competition', 'job']), 404);
        $fields = $request->validate(['slot' => 'nullable|string|max:100', 'note' => 'nullable|string|max:2000']);
        if (in_array($item->access, ['subscribers', 'purchased']) && ! $this->canAccess($request, $item)) {
            throw ValidationException::withMessages(['registration' => 'Akunmu belum memiliki akses program ini.']);
        }
        if ($item->price > 0 && ! $this->contentEntitlement($request->user()->id, $item->id) && $request->user()->role !== 'admin') {
            throw ValidationException::withMessages(['registration' => 'Program ini berbayar. Selesaikan checkout terlebih dahulu.']);
        }
        DB::transaction(function () use ($request, $item, $fields) {
            $locked = ContentItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            $registration = $locked->registrations()->where('user_id', $request->user()->id)->first();
            if ($registration?->status === 'active') {
                return;
            }
            if (isset($locked->metadata['deadline']) && now()->gt($locked->metadata['deadline'])) {
                throw ValidationException::withMessages(['registration' => 'Pendaftaran telah ditutup.']);
            }
            if ($locked->capacity && $locked->registrations()->where('status', 'active')->count() >= $locked->capacity) {
                throw ValidationException::withMessages(['registration' => 'Kuota telah penuh.']);
            }
            $slot = $fields['slot'] ?? null;
            if ($locked->kind === 'mentorship') {
                if (! $slot || ! in_array($slot, $locked->metadata['slots'] ?? [])) {
                    throw ValidationException::withMessages(['slot' => 'Pilih slot yang tersedia.']);
                }
                if ($locked->registrations()->where('slot', $slot)->where('status', 'active')->exists()) {
                    throw ValidationException::withMessages(['slot' => 'Slot ini sudah dipesan.']);
                }
            } else {
                $slot = null;
            }
            ProgramRegistration::updateOrCreate(['content_item_id' => $locked->id, 'user_id' => $request->user()->id], ['status' => 'active', 'slot' => $slot, 'metadata' => ['note' => $fields['note'] ?? null]]);
        });

        return back()->with('success', 'Pendaftaran tersimpan. Detail kegiatan tersedia di halaman ini.');
    }

    public function updateRegistration(Request $request, ProgramRegistration $registration)
    {
        abort_unless($registration->user_id === $request->user()->id, 403);
        $fields = $request->validate(['action' => 'required|in:cancel,submit', 'submission_url' => 'nullable|url:http,https|max:1000', 'submission' => 'nullable|string|max:10000']);
        if ($fields['action'] === 'cancel') {
            $registration->update(['status' => 'cancelled', 'slot' => null]);
        } else {
            abort_unless($registration->status === 'active', 422);
            if (empty($fields['submission']) && empty($fields['submission_url'])) {
                throw ValidationException::withMessages(['submission' => 'Isi uraian atau tautan hasil karya.']);
            }
            $registration->update(['metadata' => array_merge($registration->metadata ?? [], ['submission' => $fields['submission'] ?? null, 'submission_url' => $fields['submission_url'] ?? null, 'submitted_at' => now()->toIso8601String()])]);
        }

        return back()->with('success', 'Pendaftaran diperbarui.');
    }

    public function support(Request $request)
    {
        $kind = $request->route('kind', 'support');
        $tickets = $request->user() ? SupportTicket::where('user_id', $request->user()->id)->where('kind', '!=', 'whistleblowing')->latest()->limit(30)->get() : [];
        $ticket = null;
        if ($request->filled('ticket')) {
            $ticket = SupportTicket::where('user_id', $request->user()?->id)->findOrFail($request->integer('ticket'));
            abort_if($ticket->kind === 'whistleblowing', 403);
        }

        return Inertia::render('App', ['screen' => 'support', 'data' => ['kind' => $kind, 'title' => $kind === 'whistleblowing' ? 'Whistleblowing' : 'Kami siap membantu', 'tickets' => $tickets, 'ticket' => $ticket, 'receipt' => session('receipt')]]);
    }

    public function createTicket(Request $request)
    {
        $fields = $request->validate(['kind' => 'required|in:support,partner,whistleblowing', 'subject' => 'required|string|min:5|max:180', 'body' => 'required|string|min:20|max:12000', 'anonymous' => 'nullable|boolean']);
        if ($fields['kind'] !== 'whistleblowing') {
            abort_unless($request->user(), 401);
        }
        $ticket = SupportTicket::create(['reference' => 'TN-'.strtoupper(Str::random(10)), 'user_id' => $fields['kind'] === 'whistleblowing' && ($fields['anonymous'] ?? false) ? null : $request->user()?->id, 'kind' => $fields['kind'], 'subject' => $fields['subject'], 'body' => $fields['body'], 'messages' => []]);

        return back()->with('receipt', $ticket->reference)->with('success', 'Laporan tersimpan dengan referensi '.$ticket->reference.'.');
    }

    public function replyTicket(Request $request, SupportTicket $ticket)
    {
        abort_unless($ticket->kind !== 'whistleblowing' && ($ticket->user_id === $request->user()->id || $request->user()->role === 'admin'), 403);
        $fields = $request->validate(['body' => 'required|string|min:2|max:6000']);
        DB::transaction(function () use ($ticket, $request, $fields) {
            $locked = SupportTicket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $messages = $locked->messages ?? [];
            $messages[] = ['author' => $request->user()->role === 'admin' ? 'Tim Teras Ngulik' : $request->user()->name, 'body' => $fields['body'], 'at' => now()->toIso8601String()];
            $locked->update(['messages' => $messages, 'status' => $request->user()->role === 'admin' ? 'answered' : 'open']);
        });

        return back()->with('success', 'Balasan tersimpan.');
    }

    public function newsletter(Request $request)
    {
        $fields = $request->validate(['email' => 'required|email|max:254', 'consent' => 'accepted']);
        $email = strtolower($fields['email']);
        $existing = DB::table('newsletter_subscriptions')->where('email', $email)->first();
        $owner = $request->user()?->email === $email && (bool) $request->user()?->email_verified_at;
        if ($existing?->status === 'subscribed' && ! $owner) {
            return back()->with('success', 'Jika alamat dapat didaftarkan, tautan konfirmasi tersedia melalui email. Klik tautan untuk menyelesaikan persetujuan newsletter.');
        }
        $token = $existing?->unsubscribe_token ? Crypt::decryptString($existing->unsubscribe_token) : Str::random(48);
        $confirmation = $owner ? null : Str::random(48);
        DB::table('newsletter_subscriptions')->updateOrInsert(['email' => $email], ['user_id' => $request->user()?->email === $email ? $request->user()->id : null, 'status' => $owner ? 'subscribed' : 'pending', 'consent_at' => now(), 'confirmed_at' => $owner ? now() : null, 'confirmation_hash' => $confirmation ? hash('sha256', $confirmation) : null, 'unsubscribed_at' => null, 'unsubscribe_hash' => hash('sha256', $token), 'unsubscribe_token' => Crypt::encryptString($token), 'created_at' => $existing?->created_at ?? now(), 'updated_at' => now()]);
        $url = url('/newsletter/unsubscribe/'.$token);
        $body = $owner ? "Terima kasih telah berlangganan kabar Teras Ngulik. Berhenti berlangganan: {$url}" : 'Konfirmasikan persetujuan newsletter Teras Ngulik: '.url('/newsletter/confirm/'.$confirmation)."\nJika bukan kamu yang meminta, abaikan pesan ini. Kamu tidak akan menerima kampanye sampai mengonfirmasi.\nBatalkan: {$url}";
        try {
            Mail::raw($body, fn ($m) => $m->to($email)->subject($owner ? 'Preferensi newsletter Teras Ngulik' : 'Konfirmasi newsletter Teras Ngulik'));
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', $owner ? 'Persetujuan newsletter tersimpan. Kamu dapat berhenti lewat tautan email atau pengaturan akun.' : 'Jika alamat dapat didaftarkan, tautan konfirmasi tersedia melalui email. Klik tautan untuk menyelesaikan persetujuan newsletter.');
    }

    public function confirmNewsletter(Request $request, string $token)
    {
        $subscription = DB::table('newsletter_subscriptions')->where('confirmation_hash', hash('sha256', $token))->where('status', 'pending')->first();
        abort_unless($subscription, 404);
        abort_if(now()->gt(Carbon::parse($subscription->updated_at)->addDay()), 410, 'Tautan konfirmasi kedaluwarsa. Daftar kembali untuk meminta tautan baru.');
        if ($request->isMethod('post')) {
            DB::table('newsletter_subscriptions')->where('id', $subscription->id)->update(['status' => 'subscribed', 'confirmation_hash' => null, 'confirmed_at' => now(), 'updated_at' => now()]);

            return Inertia::render('App', ['screen' => 'static', 'data' => ['title' => 'Selamat datang di kabar Teras', 'body' => 'Persetujuan newsletter telah dikonfirmasi. Kamu dapat berhenti kapan saja melalui tautan di setiap email.']]);
        }

        return Inertia::render('App', ['screen' => 'static', 'data' => ['title' => 'Konfirmasi newsletter', 'body' => 'Konfirmasikan bahwa kamu ingin menerima kabar belajar dan promosi Teras Ngulik.', 'confirmation_url' => $request->url()]]);
    }

    public function unsubscribe(Request $request, string $token)
    {
        $subscription = DB::table('newsletter_subscriptions')->where('unsubscribe_hash', hash('sha256', $token))->first();
        abort_unless($subscription, 404);
        if ($request->isMethod('post')) {
            DB::table('newsletter_subscriptions')->where('id', $subscription->id)->update(['status' => 'unsubscribed', 'confirmation_hash' => null, 'unsubscribed_at' => now(), 'updated_at' => now()]);

            return Inertia::render('App', ['screen' => 'static', 'data' => ['title' => 'Newsletter dihentikan', 'body' => 'Kamu tidak akan menerima newsletter promosi lagi. Email keamanan akun dan transaksi tetap dikirim sesuai kebutuhan.']]);
        }

        return Inertia::render('App', ['screen' => 'static', 'data' => ['title' => 'Berhenti berlangganan newsletter', 'body' => 'Konfirmasikan untuk menghentikan email promosi Teras Ngulik.', 'unsubscribe_url' => $request->url()]]);
    }

    public function newsletterPreference(Request $request)
    {
        $fields = $request->validate(['subscribed' => 'required|boolean']);
        if (! $fields['subscribed']) {
            DB::table('newsletter_subscriptions')->where('email', strtolower($request->user()->email))->update(['status' => 'unsubscribed', 'unsubscribed_at' => now(), 'updated_at' => now()]);
        } else {
            $request->merge(['email' => $request->user()->email, 'consent' => true]);

            return $this->newsletter($request);
        }

        return back()->with('success', 'Preferensi newsletter diperbarui.');
    }

    public function notifications(Request $request)
    {
        return Inertia::render('App', ['screen' => 'notifications', 'data' => ['items' => DB::table('content_notifications')->where('user_id', $request->user()->id)->latest()->paginate(30)]]);
    }

    public function readNotifications(Request $request)
    {
        DB::table('content_notifications')->where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'Notifikasi ditandai dibaca.');
    }

    public function api(Request $request)
    {
        $request->validate(['kind' => 'nullable|in:article,ebook,podcast,short,gallery,discussion,showcase,event,bootcamp,mentorship,competition,job,partner,help,legal', 'q' => 'nullable|string|max:100']);
        $q = ContentItem::where('status', 'published')->where('access', 'public')->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
        if ($request->filled('kind')) {
            $q->where('kind', $request->input('kind'));
        }
        if ($request->filled('q')) {
            $q->where('title', 'like', '%'.$request->input('q').'%');
        }

        return response()->json($q->latest()->paginate(20)->through(fn ($item) => $this->summary($item)));
    }

    public function documentation()
    {
        return Inertia::render('App', ['screen' => 'documentation', 'data' => ['title' => 'Dokumentasi Teras Ngulik']]);
    }

    public function downloadAttachment(Request $request, ContentItem $item, ContentAttachment $attachment)
    {
        abort_unless($attachment->content_item_id === $item->id, 404);
        abort_unless(($item->status === 'published' && (! $item->published_at || $item->published_at->isPast()) && $this->canAccess($request, $item)) || $request->user()?->role === 'admin', 403);
        abort_unless(Storage::disk('local')->exists($attachment->getRawOriginal('path')), 404);

        return Storage::disk('local')->download($attachment->getRawOriginal('path'), $attachment->original_name, ['X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "default-src 'none'; sandbox"]);
    }

    public function search(Request $request)
    {
        $fields = $request->validate(['q' => 'nullable|string|max:150']);
        $term = trim($fields['q'] ?? '');
        $courses = DB::table('courses')->where('status', 'published')->when($term !== '', fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', '%'.$term.'%')->orWhere('summary', 'like', '%'.$term.'%')))->limit(20)->get(['id', 'slug', 'title', 'summary', 'access', 'price']);
        $items = ContentItem::where('status', 'published')->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))->when($term !== '', fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', '%'.$term.'%')->orWhere('summary', 'like', '%'.$term.'%')))->whereIn('access', $request->user()?->email_verified_at ? ['public', 'registered'] : ['public'])->with('author:id,name,username')->limit(30)->get()->map(fn ($i) => $this->summary($i));

        return Inertia::render('App', ['screen' => 'search', 'data' => ['title' => 'Temukan rasa penasaranmu', 'q' => $term, 'courses' => $courses, 'items' => $items]]);
    }

    public function myPrograms(Request $request)
    {
        $registrations = ProgramRegistration::where('user_id', $request->user()->id)->with('item')->latest()->paginate(20);

        return Inertia::render('App', ['screen' => 'my-programs', 'data' => ['title' => 'Bootcamp & program saya', 'registrations' => $registrations]]);
    }

    public function staticPage(Request $request)
    {
        $slug = $request->route('slug', 'tentang');
        $item = ContentItem::where('kind', 'legal')->where('slug', $slug)->where('status', 'published')->first();
        abort_unless($item, 404);

        return Inertia::render('App', ['screen' => 'static', 'data' => ['title' => $item->title, 'body' => $item->body]]);
    }

    public function canAccess(Request $request, ContentItem $item): bool
    {
        if ($request->user()?->role === 'admin') {
            return true;
        }
        if ($item->access === 'public') {
            return true;
        }
        if (! $request->user()) {
            return false;
        }
        if ($item->access === 'registered') {
            return (bool) $request->user()->email_verified_at;
        }
        if ($item->access === 'cohort') {
            return $item->registrations()->where('user_id', $request->user()->id)->where('status', 'active')->exists();
        }
        if ($item->access === 'subscribers' && Schema::hasTable('entitlements')) {
            return DB::table('entitlements')->where('user_id', $request->user()->id)->where('kind', 'subscription')->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->exists();
        }

        return $this->contentEntitlement($request->user()->id, $item->id);
    }

    private function contentEntitlement(int $userId, int $itemId): bool
    {
        return Schema::hasTable('entitlements') && Schema::hasColumn('entitlements', 'subject_id') && DB::table('entitlements')->where('user_id', $userId)->where('kind', 'content')->where('subject_id', $itemId)->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->exists();
    }

    private function socialAllowed(Request $request): void
    {
        abort_unless($request->user()?->email_verified_at, 403, 'Verifikasi email sebelum berpartisipasi.');
        abort_if(in_array($request->user()->age_band, ['child', 'sd', 'smp', 'under13', 'under18']), 403, 'Akun anak belum dapat mempublikasikan konten atau komentar di komunitas publik.');
    }

    public function summary(ContentItem $item): array
    {
        return ['id' => $item->id, 'kind' => $item->kind, 'slug' => $item->slug, 'title' => $item->title, 'summary' => $item->summary, 'categories' => $item->categories ?? [], 'author' => $item->relationLoaded('author') ? $item->author?->only('id','name','username') : null, 'access' => $item->access, 'price' => $item->price, 'capacity' => $item->capacity, 'status' => $item->status, 'url' => $item->public_url, 'published_at' => $item->published_at?->toIso8601String(), 'comments_count' => $item->comments_count ?? 0];
    }
}

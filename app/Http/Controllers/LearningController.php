<?php

namespace App\Http\Controllers;

use App\Models\Academy;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\NotebookEntry;
use App\Models\Project;
use App\Models\QuizAttempt;
use App\Models\ReviewCard;
use App\Models\User;
use App\Services\LearningService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LearningController extends Controller
{
    public function __construct(private LearningService $learning) {}

    private function page(string $screen, array $data = [])
    {
        return Inertia::render('App', ['screen' => $screen, 'data' => $data]);
    }

    private function adminGuard(Request $request): void
    {
        abort_unless(in_array($request->user()?->role, ['admin', 'superadmin', 'instructor']), 403);
    }

    private function courseGuard(Request $request, Course $course): void
    {
        $this->adminGuard($request);
        abort_unless(in_array($request->user()->role, ['admin', 'superadmin']) || $course->instructor_id === $request->user()->id, 403);
    }

    private function ok(Request $request, string $message, array $data = [])
    {
        return $request->expectsJson() ? response()->json(['message' => $message] + $data) : back()->with('success', $message);
    }

    public function courses(Request $r)
    {
        $query = Course::with('academy')->withCount('lessons')->where('status', 'published');
        if ($r->filled('q')) {
            $query->where(fn ($q) => $q->where('title', 'like', '%'.$r->string('q').'%')->orWhere('summary', 'like', '%'.$r->string('q').'%'));
        }
        if ($r->filled('academy')) {
            $query->whereHas('academy', fn ($q) => $q->where('slug', $r->string('academy')));
        }
        if (in_array($r->input('access'), ['free', 'purchase', 'subscription'])) {
            $query->where('access', $r->input('access'));
        }
        if ($r->filled('level')) {
            $query->where('level', $r->string('level'));
        }

        return $this->page('courses', ['courses' => $query->latest()->paginate(12)->withQueryString(), 'academies' => Academy::all(), 'filters' => $r->only('q', 'academy', 'access', 'level')]);
    }

    public function academies()
    {
        return $this->page('academies', ['academies' => Academy::withCount(['courses' => fn ($q) => $q->where('status', 'published')])->get()]);
    }

    public function academy(string $slug)
    {
        $academy = Academy::where('slug', $slug)->firstOrFail();

        return $this->page('academy', ['academy' => $academy, 'courses' => $academy->courses()->where('status', 'published')->withCount('lessons')->get()]);
    }

    public function course(Request $r, Course $course)
    {
        abort_unless($course->status === 'published' || in_array($r->user()?->role, ['admin', 'superadmin']) || ($r->user() && $course->instructor_id === $r->user()->id), 404);
        $course->load('academy');
        $lessons = $course->lessons()->get(['id', 'course_id', 'title', 'slug', 'kind', 'position', 'module_title', 'is_preview', 'required']);

        return $this->page('course', ['course' => $course, 'lessons' => $lessons, 'hasAccess' => $this->learning->hasAccess($r->user(), $course), 'enrollment' => $r->user() ? Enrollment::where('user_id', $r->user()->id)->where('course_id', $course->id)->first() : null, 'progress' => $r->user() ? LessonProgress::where('user_id', $r->user()->id)->whereIn('lesson_id', $lessons->pluck('id'))->get() : []]);
    }

    public function lesson(Request $r, Course $course, Lesson $lesson)
    {
        abort_unless($lesson->course_id === $course->id, 404);
        $this->learning->authorizeLesson($r->user(), $lesson);
        if ($r->user() && $this->learning->hasAccess($r->user(), $course)) {
            $enrollment = $this->learning->enrollment($r->user(), $course);
            $enrollment->update(['last_lesson_id' => $lesson->id]);
        }
        $lesson->content = $this->learning->sanitizedContent($lesson);
        $quizSession = null;
        if (in_array($lesson->kind, ['quiz', 'lab']) && (isset($lesson->content['time_limit_seconds']) || isset($lesson->content['max_attempts']))) {
            $active = $r->user() ? DB::table('quiz_sessions')->where('user_id', $r->user()->id)->where('lesson_id', $lesson->id)->where('status', 'active')->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->latest('id')->first() : null;
            if ($active) {
                $quizSession = ['token' => $active->token, 'expires_at' => $active->expires_at ? Carbon::parse($active->expires_at)->toIso8601String() : null, 'server_time' => now()->toIso8601String()];
            } else {
                $content = $lesson->content;
                unset($content['questions']);
                $lesson->content = $content;
            }
        }

        $revision = DB::table('scrim_revisions')->where('lesson_id', $lesson->id)->whereNotNull('published_at')->orderByDesc('version')->first();
        if ($revision) {
            $content = $lesson->content;
            $content['scrim'] = json_decode($revision->manifest, true);
            $content['revision_id'] = $revision->id;
            if ($revision->audio_path) {
                $content['audio_url'] = '/lessons/'.$lesson->id.'/scrim-audio/'.$revision->id;
            } $lesson->content = $content;
        }

        return $this->page('lesson', ['quizSession' => $quizSession, 'course' => $course, 'lesson' => $lesson, 'lessons' => $course->lessons()->get(['id', 'title', 'slug', 'kind', 'position', 'module_title', 'is_preview']), 'progress' => $r->user() ? LessonProgress::where('user_id', $r->user()->id)->where('lesson_id', $lesson->id)->first() : null, 'attempt' => $r->user() ? QuizAttempt::where('user_id', $r->user()->id)->where('lesson_id', $lesson->id)->latest()->first() : null, 'assignment' => $r->user() ? DB::table('learning_assignments')->where('user_id', $r->user()->id)->where('lesson_id', $lesson->id)->first() : null]);
    }

    public function enroll(Request $r, Course $course)
    {
        $this->learning->enrollment($r->user(), $course);

        return redirect('/belajar/course/'.$course->slug.'/lesson/'.$course->lessons()->firstOrFail()->slug)->with('success', 'Kelas ditambahkan. Selamat mengulik!');
    }

    public function progress(Request $r, Lesson $lesson)
    {
        $this->learning->authorizeLesson($r->user(), $lesson);
        $this->learning->enrollment($r->user(), $lesson->course);
        $data = $r->validate(['position_ms' => 'required|integer|min:0|max:86400000', 'scroll_y' => 'sometimes|integer|min:0|max:1000000', 'code_snapshot' => 'nullable|string|max:200000']);
        LessonProgress::updateOrCreate(['user_id' => $r->user()->id, 'lesson_id' => $lesson->id], $data);

        return $this->ok($r, 'Posisi belajar tersimpan.');
    }

    public function complete(Request $r, Lesson $lesson)
    {
        $this->learning->authorizeLesson($r->user(), $lesson);
        abort_if(in_array($lesson->kind, ['quiz', 'assignment', 'lab']), 422, 'Materi ini diselesaikan melalui evaluasi, bukan tombol selesai.');
        $cert = $this->learning->complete($r->user(), $lesson);

        return $this->ok($r, $cert ? 'Selamat! Kamu lulus kelas dan sertifikatmu terbit.' : 'Materi selesai. Lanjutkan mengulik!');
    }

    private function grade(array $questions, array $answers): array
    {
        $right = 0;
        $feedback = [];
        foreach ($questions as $i => $q) {
            $expected = (array) ($q['correct'] ?? []);
            $answer = (array) ($answers[$i] ?? []);
            sort($expected);
            sort($answer);
            $valid = $answer === $expected;
            if ($valid) {
                $right++;
            }
            $feedback[] = ['correct' => $valid, 'explanation' => $q['explanation'] ?? 'Tinjau kembali konsep pada materi.'];
        }

        return ['score' => (int) round(100 * $right / max(1, count($questions))), 'feedback' => $feedback];
    }

    public function startQuiz(Request $r, Lesson $lesson)
    {
        $this->learning->authorizeLesson($r->user(), $lesson);
        abort_unless(in_array($lesson->kind, ['quiz', 'lab']), 422);
        $this->learning->enrollment($r->user(), $lesson->course);
        $session = DB::transaction(function () use ($r, $lesson) {
            Enrollment::where('user_id', $r->user()->id)->where('course_id', $lesson->course_id)->lockForUpdate()->firstOrFail();
            $active = DB::table('quiz_sessions')->where('user_id', $r->user()->id)->where('lesson_id', $lesson->id)->where('status', 'active')->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->latest('id')->first();
            if ($active) {
                return $active;
            }
            $used = DB::table('quiz_sessions')->where('user_id', $r->user()->id)->where('lesson_id', $lesson->id)->count() + QuizAttempt::where('user_id', $r->user()->id)->where('lesson_id', $lesson->id)->whereNull('session_token')->count();
            $max = $lesson->content['max_attempts'] ?? null;
            abort_if($max && $used >= $max, 422, 'Batas percobaan evaluasi sudah tercapai. Hubungi pengajar.');
            DB::table('quiz_sessions')->where('user_id', $r->user()->id)->where('lesson_id', $lesson->id)->where('status', 'active')->update(['status' => 'expired', 'updated_at' => now()]);
            $seconds = $lesson->content['time_limit_seconds'] ?? null;
            $id = DB::table('quiz_sessions')->insertGetId(['user_id' => $r->user()->id, 'lesson_id' => $lesson->id, 'token' => (string) Str::uuid(), 'started_at' => now(), 'expires_at' => $seconds ? now()->addSeconds($seconds) : null, 'created_at' => now(), 'updated_at' => now()]);

            return DB::table('quiz_sessions')->where('id', $id)->first();
        });

        return $this->ok($r, 'Evaluasi dimulai. Waktu dihitung oleh server.', ['session' => ['token' => $session->token, 'started_at' => $session->started_at, 'expires_at' => $session->expires_at ? Carbon::parse($session->expires_at)->toIso8601String() : null], 'questions' => $this->learning->sanitizedContent($lesson)['questions'] ?? [], 'server_time' => now()->toIso8601String()]);
    }

    public function quiz(Request $r, Lesson $lesson)
    {
        $this->learning->authorizeLesson($r->user(), $lesson);
        abort_unless(in_array($lesson->kind, ['quiz', 'lab']), 422);
        $this->learning->enrollment($r->user(), $lesson->course);
        $r->validate(['answers' => 'required|array|max:100', 'answers.*' => 'nullable|array|max:10', 'answers.*.*' => 'integer|min:0|max:20', 'session_token' => 'nullable|uuid']);
        $result = DB::transaction(function () use ($r, $lesson) {
            Enrollment::where('user_id', $r->user()->id)->where('course_id', $lesson->course_id)->lockForUpdate()->firstOrFail();
            $policy = isset($lesson->content['time_limit_seconds']) || isset($lesson->content['max_attempts']);
            $session = null;
            if ($policy) {
                $session = DB::table('quiz_sessions')->where('user_id', $r->user()->id)->where('lesson_id', $lesson->id)->where('token', $r->input('session_token'))->lockForUpdate()->first();
                abort_unless($session && $session->status === 'active', 422, 'Mulai evaluasi untuk mendapatkan sesi yang sah.');
                abort_if($session->expires_at && now()->greaterThanOrEqualTo($session->expires_at), 422, 'Waktu evaluasi telah habis. Jawaban tidak dinilai.');
            }
            $result = $this->grade($lesson->content['questions'] ?? [], $r->input('answers'));
            $passed = $result['score'] >= ($lesson->content['pass_score'] ?? $lesson->course->pass_score);
            QuizAttempt::create(['user_id' => $r->user()->id, 'lesson_id' => $lesson->id, 'answers' => $r->input('answers'), 'score' => $result['score'], 'passed' => $passed, 'session_token' => $session?->token]);
            if ($session) {
                DB::table('quiz_sessions')->where('id', $session->id)->update(['status' => 'submitted', 'updated_at' => now()]);
            }
            if ($passed) {
                $this->learning->complete($r->user(), $lesson);
            }

return $result + ['passed' => $passed];
        });

        return $this->ok($r, $result['passed'] ? 'Evaluasi lulus.' : 'Belum lulus. Pelajari penjelasan dan coba lagi.', $result);
    }

    public function assignment(Request $r, Lesson $lesson)
    {
        $this->learning->authorizeLesson($r->user(), $lesson);
        abort_unless($lesson->kind === 'assignment', 422);
        $this->learning->enrollment($r->user(), $lesson->course);
        $data = $r->validate(['body' => 'required|string|min:20|max:20000', 'repository_url' => 'nullable|url:http,https|max:1000', 'attachment' => 'nullable|file|max:10240|mimes:pdf,zip,txt,csv,json']);
        unset($data['attachment']);
        $old = DB::table('learning_assignments')->where('user_id', $r->user()->id)->where('lesson_id', $lesson->id)->first();
        if ($r->hasFile('attachment')) {
            $data['file_path'] = $r->file('attachment')->store('assignment-uploads', 'local');
            $data['file_name'] = preg_replace('/[^a-zA-Z0-9_. -]/', '_', basename($r->file('attachment')->getClientOriginalName()));
        }
        DB::table('learning_assignments')->updateOrInsert(['user_id' => $r->user()->id, 'lesson_id' => $lesson->id], $data + ['status' => 'submitted', 'score' => null, 'feedback' => null, 'created_at' => $old?->created_at ?? now(), 'updated_at' => now()]);
        if (isset($data['file_path']) && $old?->file_path) {
            Storage::disk('local')->delete($old->file_path);
        }

        return $this->ok($r, 'Tugas terkirim untuk dinilai pengajar.');
    }

    public function assignmentFile(Request $r, int $id)
    {
        $assignment = DB::table('learning_assignments')->where('id', $id)->firstOrFail();
        $lesson = Lesson::findOrFail($assignment->lesson_id);
        if ($assignment->user_id !== $r->user()->id) {
            $this->courseGuard($r, $lesson->course);
        }
        abort_unless($assignment->file_path && Storage::disk('local')->exists($assignment->file_path), 404);

        return Storage::disk('local')->download($assignment->file_path, $assignment->file_name ?? 'assignment-file', ['Content-Type' => 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function playground(Request $r)
    {
        $project = $r->filled('project') ? Project::findOrFail($r->integer('project')) : null;
        if ($project) {
            abort_unless($project->user_id === $r->user()?->id, 403);
        }

        return $this->page('playground', ['project' => $project, 'runtimeStatus' => ['html' => 'ready', 'javascript' => 'ready', 'python' => 'browser-network', 'sql' => 'browser-network', 'r' => 'browser-network']]);
    }

    public function projects(Request $r)
    {
        return $this->page('projects', ['projects' => Project::where('user_id', $r->user()->id)->latest()->get()]);
    }

    private function projectData(Request $r): array
    {
        $data = $r->validate(['title' => 'required|string|max:120', 'runtime' => 'required|in:html,javascript,python,sql,r', 'files' => 'required|array|min:1|max:10', 'files.*.path' => 'required|string|max:120', 'files.*.content' => 'required|string|max:200000', 'visibility' => 'sometimes|in:private,public', 'source_lesson_id' => 'nullable|integer', 'source_time_ms' => 'nullable|integer|min:0']);
        foreach ($data['files'] as $file) {
            abort_if(str_contains($file['path'], '..') || ! preg_match('/^[a-zA-Z0-9_\/. -]+$/', $file['path']), 422, 'Nama file tidak aman.');
        }
        abort_if(strlen(json_encode($data['files'])) > 500000, 422, 'Proyek maksimal 500 KB.');
        if (in_array($r->user()->age_band ?? 'umum', ['anak', 'child', 'sd', 'smp'])) {
            $data['visibility'] = 'private';
        }
        if (! empty($data['source_lesson_id'])) {
            $lesson = Lesson::findOrFail($data['source_lesson_id']);
            $this->learning->authorizeLesson($r->user(), $lesson);
        }

        return $data;
    }

    public function saveProject(Request $r)
    {
        $data = $this->projectData($r);
        $pro = DB::table('entitlements')->where('user_id', $r->user()->id)->where('kind', 'subscription')->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->exists();
        abort_if(Project::where('user_id', $r->user()->id)->count() >= ($pro ? 50 : 5), 422, $pro ? 'Batas 50 proyek tercapai.' : 'Paket Free menyimpan 5 proyek. Unduh kode atau hapus proyek lama.');
        $project = Project::create($data + ['user_id' => $r->user()->id, 'share_slug' => ($data['visibility'] ?? 'private') === 'public' ? Str::random(24) : null, 'versions' => []]);

        return $this->ok($r, 'Proyek tersimpan.', ['project' => $project]);
    }

    public function updateProject(Request $r, Project $project)
    {
        abort_unless($project->user_id === $r->user()->id, 403);
        $data = $this->projectData($r);
        $versions = $project->versions ?? [];
        $pro = DB::table('entitlements')->where('user_id', $r->user()->id)->where('kind', 'subscription')->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->exists();
        if ($pro) {
            $versions[] = ['saved_at' => now()->toIso8601String(), 'files' => $project->files];
            $versions = array_slice($versions, -20);
        }
        $project->update($data + ['versions' => $versions, 'share_slug' => ($data['visibility'] ?? $project->visibility) === 'public' ? ($project->share_slug ?: Str::random(24)) : null]);

        return $this->ok($r, 'Perubahan proyek tersimpan.', ['project' => $project]);
    }

    public function deleteProject(Request $r, Project $project)
    {
        abort_unless($project->user_id === $r->user()->id, 403);
        $project->delete();

        return $this->ok($r, 'Proyek dihapus.');
    }

    public function sharedProject(string $slug)
    {
        $project = Project::where('share_slug', $slug)->where('visibility', 'public')->firstOrFail();

        return $this->page('playground', ['project' => $project, 'readOnly' => true]);
    }

    public function requestReview(Request $r, Project $project)
    {
        abort_unless($project->user_id === $r->user()->id, 403);
        $project->update(['review_status' => 'requested']);

        return $this->ok($r, 'Permintaan review terkirim ke pengajar.');
    }

    public function codeReviews(Request $r)
    {
        return $this->page('code-reviews', ['projects' => $r->user() ? Project::where('user_id', $r->user()->id)->whereIn('review_status', ['requested', 'reviewed'])->get() : []]);
    }

    public function notebook(Request $r)
    {
        return $this->page('notebook', ['entries' => NotebookEntry::where('user_id', $r->user()->id)->when($r->filled('q'), fn ($q) => $q->where('body', 'like', '%'.$r->string('q').'%'))->latest()->get()]);
    }

    public function saveNote(Request $r)
    {
        $data = $r->validate(['title' => 'required|string|max:160', 'body' => 'required|string|max:50000', 'tags' => 'nullable|string|max:300', 'lesson_id' => 'nullable|exists:lessons,id', 'position_ms' => 'nullable|integer|min:0|max:86400000', 'is_bookmark' => 'sometimes|boolean']);
        if (! empty($data['lesson_id'])) {
            $this->learning->authorizeLesson($r->user(), Lesson::findOrFail($data['lesson_id']));
        }
        NotebookEntry::create($data + ['user_id' => $r->user()->id]);

        return $this->ok($r, 'Catatan tersimpan.');
    }

    public function deleteNote(Request $r, NotebookEntry $entry)
    {
        abort_unless($entry->user_id === $r->user()->id, 403);
        $entry->delete();

        return $this->ok($r, 'Catatan dihapus.');
    }

    public function exportNotes(Request $r)
    {
        $text = NotebookEntry::where('user_id', $r->user()->id)->get()->map(fn ($n) => '# '.$n->title."\n\n".$n->body."\n\n---\n")->join("\n");

        return response($text, 200, ['Content-Type' => 'text/markdown; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="teras-ngulik-notebook.md"']);
    }

    public function review(Request $r)
    {
        return $this->page('review', ['cards' => ReviewCard::where('user_id', $r->user()->id)->where('due_at', '<=', now())->orderBy('due_at')->limit(30)->get(), 'total' => ReviewCard::where('user_id', $r->user()->id)->count()]);
    }

    public function saveReviewCard(Request $r)
    {
        $data = $r->validate(['front' => 'required|string|max:1000', 'back' => 'required|string|max:3000']);
        ReviewCard::create($data + ['user_id' => $r->user()->id, 'due_at' => now()]);

        return $this->ok($r, 'Kartu review ditambahkan.');
    }

    public function rateReviewCard(Request $r, ReviewCard $card)
    {
        abort_unless($card->user_id === $r->user()->id, 403);
        $r->validate(['rating' => 'required|in:again,hard,good,easy']);
        DB::transaction(function () use ($r, $card) {
            $card = ReviewCard::whereKey($card->id)->lockForUpdate()->firstOrFail();
            abort_if($card->due_at->isFuture(), 422, 'Kartu ini belum waktunya direview.');
            $days = match ($r->input('rating')) {
                'again' => 0,'hard' => max(1, (int) round(max(1, $card->interval_days) * 1.2)),'good' => max(1, (int) round(max(1, $card->interval_days) * 2.5)),'easy' => max(4, (int) round(max(1, $card->interval_days) * 3.5))
            };
            $due = $days ? now()->addDays($days) : now()->addMinutes(10);
            $card->update(['interval_days' => $days, 'repetitions' => $card->repetitions + 1, 'due_at' => $due]);
            DB::table('review_history')->insert(['review_card_id' => $card->id, 'rating' => $r->input('rating'), 'reviewed_at' => now(), 'next_due_at' => $due]);
        });

        return $this->ok($r, 'Jadwal review diperbarui.');
    }

    public function certificates(Request $r)
    {
        return $this->page('certificates', ['certificates' => Certificate::with('course')->where('user_id', $r->user()->id)->latest()->get()]);
    }

    public function certificate(string $credential)
    {
        $cert = Certificate::with('course', 'user')->where('credential_id', $credential)->firstOrFail();

        return $this->page('certificate', ['certificate' => ['credential_id' => $cert->credential_id, 'name' => in_array($cert->user->age_band ?? 'umum', ['anak', 'child', 'sd', 'smp']) ? Str::substr($cert->user->name, 0, 1).'.' : $cert->user->name, 'title' => $cert->course->title, 'issued_at' => $cert->issued_at->toDateString(), 'revoked' => (bool) $cert->revoked_at, 'url' => url('/sertifikat/'.$cert->credential_id)]]);
    }

    public function paths(Request $r)
    {
        $paths = DB::table('learning_paths')->get()->map(function ($path) use ($r) {
            $ids = json_decode($path->course_ids, true);
            $completed = $r->user() ? Enrollment::where('user_id', $r->user()->id)->whereIn('course_id', $ids)->whereNotNull('completed_at')->pluck('course_id')->all() : [];
            $courses = Course::whereIn('id', $ids)->where('status', 'published')->get()->keyBy('id');
            $path->courses = collect($ids)->filter(fn ($id) => isset($courses[$id]))->map(function ($id) use ($courses, $ids, $completed) {
                $course = $courses[$id]->toArray();
                $preceding = array_slice($ids, 0, array_search($id, $ids, true));
                $course['path_locked'] = count(array_diff($preceding, $completed)) > 0;
                $course['path_completed'] = in_array($id, $completed);

                return $course;
            })->values();
            $path->completed = count($completed);
            $path->enrolled = $r->user() ? DB::table('learning_path_enrollments')->where('user_id', $r->user()->id)->where('learning_path_id', $path->id)->exists() : false;

            return $path;
        });

        return $this->page('learning-paths', ['paths' => $paths]);
    }

    public function enrollPath(Request $r, string $slug)
    {
        $path = DB::table('learning_paths')->where('slug', $slug)->firstOrFail();
        DB::table('learning_path_enrollments')->insertOrIgnore(['user_id' => $r->user()->id, 'learning_path_id' => $path->id, 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Learning path diikuti. Selesaikan kelas sesuai urutan jalur.');
    }

    public function launchPathCourse(Request $r, string $slug, Course $course)
    {
        $path = DB::table('learning_paths')->where('slug', $slug)->firstOrFail();
        $ids = json_decode($path->course_ids, true);
        $position = array_search($course->id, $ids, true);
        abort_unless($position !== false, 404);
        abort_unless(DB::table('learning_path_enrollments')->where('user_id', $r->user()->id)->where('learning_path_id', $path->id)->exists(), 422, 'Ikuti learning path sebelum memulai langkah.');
        $required = array_slice($ids, 0, $position);
        $done = Enrollment::where('user_id', $r->user()->id)->whereIn('course_id', $required)->whereNotNull('completed_at')->count();
        abort_unless($done === count($required), 422, 'Selesaikan kelas sebelumnya sebelum melanjutkan jalur ini.');

        return redirect('/belajar/course/'.$course->slug);
    }

    public function savePath(Request $r)
    {
        $this->adminGuard($r);
        abort_unless(in_array($r->user()->role, ['admin', 'superadmin']), 403);
        $data = $r->validate(['title' => 'required|string|max:200', 'slug' => 'required|regex:/^[a-z0-9-]+$/|max:200', 'academy_id' => 'required|exists:academies,id', 'description' => 'required|string|max:3000', 'course_ids' => 'required|array|min:1|max:50', 'course_ids.*' => 'required|integer|distinct|exists:courses,id']);
        DB::table('learning_paths')->updateOrInsert(['slug' => $data['slug']], ['title' => $data['title'], 'academy_id' => $data['academy_id'], 'description' => $data['description'], 'course_ids' => json_encode($data['course_ids']), 'created_at' => now(), 'updated_at' => now()]);

        return $this->ok($r, 'Urutan learning path disimpan.');
    }

    public function games()
    {
        return $this->page('games');
    }

    public function labs()
    {
        return $this->page('labs');
    }

    public function challenges()
    {
        return $this->page('challenges');
    }

    public function diagnostic()
    {
        return $this->page('diagnostic', ['academies' => Academy::all()]);
    }

    public function saveDiagnostic(Request $r)
    {
        $data = $r->validate(['academy_id' => 'required|exists:academies,id', 'answers' => 'required|array|size:3', 'answers.*' => 'required|integer|min:0|max:3']);
        $score = 0;
        foreach ([1, 2, 0] as $i => $correct) {
            if (($data['answers'][$i] ?? null) === $correct) {
                $score += 1;
            }
        }
        $recommendation = $score === 3 ? 'Menengah' : 'Pemula';
        DB::table('learning_diagnostics')->insert(['user_id' => $r->user()->id, 'academy_id' => $data['academy_id'], 'score' => (int) round($score / 3 * 100), 'recommendation' => $recommendation, 'created_at' => now(), 'updated_at' => now()]);

        return $this->ok($r, 'Rekomendasi level: '.$recommendation.'. Kamu tetap bebas memilih kelas lain.', ['score' => (int) round($score / 3 * 100), 'recommendation' => $recommendation]);
    }

    public function standaloneQuizzes()
    {
        return $this->page('standalone-quizzes', ['quizzes' => DB::table('standalone_quizzes')->get(['id', 'title', 'slug', 'description', 'age_band'])]);
    }

    public function standaloneQuiz(string $slug)
    {
        $quiz = DB::table('standalone_quizzes')->where('slug', $slug)->firstOrFail();
        $questions = json_decode($quiz->questions, true);
        foreach ($questions as &$q) {
            unset($q['correct'],$q['explanation']);
        }$quiz->questions = $questions;

        return $this->page('standalone-quiz', ['quiz' => $quiz]);
    }

    public function submitStandaloneQuiz(Request $r, string $slug)
    {
        $quiz = DB::table('standalone_quizzes')->where('slug', $slug)->firstOrFail();
        $r->validate(['answers' => 'required|array|max:100', 'answers.*' => 'nullable|array|max:10', 'answers.*.*' => 'integer|min:0|max:20']);
        $result = $this->grade(json_decode($quiz->questions, true), $r->input('answers'));
        DB::table('standalone_quiz_attempts')->insert(['quiz_id' => $quiz->id, 'user_id' => $r->user()->id, 'answers' => json_encode($r->input('answers')), 'score' => $result['score'], 'created_at' => now(), 'updated_at' => now()]);

        return $this->ok($r, 'Hasil kuis: '.$result['score'].'/100. Kuis mandiri tidak menerbitkan sertifikat.', $result);
    }

    public function admin(Request $r)
    {
        $this->adminGuard($r);
        $courses = Course::with('lessons', 'academy')->when($r->user()->role === 'instructor', fn ($q) => $q->where('instructor_id', $r->user()->id))->get();
        $assignments = DB::table('learning_assignments')->join('lessons', 'lessons.id', '=', 'learning_assignments.lesson_id')->join('users', 'users.id', '=', 'learning_assignments.user_id')->whereIn('lessons.course_id', $courses->pluck('id'))->select('learning_assignments.*', 'lessons.title as lesson_title', 'users.name as learner_name')->get();

        return $this->page('learning-admin', ['courses' => $courses, 'academies' => Academy::all(), 'assignments' => $assignments, 'reviewProjects' => Project::where('review_status', 'requested')->when($r->user()->role === 'instructor', fn ($q) => $q->whereRaw('1=0'))->get()]);
    }

    private function courseData(Request $r): array
    {
        return $r->validate(['title' => 'required|string|max:200', 'slug' => 'required|regex:/^[a-z0-9-]+$/|max:200', 'academy_id' => 'required|exists:academies,id', 'summary' => 'required|string|max:2000', 'description' => 'nullable|string|max:50000', 'level' => 'required|in:Pemula,Menengah,Lanjutan', 'age_band' => 'required|in:anak,smp,sma,kuliah,umum', 'access' => 'required|in:free,purchase,subscription', 'price' => 'required|integer|min:0|max:100000000', 'status' => 'required|in:draft,review,published,archived', 'duration_minutes' => 'required|integer|min:1|max:10000']);
    }

    public function saveCourse(Request $r)
    {
        $this->adminGuard($r);
        $data = $this->courseData($r);
        $r->validate(['slug' => 'unique:courses,slug']);
        Course::create($data + ['instructor_id' => $r->user()->id, 'instructor' => $r->user()->name]);

        return $this->ok($r, 'Kelas dibuat. Tambahkan materi melalui editor.');
    }

    public function updateCourse(Request $r, Course $course)
    {
        $this->courseGuard($r, $course);
        $data = $this->courseData($r);
        $r->validate(['slug' => Rule::unique('courses', 'slug')->ignore($course->id)]);
        $course->update($data);

        return $this->ok($r, 'Kelas diperbarui.');
    }

    private function lessonData(Request $r): array
    {
        $data = $r->validate(['course_id' => 'required|exists:courses,id', 'title' => 'required|string|max:200', 'slug' => 'required|regex:/^[a-z0-9-]+$/|max:200', 'kind' => 'required|in:rich_text,youtube_video,scrim,document,download_resource,external_resource,lab,quiz,assignment', 'position' => 'required|integer|min:0|max:10000', 'module_title' => 'required|string|max:200', 'is_preview' => 'required|boolean', 'required' => 'required|boolean', 'content' => 'required|array']);
        if (in_array($data['kind'], ['quiz', 'lab'])) {
            $r->validate(['content.time_limit_seconds' => 'nullable|integer|min:30|max:10800', 'content.max_attempts' => 'nullable|integer|min:1|max:100', 'content.pass_score' => 'nullable|integer|min:1|max:100', 'content.questions' => 'required|array|min:1|max:100', 'content.questions.*.prompt' => 'required|string|max:3000', 'content.questions.*.options' => 'required|array|min:2|max:10', 'content.questions.*.options.*' => 'required|string|max:1000', 'content.questions.*.correct' => 'required|array|min:1|max:10', 'content.questions.*.correct.*' => 'required|integer|min:0|max:9']);
            foreach ($data['content']['questions'] as $q) {
                foreach ($q['correct'] as $answer) {
                    abort_if(! array_key_exists($answer, $q['options']), 422, 'Jawaban benar harus menunjuk opsi yang tersedia.');
                }
            }
        }
        if ($data['kind'] === 'youtube_video') {
            $r->validate(['content.youtube_id' => 'required|regex:/^[a-zA-Z0-9_-]{11}$/']);
        }
        if (in_array($data['kind'], ['document', 'download_resource', 'external_resource'])) {
            $r->validate(['content.url' => 'required|url:http,https|max:2000']);
        }
        if (isset($data['content']['scrim'])) {
            $data['content']['scrim'] = $this->learning->validateScrim($data['content']['scrim']);
        }

        return $data;
    }

    public function saveLesson(Request $r)
    {
        $this->adminGuard($r);
        $data = $this->lessonData($r);
        $this->courseGuard($r, Course::findOrFail($data['course_id']));
        $r->validate(['slug' => Rule::unique('lessons', 'slug')->where('course_id', $data['course_id'])]);
        Lesson::create($data);

        return $this->ok($r, 'Materi dibuat.');
    }

    public function updateLesson(Request $r, Lesson $lesson)
    {
        $this->courseGuard($r, $lesson->course);
        $data = $this->lessonData($r);
        abort_unless($data['course_id'] == $lesson->course_id, 422);
        $r->validate(['slug' => Rule::unique('lessons', 'slug')->where('course_id', $lesson->course_id)->ignore($lesson->id)]);
        unset($data['content']['scrim']);
        if (isset($lesson->content['scrim'])) {
            $data['content']['scrim'] = $lesson->content['scrim'];
        }$lesson->update($data);

        return $this->ok($r, 'Materi diperbarui. Rekaman terbit tetap tersimpan sebagai revisi.');
    }

    public function publishScrim(Request $r, Lesson $lesson)
    {
        $this->courseGuard($r, $lesson->course);
        abort_unless($lesson->kind === 'scrim', 422);
        if (is_string($r->input('manifest'))) {
            $r->merge(['manifest' => json_decode($r->input('manifest'), true)]);
        }$r->validate(['manifest' => 'required|array', 'audio' => 'nullable|file|mimes:webm,ogg,mp3,wav|max:25000']);
        $manifest = $this->learning->validateScrim($r->input('manifest'));
        $audio = $r->hasFile('audio') ? $r->file('audio')->store('scrims', 'local') : null;
        DB::transaction(function () use ($r, $lesson, $manifest, $audio) {
            Lesson::whereKey($lesson->id)->lockForUpdate()->first();
            $version = (DB::table('scrim_revisions')->where('lesson_id', $lesson->id)->max('version') ?? 0) + 1;
            DB::table('scrim_revisions')->insert(['lesson_id' => $lesson->id, 'author_id' => $r->user()->id, 'version' => $version, 'manifest' => json_encode($manifest), 'checksum' => hash('sha256', json_encode($manifest)), 'audio_path' => $audio, 'published_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        });

        return $this->ok($r, 'Revisi scrim diterbitkan tanpa mengubah revisi sebelumnya.');
    }

    public function scrimAudio(Request $r, Lesson $lesson, int $revision)
    {
        $this->learning->authorizeLesson($r->user(), $lesson);
        $recording = DB::table('scrim_revisions')->where('id', $revision)->where('lesson_id',$lesson->id)->whereNotNull('published_at')->firstOrFail();
        abort_unless($recording->audio_path && Storage::disk('local')->exists($recording->audio_path),404);

        return Storage::disk('local')->response($recording->audio_path);
    }

    public function gradeAssignment(Request $r,int $id)
    {
        $assignment = DB::table('learning_assignments')->where('id',$id)->firstOrFail();
        $lesson = Lesson::findOrFail($assignment->lesson_id);
        $this->courseGuard($r,$lesson->course);
        $data = $r->validate(['score' => 'required|integer|min:0|max:100', 'feedback' => 'required|string|max:5000']);
        $passed = $data['score'] >= $lesson->course->pass_score;
        DB::table('learning_assignments')->where('id',$id)->update($data + ['status' => $passed ? 'approved' : 'changes_requested', 'reviewer_id' => $r->user()->id, 'updated_at' => now()]);
        if ($passed) {
            $this->learning->complete(User::findOrFail($assignment->user_id),$lesson);
        }

return $this->ok($r,'Penilaian tugas tersimpan.');
    }

    public function giveReview(Request $r,Project $project)
    {
        $this->adminGuard($r);
        abort_unless(in_array($r->user()->role,['admin', 'superadmin']),403);
        $data = $r->validate(['review_feedback' => 'required|string|min:10|max:10000']);
        $project->update($data + ['review_status' => 'reviewed']);

        return $this->ok($r,'Review proyek tersimpan.');
    }
}

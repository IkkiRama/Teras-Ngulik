<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LearningService
{
    public function hasAccess(?User $user, Course $course): bool
    {
        if ($course->status !== 'published' && ! in_array($user?->role, ['admin', 'superadmin']) && (! $user || $course->instructor_id !== $user->id)) {
            return false;
        }
        if ($course->access === 'free') {
            return true;
        }
        if (! $user) {
            return false;
        }
        if (in_array($user->role, ['admin', 'superadmin']) || $course->instructor_id === $user->id) {
            return true;
        }

        return DB::table('entitlements')->where('user_id', $user->id)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(fn ($q) => $q->where('course_id', $course->id)->when($course->access === 'subscription', fn ($s) => $s->orWhere('kind', 'subscription')))->exists();
    }

    public function authorizeLesson(?User $user, Lesson $lesson): void
    {
        abort_unless(($lesson->is_preview && $lesson->course->status === 'published') || $this->hasAccess($user, $lesson->course), 403, 'Materi ini memerlukan akses kelas.');
    }

    public function enrollment(User $user, Course $course): Enrollment
    {
        abort_unless($this->hasAccess($user, $course), 403, 'Kamu belum memiliki akses kelas ini.');

        return Enrollment::firstOrCreate(['user_id' => $user->id, 'course_id' => $course->id]);
    }

    public function complete(User $user, Lesson $lesson): ?Certificate
    {
        $this->enrollment($user, $lesson->course);

        return DB::transaction(function () use ($user, $lesson) {
            Enrollment::where('user_id', $user->id)->where('course_id', $lesson->course_id)->lockForUpdate()->first();
            LessonProgress::updateOrCreate(['user_id' => $user->id, 'lesson_id' => $lesson->id], ['completed_at' => now()]);
            $required = $lesson->course->lessons()->where('required', true)->pluck('id');
            if ($required->isEmpty()) {
                return null;
            }
            $done = LessonProgress::where('user_id', $user->id)->whereIn('lesson_id', $required)->whereNotNull('completed_at')->count();
            if ($done !== $required->count()) {
                return null;
            }
            $certificate = Certificate::firstOrCreate(['user_id' => $user->id, 'course_id' => $lesson->course_id], ['credential_id' => 'TN-'.strtoupper(Str::random(16)), 'issued_at' => now()]);
            Enrollment::where('user_id', $user->id)->where('course_id', $lesson->course_id)->update(['completed_at' => now()]);
            foreach (['xp_entries' => 100, 'bara_entries' => 25] as $table => $amount) {
                DB::table($table)->insertOrIgnore(['user_id' => $user->id, 'amount' => $amount, 'source' => 'course-complete', 'source_id' => (string) $lesson->course_id, 'idempotency_key' => "course-complete:{$user->id}:{$lesson->course_id}:".($table === 'xp_entries' ? 'xp' : 'bara'), 'created_at' => now()]);
            }

            return $certificate;
        });
    }

    public function sanitizedContent(Lesson $lesson): array
    {
        $content = $lesson->content;
        if (in_array($lesson->kind, ['quiz', 'lab'])) {
            $content['questions'] = array_map(function ($q) {
                unset($q['correct'],$q['explanation']);

                return $q;
            }, $content['questions'] ?? []);
        }

        return $content;
    }

    public function validateScrim(array $manifest): array
    {
        $fail = fn ($message) => throw ValidationException::withMessages(['manifest' => $message]);
        if (($manifest['schema_version'] ?? null) !== 1 || ! is_array($manifest['initial_files'] ?? null) || ! is_array($manifest['events'] ?? null)) {
            $fail('Manifest scrim tidak valid.');
        }
        if (count($manifest['initial_files']) < 1 || count($manifest['initial_files']) > 10 || count($manifest['events']) > 5000 || ! is_int($manifest['duration_ms'] ?? null) || $manifest['duration_ms'] < 1 || $manifest['duration_ms'] > 3600000) {
            $fail('Batas: 10 file, 5.000 event, 60 menit.');
        }
        $paths = [];
        foreach ($manifest['initial_files'] as $file) {
            if (! is_array($file) || ! is_string($file['path'] ?? null) || ! preg_match('/^[a-zA-Z0-9_\/. -]+\.(html|css|js|json|txt|py|sql|r)$/i', $file['path']) || str_contains($file['path'], '..') || ! is_string($file['content'] ?? null) || strlen($file['content']) > 200000 || in_array($file['path'], $paths)) {
                $fail('Nama/ukuran file tidak didukung.');
            }
            $paths[] = $file['path'];
        }
        $previous = -1;
        $seq = -1;
        foreach ($manifest['events'] as $event) {
            if (! is_array($event) || ! is_int($event['t'] ?? null) || $event['t'] < 0 || $event['t'] < $previous || $event['t'] > ($manifest['duration_ms'] ?? 0) || ! is_int($event['seq'] ?? null) || $event['seq'] <= $seq || ! in_array($event['type'] ?? null, ['replace', 'file_open', 'selection']) || ! in_array($event['path'] ?? null, $paths)) {
                $fail('Urutan atau tipe event tidak valid.');
            }
            if ($event['type'] === 'replace' && (! is_string($event['content'] ?? null) || strlen($event['content']) > 200000)) {
                $fail('Isi event terlalu besar.');
            }
            $previous = $event['t'];
            $seq = $event['seq'];
        }
        if (strlen(json_encode($manifest)) > 2000000) {
            $fail('Rekaman melebihi 2 MB. Pecah menjadi materi yang lebih pendek.');
        }
        // Checkpoints are deterministic reconstructed snapshots, never trusted client code.
        $files = array_column($manifest['initial_files'], 'content', 'path');
        $checkpoints = [['t' => 0, 'files' => $files]];
        $last = 0;
        foreach ($manifest['events'] as $event) {
            if ($event['type'] === 'replace') {
                $files[$event['path']] = $event['content'];
            }
            if ($event['t'] - $last >= 15000) {
                $checkpoints[] = ['t' => $event['t'], 'files' => $files];
                $last = $event['t'];
            }
        }
        $manifest['checkpoints'] = $checkpoints;

        return $manifest;
    }
}

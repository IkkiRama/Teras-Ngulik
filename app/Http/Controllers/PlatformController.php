<?php

namespace App\Http\Controllers;

use App\Models\Academy;
use App\Models\ContentItem;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PlatformController extends Controller
{
    public function home()
    {
        $announcement = DB::table('platform_settings')->where('key', 'announcement')->value('value');

        return Inertia::render('App', ['screen' => 'home', 'data' => [
            'academies' => Academy::all(), 'courses' => Course::where('status', 'published')->with('academy')->limit(6)->get(),
            'articles' => ContentItem::where('kind', 'article')->where('status', 'published')->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))->limit(3)->get(['id', 'kind', 'slug', 'title', 'summary', 'categories', 'access', 'published_at']),
            'events' => ContentItem::where('kind', 'event')->where('status', 'published')->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))->limit(2)->get(['id', 'kind', 'slug', 'title', 'summary', 'categories', 'access', 'published_at']),
            'announcement' => $announcement ? json_decode($announcement, true) : null,
        ]]);
    }

    public function dashboard(Request $request)
    {
        $uid = $request->user()->id;
        $bestScores = DB::table('quiz_attempts')->where('user_id', $uid)->select('lesson_id', DB::raw('MAX(score) as score'))->groupBy('lesson_id');
        $evidence = DB::query()->fromSub($bestScores, 'best')->join('lessons', 'lessons.id', '=', 'best.lesson_id')->join('courses', 'courses.id', '=', 'lessons.course_id')->select('courses.academy_id', DB::raw('AVG(best.score) as score'), DB::raw('COUNT(*) as attempts'))->groupBy('courses.academy_id')->get()->keyBy('academy_id');

        return Inertia::render('App', ['screen' => 'dashboard', 'data' => [
            'enrollments' => Enrollment::where('user_id', $uid)->with('course.academy')->latest('updated_at')->get()->map(function ($enrollment) use ($uid) {
                $required = DB::table('lessons')->where('course_id', $enrollment->course_id)->where('required', true)->pluck('id');
                $done = DB::table('lesson_progress')->where('user_id', $uid)->whereIn('lesson_id', $required)->whereNotNull('completed_at')->count();
                $last = DB::table('lessons')->where('course_id', $enrollment->course_id)->when($enrollment->last_lesson_id, fn ($q) => $q->where('id', $enrollment->last_lesson_id))->orderBy('position')->first();
                $enrollment->progress_percent = $required->count() ? min(100, (int) round($done / $required->count() * 100)) : 0;
                $enrollment->resume_url = $last ? '/belajar/course/'.$enrollment->course->slug.'/lesson/'.$last->slug : '/belajar/course/'.$enrollment->course->slug;

                return $enrollment;
            }),
            'projects' => DB::table('projects')->where('user_id', $uid)->latest('updated_at')->limit(5)->get(),
            'certificates' => DB::table('certificates')->where('user_id', $uid)->get(),
            'recentOrders' => DB::table('orders')->where('user_id', $uid)->latest('id')->limit(5)->get(['id', 'number', 'status', 'total', 'snapshot', 'created_at']),
            'xpEntries' => DB::table('xp_entries')->where('user_id', $uid)->latest('id')->limit(10)->get(),
            'baraEntries' => DB::table('bara_entries')->where('user_id', $uid)->latest('id')->limit(10)->get(),
            'skillScores' => Academy::all()->map(fn ($academy) => ['name' => $academy->name, 'score' => isset($evidence[$academy->id]) ? (int) round($evidence[$academy->id]->score) : null, 'attempts' => $evidence[$academy->id]->attempts ?? 0]),
            'counts' => ['courses' => Enrollment::where('user_id', $uid)->count(), 'projects' => DB::table('projects')->where('user_id', $uid)->count(), 'certificates' => DB::table('certificates')->where('user_id', $uid)->count(), 'review' => DB::table('review_cards')->where('user_id', $uid)->where('due_at', '<=', now())->count()],
        ]]);
    }

    public function leaderboard()
    {
        $leaders = DB::table('users')->join('xp_entries', 'xp_entries.user_id', '=', 'users.id')->where('privacy', 'public')->whereNotIn('age_band', ['child', 'sd', 'smp'])->select('users.username', 'users.name', DB::raw('SUM(xp_entries.amount) as xp'))->groupBy('users.id', 'users.username', 'users.name')->orderByDesc('xp')->limit(50)->get();

        return Inertia::render('App', ['screen' => 'leaderboard', 'data' => ['leaders' => $leaders]]);
    }

    public function profile(string $username)
    {
        $user = User::where('username', $username)->where('privacy', 'public')->whereNotIn('age_band', ['child', 'sd', 'smp'])->firstOrFail();

        return Inertia::render('App', ['screen' => 'public-profile', 'data' => ['profile' => $user->only(['name', 'username', 'bio', 'avatar_seed', 'website', 'github', 'linkedin']), 'certificates' => DB::table('certificates')->where('user_id', $user->id)->get(), 'projects' => DB::table('projects')->where('user_id', $user->id)->where('visibility', 'public')->get()]]);
    }

    public function adminUsers(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403);

        return Inertia::render('App', ['screen' => 'admin-users', 'data' => ['users' => User::orderBy('id')->paginate(30), 'audit' => DB::table('audit_logs')->latest('id')->limit(50)->get()]]);
    }

    public function adminRole(Request $request, User $user)
    {
        abort_unless($request->user()->role === 'admin' && $user->id !== $request->user()->id, 403);
        $input = $request->validate(['role' => 'required|in:admin,learner']);
        $user->forceFill(['role' => $input['role']])->save();
        DB::table('audit_logs')->insert(['user_id' => $request->user()->id, 'action' => 'user.role_changed', 'target' => (string) $user->id, 'metadata' => json_encode($input), 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success','Peran diperbarui.');
    }
}

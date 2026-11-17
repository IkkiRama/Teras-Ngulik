<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\NotebookEntry;
use App\Models\Project;
use App\Models\ReviewCard;
use App\Models\User;
use Database\Seeders\LearningSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LearningTest extends TestCase
{
    use RefreshDatabase;

    private function learner(array $data = []): User
    {
        return User::factory()->create($data + ['email_verified_at' => now()]);
    }

    private function course(array $data = []): Course
    {
        $academy = Academy::create(['name' => 'Web', 'slug' => 'web', 'description' => 'Test']);

        return Course::create($data + ['academy_id' => $academy->id, 'title' => 'Test course', 'slug' => 'test-course', 'summary' => 'Test', 'status' => 'published', 'access' => 'free']);
    }

    private function lesson(Course $course, array $data = []): Lesson
    {
        return Lesson::create($data + ['course_id' => $course->id, 'title' => 'Lesson', 'slug' => 'lesson', 'kind' => 'rich_text', 'position' => 1, 'content' => ['body' => 'Learn the concept.']]);
    }

    public function test_paid_lesson_does_not_leak_content_to_unentitled_user(): void
    {
        $course = $this->course(['access' => 'purchase', 'price' => 100000]);
        $lesson = $this->lesson($course, ['content' => ['body' => 'PRIVATE CODE']]);
        $user = $this->learner();
        $this->get('/belajar/course/test-course/lesson/lesson')->assertForbidden()->assertDontSee('PRIVATE CODE');
        $this->actingAs($user)->get('/belajar/course/test-course/lesson/lesson')->assertForbidden()->assertDontSee('PRIVATE CODE');
        DB::table('entitlements')->insert(['user_id' => $user->id, 'course_id' => $course->id, 'kind' => 'course', 'source' => 'test', 'source_id' => 'test']);
        $this->get('/belajar/course/test-course/lesson/lesson')->assertOk();
    }

    public function test_unassigned_draft_and_other_instructor_content_are_not_public(): void
    {
        $course = $this->course(['status' => 'draft', 'description' => 'DRAFT SECRET']);
        $lesson = $this->lesson($course, ['is_preview' => true, 'content' => ['body' => 'DRAFT SOURCE']]);
        $this->get('/belajar/course/test-course')->assertNotFound()->assertDontSee('DRAFT SECRET');
        $instructor = $this->learner();
        $instructor->forceFill(['role' => 'instructor'])->save();
        $this->actingAs($instructor)->get('/belajar/course/test-course/lesson/lesson')->assertForbidden()->assertDontSee('DRAFT SOURCE');
    }

    public function test_expired_subscription_cannot_open_subscriber_course(): void
    {
        $course = $this->course(['access' => 'subscription']);
        $lesson = $this->lesson($course);
        $user = $this->learner();
        DB::table('entitlements')->insert(['user_id' => $user->id, 'kind' => 'subscription', 'source' => 'test', 'source_id' => 'sub', 'expires_at' => now()->subMinute()]);
        $this->actingAs($user)->get('/belajar/course/test-course/lesson/lesson')->assertForbidden();
        DB::table('entitlements')->update(['expires_at' => now()->addDay()]);
        $this->get('/belajar/course/test-course/lesson/lesson')->assertOk();
    }

    public function test_quiz_answers_are_hidden_and_client_cannot_claim_completion(): void
    {
        $course = $this->course();
        $lesson = $this->lesson($course, ['kind' => 'quiz', 'content' => ['questions' => [['prompt' => '2+2?', 'options' => ['3', '4', '5', '6'], 'correct' => [1], 'explanation' => 'Two plus two is four.']]]]);
        $user = $this->learner();
        $this->actingAs($user)->get('/belajar/course/test-course/lesson/lesson')->assertInertia(fn (Assert $page) => $page->component('App', false)->missing('data.lesson.content.questions.0.correct')->missing('data.lesson.content.questions.0.explanation'));
        $this->postJson('/lessons/'.$lesson->id.'/complete', [])->assertStatus(422);
        $this->postJson('/lessons/'.$lesson->id.'/quiz', ['answers' => [[0]], 'score' => 100, 'passed' => true])->assertOk()->assertJsonPath('score', 0)->assertJsonPath('passed', false);
        $this->assertDatabaseCount('certificates', 0);
        $this->postJson('/lessons/'.$lesson->id.'/quiz', ['answers' => [[1]]])->assertOk()->assertJsonPath('score', 100)->assertJsonPath('passed', true);
        $this->assertDatabaseCount('certificates', 1);
    }

    public function test_certificate_and_completion_rewards_are_issued_exactly_once(): void
    {
        $course = $this->course();
        $read = $this->lesson($course);
        $quiz = $this->lesson($course, ['slug' => 'quiz', 'kind' => 'quiz', 'position' => 2, 'content' => ['questions' => [['prompt' => 'Which?', 'options' => ['A', 'B'], 'correct' => [0]]]]]);
        $user = $this->learner();
        $this->actingAs($user);
        $this->postJson('/lessons/'.$read->id.'/complete')->assertOk();
        $this->assertDatabaseCount('certificates', 0);
        $this->postJson('/lessons/'.$quiz->id.'/quiz', ['answers' => [[0]]])->assertOk();
        $this->postJson('/lessons/'.$quiz->id.'/quiz', ['answers' => [[0]]])->assertOk();
        $this->postJson('/lessons/'.$read->id.'/complete')->assertOk();
        $this->assertDatabaseCount('certificates', 1);
        $this->assertDatabaseCount('xp_entries', 1);
        $this->assertDatabaseCount('bara_entries', 1);
        $this->assertSame(100, $user->xpBalance());
        $this->assertSame(25, $user->baraBalance());
    }

    public function test_assignment_requires_manual_authorized_grade_before_certificate(): void
    {
        $course = $this->course();
        $lesson = $this->lesson($course, ['kind' => 'assignment']);
        $user = $this->learner();
        $this->actingAs($user);
        $this->postJson('/lessons/'.$lesson->id.'/assignment', ['body' => 'This is my project description, including my choices.', 'repository_url' => 'https://example.test/repo'])->assertOk();
        $id = DB::table('learning_assignments')->value('id');
        $this->assertDatabaseCount('certificates', 0);
        $this->postJson('/admin/learning/assignments/'.$id, ['score' => 100, 'feedback' => 'Looks good.'])->assertForbidden();
        $admin = $this->learner();
        $admin->forceFill(['role' => 'admin'])->save();
        $this->actingAs($admin)->postJson('/admin/learning/assignments/'.$id, ['score' => 90, 'feedback' => 'Meets the published rubric.'])->assertOk();
        $this->assertDatabaseHas('certificates', ['user_id' => $user->id, 'course_id' => $course->id]);
    }

    public function test_project_notebook_and_review_are_private_to_the_owner(): void
    {
        $owner = $this->learner();
        $other = $this->learner();
        $project = Project::create(['user_id' => $owner->id, 'title' => 'Private fork', 'files' => [['path' => 'main.js', 'content' => 'secret source']]]);
        $note = NotebookEntry::create(['user_id' => $owner->id, 'title' => 'Private note', 'body' => 'Secret']);
        $card = ReviewCard::create(['user_id' => $owner->id, 'front' => 'Question', 'back' => 'Answer', 'due_at' => now()]);
        $this->actingAs($other)->get('/labs/playground?project='.$project->id)->assertForbidden();
        $this->deleteJson('/projects/'.$project->id)->assertForbidden();
        $this->deleteJson('/notebook/'.$note->id)->assertForbidden();
        $this->postJson('/review/cards/'.$card->id, ['rating' => 'good'])->assertForbidden();
        $this->assertDatabaseCount('projects', 1);
        $this->assertDatabaseCount('notebook_entries', 1);
    }

    public function test_assignment_files_are_private_downloads_and_script_uploads_are_rejected(): void
    {
        Storage::fake('local');
        $course = $this->course();
        $lesson = $this->lesson($course, ['kind' => 'assignment']);
        $owner = $this->learner();
        $other = $this->learner();
        $this->actingAs($owner)->postJson('/lessons/'.$lesson->id.'/assignment', ['body' => 'A description of my completed project.', 'attachment' => UploadedFile::fake()->createWithContent('proof.txt', 'My project evidence.')])->assertOk();
        $id = DB::table('learning_assignments')->value('id');
        $path = DB::table('learning_assignments')->value('file_path');
        Storage::disk('local')->assertExists($path);
        $this->get('/assignments/'.$id.'/file')->assertOk()->assertDownload('proof.txt');
        $this->actingAs($other)->get('/assignments/'.$id.'/file')->assertForbidden();
        $this->actingAs($owner)->postJson('/lessons/'.$lesson->id.'/assignment', ['body' => 'A description of my completed project.', 'attachment' => UploadedFile::fake()->createWithContent('script.html', '<script>alert(1)</script>')])->assertUnprocessable();
    }

    public function test_child_project_cannot_be_published_even_with_a_tampered_request(): void
    {
        $user = $this->learner(['age_band' => 'child']);
        $this->actingAs($user)->postJson('/projects', ['title' => 'My project', 'runtime' => 'html', 'visibility' => 'public', 'files' => [['path' => 'index.html', 'content' => '<h1>Hello</h1>']]])->assertOk();
        $this->assertDatabaseHas('projects', ['user_id' => $user->id, 'visibility' => 'private', 'share_slug' => null]);
    }

    public function test_review_card_cannot_be_farmed_before_due_time_and_grants_no_reward(): void
    {
        $user = $this->learner();
        $card = ReviewCard::create(['user_id' => $user->id, 'front' => 'Explain variables', 'back' => 'Names for values', 'due_at' => now()]);
        $this->actingAs($user)->postJson('/review/cards/'.$card->id, ['rating' => 'good'])->assertOk();
        $this->postJson('/review/cards/'.$card->id, ['rating' => 'good'])->assertStatus(422);
        $this->assertDatabaseCount('review_history', 1);
        $this->assertDatabaseCount('xp_entries', 0);
        $this->assertDatabaseCount('bara_entries', 0);
    }

    public function test_scrim_revisions_are_immutable_and_invalid_timeline_cannot_publish(): void
    {
        $course = $this->course();
        $lesson = $this->lesson($course, ['kind' => 'scrim']);
        $admin = $this->learner();
        $admin->forceFill(['role' => 'admin'])->save();
        $manifest = ['schema_version' => 1, 'initial_files' => [['path' => 'index.html', 'content' => '<h1>One</h1>']], 'duration_ms' => 20000, 'events' => [['t' => 1000, 'seq' => 1, 'type' => 'replace', 'path' => 'index.html', 'content' => '<h1>Two 👋</h1>'], ['t' => 16000, 'seq' => 2, 'type' => 'replace', 'path' => 'index.html', 'content' => '<h1>Three</h1>']]];
        $this->actingAs($admin)->postJson('/admin/learning/lesson/'.$lesson->id.'/scrim', ['manifest' => $manifest])->assertOk();
        $first = DB::table('scrim_revisions')->first();
        $manifest['events'][0]['content'] = '<h1>New</h1>';
        $this->postJson('/admin/learning/lesson/'.$lesson->id.'/scrim', ['manifest' => $manifest])->assertOk();
        $this->assertDatabaseCount('scrim_revisions', 2);
        $this->assertSame($first->manifest, DB::table('scrim_revisions')->where('id', $first->id)->value('manifest'));
        $manifest['events'][1]['t'] = 500;
        $this->postJson('/admin/learning/lesson/'.$lesson->id.'/scrim', ['manifest' => $manifest])->assertUnprocessable();
        $this->assertDatabaseCount('scrim_revisions', 2);
        $manifest['initial_files'][0]['path'] = '../../secret.php';
        $this->postJson('/admin/learning/lesson/'.$lesson->id.'/scrim', ['manifest' => $manifest])->assertUnprocessable();
    }

    public function test_demo_learning_seed_contains_all_five_academies_and_real_questions(): void
    {
        $this->seed(LearningSeeder::class);
        $this->assertDatabaseCount('academies', 5);
        $this->assertDatabaseCount('courses', 6);
        $this->assertDatabaseCount('standalone_quizzes', 2);
        $quiz = DB::table('standalone_quizzes')->where('slug', 'logika-sehari-hari')->first();
        $this->assertCount(2, json_decode($quiz->questions, true));
    }

    public function test_timed_quiz_requires_owned_server_session_and_rejects_expired_submission(): void
    {
        $course = $this->course();
        $lesson = $this->lesson($course, ['kind' => 'quiz', 'content' => ['time_limit_seconds' => 30, 'max_attempts' => 1, 'questions' => [['prompt' => 'Which?', 'options' => ['A', 'B'], 'correct' => [0]]]]]);
        $user = $this->learner();
        $this->actingAs($user);
        $this->get('/belajar/course/test-course/lesson/lesson')->assertInertia(fn (Assert $page) => $page->component('App', false)->missing('data.lesson.content.questions'));
        $this->postJson('/lessons/'.$lesson->id.'/quiz', ['answers' => [[0]]])->assertUnprocessable();
        $response = $this->postJson('/lessons/'.$lesson->id.'/quiz/start')->assertOk();
        $token = $response->json('session.token');
        $this->assertSame('Which?', $response->json('questions.0.prompt'));
        $this->assertArrayNotHasKey('correct', $response->json('questions.0'));
        $this->postJson('/lessons/'.$lesson->id.'/quiz/start')->assertOk()->assertJsonPath('session.token', $token);
        $this->assertDatabaseCount('quiz_sessions', 1);
        $this->travel(31)->seconds();
        $this->postJson('/lessons/'.$lesson->id.'/quiz', ['answers' => [[0]], 'session_token' => $token])->assertUnprocessable();
        $this->postJson('/lessons/'.$lesson->id.'/quiz/start')->assertUnprocessable();
        $this->assertDatabaseCount('certificates', 0);
        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_quiz_session_is_single_use_and_attempt_policy_is_enforced(): void
    {
        $course = $this->course();
        $lesson = $this->lesson($course, ['kind' => 'quiz', 'content' => ['max_attempts' => 2, 'questions' => [['prompt' => 'Which?', 'options' => ['A', 'B'], 'correct' => [0]]]]]);
        $user = $this->learner();
        $other = $this->learner();
        $this->actingAs($user);
        $token = $this->postJson('/lessons/'.$lesson->id.'/quiz/start')->json('session.token');
        $this->actingAs($other)->postJson('/lessons/'.$lesson->id.'/quiz', ['answers' => [[0]], 'session_token' => $token])->assertUnprocessable();
        $this->actingAs($user)->postJson('/lessons/'.$lesson->id.'/quiz', ['answers' => [[1]], 'session_token' => $token])->assertOk()->assertJsonPath('passed', false);
        $this->postJson('/lessons/'.$lesson->id.'/quiz', ['answers' => [[0]], 'session_token' => $token])->assertUnprocessable();
        $next = $this->postJson('/lessons/'.$lesson->id.'/quiz/start')->assertOk()->json('session.token');
        $this->assertNotSame($token, $next);
        $this->postJson('/lessons/'.$lesson->id.'/quiz', ['answers' => [[0]], 'session_token' => $next])->assertOk()->assertJsonPath('passed', true);
        $this->postJson('/lessons/'.$lesson->id.'/quiz/start')->assertUnprocessable();
        $this->assertDatabaseCount('quiz_attempts', 2);
        $this->assertDatabaseCount('certificates', 1);
    }

    public function test_path_order_is_preserved_and_launch_requires_completed_predecessors(): void
    {
        $first = $this->course();
        $second = Course::create(['academy_id' => $first->academy_id, 'title' => 'Second', 'slug' => 'second', 'summary' => 'Test', 'status' => 'published', 'access' => 'free']);
        $lesson = $this->lesson($second);
        $user = $this->learner();
        DB::table('learning_paths')->insert(['title' => 'Ordered path', 'slug' => 'ordered', 'description' => 'Test', 'academy_id' => $first->academy_id, 'course_ids' => json_encode([$second->id, $first->id]), 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($user);
        $this->get('/belajar/learning-path')->assertInertia(fn (Assert $page) => $page->component('App', false)->where('data.paths.0.courses.0.id', $second->id)->where('data.paths.0.courses.1.path_locked', true));
        $this->post('/belajar/learning-path/ordered/start/'.$second->id)->assertStatus(422);
        $this->post('/belajar/learning-path/ordered/enroll')->assertRedirect();
        $this->post('/belajar/learning-path/ordered/enroll')->assertRedirect();
        $this->assertDatabaseCount('learning_path_enrollments',1);
        $this->post('/belajar/learning-path/ordered/start/'.$first->id)->assertStatus(422);
        $this->post('/belajar/learning-path/ordered/start/'.$second->id)->assertRedirect('/belajar/course/second');
        $this->postJson('/lessons/'.$lesson->id.'/complete')->assertOk();
        $this->post('/belajar/learning-path/ordered/start/'.$first->id)->assertRedirect('/belajar/course/test-course');
    }
}

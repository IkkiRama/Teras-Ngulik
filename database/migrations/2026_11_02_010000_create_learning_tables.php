<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academies', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('description');
            $t->string('color')->default('#DCE8FF');
            $t->string('icon')->default('code');
            $t->timestamps();
        });
        Schema::create('courses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('academy_id')->constrained();
            $t->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('summary');
            $t->longText('description')->nullable();
            $t->string('level')->default('Pemula');
            $t->string('age_band')->default('umum');
            $t->string('access')->default('free');
            $t->unsignedInteger('price')->default(0);
            $t->string('status')->default('draft');
            $t->unsignedInteger('duration_minutes')->default(60);
            $t->string('color')->default('#DCE8FF');
            $t->string('instructor')->default('Tim Teras Ngulik');
            $t->unsignedInteger('pass_score')->default(70);
            $t->timestamps();
            $t->index(['status', 'academy_id']);
        });
        Schema::create('lessons', function (Blueprint $t) {
            $t->id();
            $t->foreignId('course_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->string('slug');
            $t->string('module_title')->default('Mulai mengulik');
            $t->string('kind')->default('rich_text');
            $t->unsignedInteger('position')->default(0);
            $t->boolean('is_preview')->default(false);
            $t->boolean('required')->default(true);
            $t->json('content');
            $t->timestamps();
            $t->unique(['course_id', 'slug']);
        });
        Schema::create('enrollments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('course_id')->constrained()->cascadeOnDelete();
            $t->timestamp('completed_at')->nullable();
            $t->unsignedInteger('last_lesson_id')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'course_id']);
        });
        Schema::create('lesson_progress', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('position_ms')->default(0);
            $t->unsignedInteger('scroll_y')->default(0);
            $t->text('code_snapshot')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'lesson_id']);
        });
        Schema::create('quiz_attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $t->json('answers');
            $t->unsignedInteger('score');
            $t->boolean('passed');
            $t->timestamps();
        });
        Schema::create('learning_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $t->text('body');
            $t->string('repository_url')->nullable();
            $t->string('status')->default('submitted');
            $t->text('feedback')->nullable();
            $t->unsignedInteger('score')->nullable();
            $t->foreignId('reviewer_id')->nullable()->constrained('users');
            $t->timestamps();
            $t->unique(['user_id', 'lesson_id']);
        });
        Schema::create('projects', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->string('runtime')->default('html');
            $t->json('files');
            $t->json('versions')->nullable();
            $t->string('visibility')->default('private');
            $t->string('share_slug')->nullable()->unique();
            $t->unsignedInteger('source_lesson_id')->nullable();
            $t->unsignedInteger('source_time_ms')->nullable();
            $t->text('review_feedback')->nullable();
            $t->string('review_status')->default('none');
            $t->timestamps();
        });
        Schema::create('review_cards', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->text('front');
            $t->text('back');
            $t->unsignedInteger('interval_days')->default(0);
            $t->unsignedInteger('repetitions')->default(0);
            $t->timestamp('due_at');
            $t->timestamps();
        });
        Schema::create('review_history', function (Blueprint $t) {
            $t->id();
            $t->foreignId('review_card_id')->constrained()->cascadeOnDelete();
            $t->string('rating');
            $t->timestamp('reviewed_at');
            $t->timestamp('next_due_at');
        });
        Schema::create('notebook_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $t->string('title');
            $t->longText('body');
            $t->string('tags')->nullable();
            $t->unsignedInteger('position_ms')->nullable();
            $t->boolean('is_bookmark')->default(false);
            $t->timestamps();
        });
        Schema::create('certificates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('course_id')->constrained();
            $t->string('credential_id')->unique();
            $t->timestamp('issued_at');
            $t->timestamp('revoked_at')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'course_id']);
        });
        Schema::create('learning_paths', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('description');
            $t->foreignId('academy_id')->constrained();
            $t->json('course_ids');
            $t->timestamps();
        });
        Schema::create('learning_diagnostics', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('academy_id')->constrained();
            $t->unsignedInteger('score');
            $t->string('recommendation');
            $t->timestamps();
        });
        Schema::create('scrim_revisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $t->foreignId('author_id')->constrained('users');
            $t->unsignedInteger('version');
            $t->json('manifest');
            $t->string('checksum', 64);
            $t->string('audio_path')->nullable();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
            $t->unique(['lesson_id', 'version']);
        });
        Schema::create('standalone_quizzes', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('description');
            $t->string('age_band')->default('umum');
            $t->json('questions');
            $t->unsignedInteger('pass_score')->default(70);
            $t->timestamps();
        });
        Schema::create('standalone_quiz_attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('quiz_id')->constrained('standalone_quizzes');
            $t->json('answers');
            $t->unsignedInteger('score');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['standalone_quiz_attempts', 'standalone_quizzes', 'scrim_revisions', 'learning_diagnostics', 'learning_paths', 'certificates', 'notebook_entries', 'review_history', 'review_cards', 'projects', 'learning_assignments', 'quiz_attempts', 'lesson_progress', 'enrollments', 'lessons', 'courses', 'academies'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

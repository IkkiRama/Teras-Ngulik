<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $t->uuid('token')->unique();
            $t->string('status')->default('active');
            $t->timestamp('started_at');
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'lesson_id', 'status']);
        });
        Schema::table('quiz_attempts', fn (Blueprint $t) => $t->uuid('session_token')->nullable()->unique());
        Schema::create('learning_path_enrollments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('learning_path_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['user_id', 'learning_path_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_path_enrollments');
        Schema::table('quiz_attempts', fn (Blueprint $t) => $t->dropColumn('session_token'));
        Schema::dropIfExists('quiz_sessions');
    }
};

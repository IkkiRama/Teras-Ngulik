<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_items', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 30)->index();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('body');
            $table->json('categories')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('access', 24)->default('public');
            $table->string('status', 24)->default('draft')->index();
            $table->unsignedInteger('price')->default(0);
            $table->unsignedInteger('capacity')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['kind', 'status', 'published_at']);
        });
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->nullOnDelete();
            $table->text('body');
            $table->unsignedTinyInteger('depth')->default(0);
            $table->string('status', 20)->default('published');
            $table->timestamps();
            $table->index(['content_item_id', 'parent_id', 'status']);
        });
        Schema::create('content_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_item_id')->constrained()->cascadeOnDelete();
            $table->text('reason');
            $table->string('status', 20)->default('open');
            $table->timestamps();
        });
        Schema::create('program_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('active');
            $table->string('slot')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['content_item_id', 'user_id']);
            $table->unique(['content_item_id', 'slot']);
        });
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 30)->default('support');
            $table->string('subject');
            $table->longText('body');
            $table->string('status', 24)->default('open');
            $table->json('messages')->nullable();
            $table->timestamps();
            $table->index(['kind', 'status']);
        });
        Schema::create('newsletter_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('subscribed');
            $table->string('unsubscribe_hash', 64)->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('content_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['content_notifications', 'newsletter_subscriptions', 'support_tickets', 'program_registrations', 'content_reports', 'comments', 'content_items'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

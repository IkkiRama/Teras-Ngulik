<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_subscriptions', function (Blueprint $table) {
            $table->text('unsubscribe_token')->nullable();
        });
        Schema::create('newsletter_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users');
            $table->string('title');
            $table->string('subject');
            $table->longText('body');
            $table->string('status')->default('draft')->index();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('newsletter_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('newsletter_campaigns')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained('newsletter_subscriptions')->cascadeOnDelete();
            $table->string('status')->default('queued')->index();
            $table->string('failure_code')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'subscription_id']);
        });
        Schema::create('content_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('original_name');
            $table->string('path');
            $table->string('mime');
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_attachments');
        Schema::dropIfExists('newsletter_deliveries');
        Schema::dropIfExists('newsletter_campaigns');
        Schema::table('newsletter_subscriptions', fn (Blueprint $table) => $table->dropColumn('unsubscribe_token'));
    }
};

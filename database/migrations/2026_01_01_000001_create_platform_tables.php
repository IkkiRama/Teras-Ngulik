<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('username')->nullable()->unique();
            $t->string('role')->default('learner');
            $t->string('age_band')->default('adult');
            $t->text('bio')->nullable();
            $t->string('avatar_seed')->default('teras');
            $t->string('website')->nullable();
            $t->string('github')->nullable();
            $t->string('linkedin')->nullable();
            $t->string('privacy')->default('private');
            $t->string('locale')->default('id');
            $t->boolean('newsletter')->default(false);
            $t->string('google_id')->nullable()->unique();
        });
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('title');
            $t->string('kind');
            $t->unsignedBigInteger('course_id')->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->text('description');
            $t->unsignedInteger('price');
            $t->integer('stock')->nullable();
            $t->json('metadata')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('coupons', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->unsignedInteger('percent');
            $t->unsignedInteger('max_uses')->default(100);
            $t->unsignedInteger('uses')->default(0);
            $t->unsignedInteger('min_seats')->default(1);
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();
        });
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('number')->unique();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('product_id')->constrained();
            $t->string('status')->default('pending')->index();
            $t->unsignedInteger('quantity');
            $t->unsignedInteger('subtotal');
            $t->unsignedInteger('discount')->default(0);
            $t->unsignedInteger('total');
            $t->json('snapshot');
            $t->json('recipients');
            $t->foreignId('coupon_id')->nullable()->constrained();
            $t->text('snap_token')->nullable();
            $t->timestamp('expires_at');
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
        });
        Schema::create('entitlements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->unsignedBigInteger('course_id')->nullable()->index();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->string('kind', 32);
            $t->string('source', 32);
            $t->string('source_id', 100);
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'kind', 'source', 'source_id', 'course_id'], 'entitlement_source_unique');
            $t->index(['user_id', 'kind', 'expires_at']);
        });
        foreach (['xp_entries', 'bara_entries'] as $name) {
            Schema::create($name, function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained();
                $t->integer('amount');
                $t->string('source');
                $t->string('source_id');
                $t->string('idempotency_key')->unique();
                $t->timestamp('created_at')->useCurrent();
                $t->index(['user_id', 'created_at']);
            });
        }
        Schema::create('reward_items', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('description');
            $t->unsignedInteger('bara_cost');
            $t->unsignedInteger('stock');
            $t->unsignedBigInteger('course_id')->nullable();
            $t->timestamps();
        });
        Schema::create('reward_redemptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('reward_item_id')->constrained();
            $t->string('request_key')->unique();
            $t->unsignedInteger('cost');
            $t->string('status')->default('granted');
            $t->timestamps();
        });
        Schema::create('payment_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained();
            $t->string('event_key')->unique();
            $t->string('provider_status');
            $t->json('metadata');
            $t->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained();
            $t->string('action');
            $t->string('target')->nullable();
            $t->json('metadata')->nullable();
            $t->timestamps();
        });
        Schema::create('password_otps', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->string('code_hash');
            $t->unsignedInteger('attempts')->default(0);
            $t->timestamp('expires_at');
            $t->timestamp('consumed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('platform_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->json('value');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['platform_settings', 'password_otps', 'audit_logs', 'payment_events', 'reward_redemptions', 'reward_items', 'bara_entries', 'xp_entries', 'entitlements', 'orders', 'coupons', 'products'] as $name) {
            Schema::dropIfExists($name);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['username', 'role', 'age_band', 'bio', 'avatar_seed', 'website', 'github', 'linkedin', 'privacy', 'locale', 'newsletter', 'google_id']));
    }
};

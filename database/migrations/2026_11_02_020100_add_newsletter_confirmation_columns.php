<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('newsletter_subscriptions', 'unsubscribe_token')) {
            Schema::table('newsletter_subscriptions', fn (Blueprint $t) => $t->text('unsubscribe_token')->nullable());
        }
        if (! Schema::hasColumn('newsletter_subscriptions', 'confirmation_hash')) {
            Schema::table('newsletter_subscriptions', fn (Blueprint $t) => $t->string('confirmation_hash', 64)->nullable()->unique());
        }
        if (! Schema::hasColumn('newsletter_subscriptions', 'confirmed_at')) {
            Schema::table('newsletter_subscriptions', fn (Blueprint $t) => $t->timestamp('confirmed_at')->nullable());
        }
    }

    public function down(): void
    {
        foreach (['confirmation_hash', 'confirmed_at'] as $column) {
            if (Schema::hasColumn('newsletter_subscriptions', $column)) {
                Schema::table('newsletter_subscriptions', fn (Blueprint $t) => $t->dropColumn($column));
            }
        }
    }
};

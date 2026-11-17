<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            $t->text('delivery')->nullable();
            $t->string('fulfillment_status')->default('unfulfilled');
            $t->string('tracking_number')->nullable();
        });
        Schema::create('refund_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->unique()->constrained();
            $t->foreignId('user_id')->constrained();
            $t->text('reason');
            $t->string('status')->default('requested');
            $t->text('admin_note')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn(['delivery', 'fulfillment_status', 'tracking_number']));
    }
};

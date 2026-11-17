<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_assignments', function (Blueprint $t) {
            $t->string('file_path')->nullable();
            $t->string('file_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('learning_assignments', fn (Blueprint $t) => $t->dropColumn(['file_path', 'file_name']));
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The activity log, narrowed by kind, newest first; and what goes after a while found without
 * reading the whole table.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->index(['action', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex(['action', 'id']);
        });
    }
};

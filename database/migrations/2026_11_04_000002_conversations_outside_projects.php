<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Someone's conversation with the Assistant outside any project (signed up and in none yet, or
 * with several and none chosen): a conversation of no project, theirs alone.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('conversations')->whereNull('project_id')->delete();

        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable(false)->change();
        });
    }
};

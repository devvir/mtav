<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('conversation_user', function (Blueprint $table) {
            $table->timestamp('pinned_at')->nullable();
        });

        // Community starts pinned, for everyone in it
        DB::table('conversation_user')
            ->whereIn('conversation_id', DB::table('conversations')->where('is_community', true)->select('id'))
            ->update(['pinned_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('conversation_user', function (Blueprint $table) {
            $table->dropColumn('pinned_at');
        });
    }
};

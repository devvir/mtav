<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->boolean('is_assistant')->default(false)->after('is_community');
        });
        Schema::table('messages', function (Blueprint $table) {
            $table->boolean('from_assistant')->default(false)->after('user_id');
            $table->json('extras')->nullable()->after('body');
        });

        // Everyone taking part has their conversation with it, pinned to begin with
        foreach (DB::table('memberships')->get(['user_id', 'project_id']) as $membership) {
            $conversation = DB::table('conversations')->insertGetId([
                'project_id'   => $membership->project_id,
                'is_assistant' => true,
                'pair'         => "assistant:{$membership->user_id}",
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
            DB::table('conversation_user')->insert([
                'conversation_id' => $conversation,
                'user_id'         => $membership->user_id,
                'pinned_at'       => now(),
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('conversations')->where('is_assistant', true)->delete();

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('from_assistant', 'extras');
        });
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('is_assistant');
        });
    }
};

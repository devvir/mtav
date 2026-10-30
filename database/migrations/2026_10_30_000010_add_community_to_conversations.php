<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->boolean('is_community')->default(false)->after('is_group');
        });

        // Every project has its Community group, with everyone in it
        foreach (DB::table('projects')->pluck('id') as $projectId) {
            $conversation = DB::table('conversations')->insertGetId([
                'project_id'   => $projectId,
                'is_group'     => true,
                'is_community' => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            foreach (DB::table('memberships')->where('project_id', $projectId)->pluck('user_id') as $userId) {
                DB::table('conversation_user')->insert([
                    'conversation_id' => $conversation,
                    'user_id'         => $userId,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('conversations')->where('is_community', true)->delete();

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('is_community');
        });
    }
};

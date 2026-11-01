<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('family_people', function (Blueprint $table) {
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable()->comment('When they first came in');

            $table->primary(['family_id', 'user_id']);
        });

        // Who is in each now: who was in one before this was kept is not known
        DB::statement('INSERT INTO family_people (family_id, user_id, created_at)
            SELECT family_id, user_id, created_at FROM memberships WHERE family_id IS NOT NULL');

        // Names repeat. The foreign key on project_id needs an index to stand on, first
        Schema::table('families', function (Blueprint $table) {
            $table->index(['project_id', 'name']);
            $table->dropUnique(['project_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->unique(['project_id', 'name']);
            $table->dropIndex(['project_id', 'name']);
        });

        Schema::dropIfExists('family_people');
    }
};

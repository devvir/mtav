<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('listed')->default(false)->after('active');
            $table->boolean('open_to_join')->default(false)->after('listed');
        });

        Schema::create('join_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('declined_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('join_requests');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('listed', 'open_to_join');
        });
    }
};

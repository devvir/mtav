<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('lottery_audits', function (Blueprint $table) {
            $table->id();
            $table->uuid('execution_uuid');
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->foreignId('lottery_id')->constrained('events')->restrictOnDelete();
            $table->enum('type', ['init', 'group_execution', 'project_execution', 'custom', 'invalidate', 'failure']);
            $table->json('audit');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_audits');
    }
};

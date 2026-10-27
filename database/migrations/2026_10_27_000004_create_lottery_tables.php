<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Family rankings, lottery runs and their permanent audit trail.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('unit_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('rank')->comment('1 = most wanted');
            $table->timestamps();

            $table->unique(['family_id', 'unit_id']);
        });

        Schema::create('lottery_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['running', 'completed', 'failed', 'invalidated']);
            $table->string('solver');
            $table->json('options')->nullable()->comment('Confirmations given by the admin');
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->text('failure')->nullable()->comment('User-facing reason of a failed run');
            $table->foreignId('invalidated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('invalidated_at')->nullable();
            $table->text('invalidation_reason')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });

        Schema::create('lottery_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lottery_run_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['init', 'group_execution', 'project_execution', 'failure', 'invalidate', 'custom']);
            $table->json('audit');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_audits');
        Schema::dropIfExists('lottery_runs');
        Schema::dropIfExists('unit_preferences');
    }
};

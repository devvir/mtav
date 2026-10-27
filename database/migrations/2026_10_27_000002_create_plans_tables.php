<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The project plan: a canvas (one per project) and the shapes drawn on it.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('polygon')->comment('Project boundary: [[x, y], …]');
            $table->decimal('width', 10, 2);
            $table->decimal('height', 10, 2);
            $table->enum('unit_system', ['meters', 'feet'])->default('meters');
            $table->timestamps();
        });

        Schema::create('plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->string('type')->comment('unit, building, street, green, …');
            $table->integer('floor')->default(0)->comment('0 = ground');
            $table->json('polygon')->comment('[[x, y], …]');
            $table->string('name')->nullable();
            $table->json('metadata')->nullable()->comment('Colors, notes, …');
            $table->timestamps();

            $table->index(['plan_id', 'floor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_items');
        Schema::dropIfExists('plans');
    }
};

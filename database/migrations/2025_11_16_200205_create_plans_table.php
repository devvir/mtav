<?php

use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Project::class)->constrained()->cascadeOnDelete();
            $table->json('polygon')->comment('Project boundary polygon coordinates');
            $table->decimal('width', 10, 2)->comment('Canvas width in chosen unit system');
            $table->decimal('height', 10, 2)->comment('Canvas height in chosen unit system');
            $table->enum('unit_system', ['meters', 'feet'])->default('meters');
            $table->timestamps();

            $table->unique(['project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};

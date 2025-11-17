<?php

use App\Models\Project;
use App\Models\UnitType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Project::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(UnitType::class)->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('avatar')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['name', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('families');
    }
};

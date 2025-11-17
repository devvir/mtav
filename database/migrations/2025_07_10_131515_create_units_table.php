<?php

use App\Models\Family;
use App\Models\Project;
use App\Models\UnitType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Project::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(UnitType::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Family::class)->nullable()->constrained()->nullOnDelete();
            $table->string('identifier');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['identifier', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};

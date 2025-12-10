<?php

use App\Models\Family;
use App\Models\Unit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('unit_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Family::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Unit::class)->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order'); // 1-based ordering
            $table->timestamps();

            $table->unique(['family_id', 'unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_preferences');
    }
};

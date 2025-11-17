<?php

use App\Models\Plan;
use App\Models\PlanItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Plan::class)->constrained()->cascadeOnDelete();
            $table->string('type')->comment('unit, park, street, building, etc.');
            $table->json('polygon')->comment('Shape coordinates array');
            $table->integer('floor')->default(0)->comment('0=ground, 1=first, etc.');
            $table->string('name')->nullable()->comment('Optional name for the plan item');
            $table->json('metadata')->nullable()->comment('Colors, notes, measurements, etc.');
            $table->timestamps();

            $table->index(['type', 'plan_id']);
            $table->index(['floor', 'plan_id']);
        });

        Schema::table('units', function (Blueprint $table) {
            $table->foreignIdFor(PlanItem::class)->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(PlanItem::class);
        });

        Schema::dropIfExists('plan_items');
    }
};

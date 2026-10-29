<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The size a unit of each type starts at on the plan, in plan units (null: the plain
 * square every unit gets)
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('unit_types', function (Blueprint $table) {
            $table->decimal('plan_width', 7, 1)->nullable()->after('description');
            $table->decimal('plan_height', 7, 1)->nullable()->after('plan_width');
        });
    }

    public function down(): void
    {
        Schema::table('unit_types', function (Blueprint $table) {
            $table->dropColumn(['plan_width', 'plan_height']);
        });
    }
};

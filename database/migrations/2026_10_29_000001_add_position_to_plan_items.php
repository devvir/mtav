<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where each shape sits in the plan's stack (drawn in that order: brought to the front or
 * sent to the back by its admins)
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('plan_items', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0)->after('floor');
        });
    }

    public function down(): void
    {
        Schema::table('plan_items', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};

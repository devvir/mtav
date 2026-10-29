<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whether a shape stands on the plan, or (a unit added elsewhere) waits beside the
 * canvas for an admin to place it
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('plan_items', function (Blueprint $table) {
            $table->boolean('placed')->default(true)->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('plan_items', function (Blueprint $table) {
            $table->dropColumn('placed');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('active');
            $table->boolean('allows_guests')->default(false)->after('open_to_join');
            $table->json('disabled_features')->nullable()->after('allows_guests');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('allows_guests', 'disabled_features');
            $table->boolean('active')->default(true);
        });
    }
};

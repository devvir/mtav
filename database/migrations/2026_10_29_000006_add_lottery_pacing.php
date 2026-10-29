<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The platform's settings (what superadmins change in the console), and a lottery run's
 * pace: the assignment it found, held until the moment it is shown (`reveal_at`)
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value');
            $table->timestamps();
        });

        Schema::table('lottery_runs', function (Blueprint $table) {
            $table->timestamp('reveal_at')->nullable()->after('started_at');
            $table->json('result')->nullable()->after('reveal_at')->comment('The assignment found, written at reveal_at');
        });
    }

    public function down(): void
    {
        Schema::table('lottery_runs', function (Blueprint $table) {
            $table->dropColumn(['reveal_at', 'result']);
        });
        Schema::dropIfExists('platform_settings');
    }
};

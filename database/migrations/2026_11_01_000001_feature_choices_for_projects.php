<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What a project chose of the features its variant lets it choose: what it says here counts over
 * the variant's default. Not a list of what is off, since a default may be off too.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->json('feature_choices')->nullable()->after('disabled_features');
        });

        DB::table('projects')->whereNotNull('disabled_features')->get(['id', 'disabled_features'])
            ->each(fn ($project) => DB::table('projects')->where('id', $project->id)->update([
                'feature_choices' => json_encode(array_fill_keys(json_decode($project->disabled_features, true) ?: [], false)),
            ]));

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('disabled_features');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->json('disabled_features')->nullable();
            $table->dropColumn('feature_choices');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('open_lotteries', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 10)->unique();
            $table->string('name', 80);
            $table->string('items_name', 40)->comment('What is distributed, as the organizer calls it');
            $table->string('organizer_key', 64)->comment('Hash of the secret the organizer keeps');
            $table->timestamps();

            $table->index('updated_at');
        });

        Schema::create('open_lottery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('open_lottery_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->timestamps();

            $table->unique(['open_lottery_id', 'name']);
        });

        Schema::create('open_lottery_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('open_lottery_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('key', 64)->unique()->comment('Hash of the secret this person keeps');
            $table->timestamp('ranked_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->foreignId('item_id')->nullable()->comment('What the lottery gave them')
                ->constrained('open_lottery_items')->nullOnDelete();
            $table->timestamps();

            $table->index(['open_lottery_id', 'removed_at']);
        });

        Schema::create('open_lottery_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('open_lottery_participants')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('open_lottery_items')->cascadeOnDelete();
            $table->unsignedInteger('rank')->comment('1 = most wanted');

            $table->unique(['participant_id', 'item_id']);
        });

        Schema::table('lottery_runs', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->change();
            $table->foreignId('open_lottery_id')->nullable()->after('project_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lottery_runs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('open_lottery_id');
        });
        Schema::dropIfExists('open_lottery_rankings');
        Schema::dropIfExists('open_lottery_participants');
        Schema::dropIfExists('open_lottery_items');
        Schema::dropIfExists('open_lotteries');
    }
};

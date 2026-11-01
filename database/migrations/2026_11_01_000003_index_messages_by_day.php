<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Assistant's messages of the day, one person's and everyone's, found without reading
 * every message of every chat.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // The person's own index stays for the foreign key that needs one
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id', 'created_at']);
        });
    }
};

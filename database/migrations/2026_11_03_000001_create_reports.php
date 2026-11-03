<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What people tell the platform's team through the Assistant: something that does not work,
 * or an idea for the app. Read by superadmins, by person.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            // Gone with the account, the words stay, by nobody
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete()->comment('Where they were when they wrote it');
            $table->text('body');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};

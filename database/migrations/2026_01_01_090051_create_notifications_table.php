<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('target')->comment('e.g. private (User), project (Project Users), global');
            $table->unsignedBigInteger('target_id')->nullable()->comment('e.g. project or user ID');
            $table->json('data');
            $table->foreignIdFor(User::class, 'triggered_by')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['created_at']);
        });

        Schema::create('notification_read', function (Blueprint $table) {
            $table->foreignId('notification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->timestamps();

            $table->primary(['notification_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_read');
        Schema::dropIfExists('notifications');
    }
};

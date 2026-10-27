<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What people share in a project: events (with attendance) and media.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['lottery', 'online', 'onsite'])->default('onsite');
            $table->string('title');
            $table->text('description');
            $table->string('location')->nullable()->comment('Address, or a link for online events');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('allows_rsvp')->default(false);
            $table->timestamps();

            $table->index(['project_id', 'type']);
        });

        Schema::create('event_rsvps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('attending');
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
        });

        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category')->index();
            $table->string('path');
            $table->string('thumbnail')->nullable();
            $table->string('description');
            $table->string('alt_text')->nullable();
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size')->comment('bytes');
            $table->unsignedInteger('width')->nullable()->comment('pixels');
            $table->unsignedInteger('height')->nullable()->comment('pixels');
            $table->timestamps();

            $table->index(['project_id', 'category', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
        Schema::dropIfExists('event_rsvps');
        Schema::dropIfExists('events');
    }
};

<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'owner_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Project::class)->constrained()->cascadeOnDelete();
            $table->string('path')->comment('relative');
            $table->text('description');
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('width')->nullable()->comment('pixels');
            $table->unsignedInteger('height')->nullable()->comment('pixels');
            $table->string('category')->nullable()->index();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable()->comment('bytes');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};

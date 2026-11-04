<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invitations to a support account (a superadmin): to an address that has no account yet, so a
 * support account is always apart from anyone's place in a project.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('support_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('token_hash', 64)->unique()->comment('sha256 of the token, which only the link holds');
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_invitations');
    }
};

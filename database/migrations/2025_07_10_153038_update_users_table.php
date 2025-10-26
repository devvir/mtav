<?php

use App\Models\Family;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('firstname')->nullable()->after('phone');
            $table->string('lastname')->nullable()->after('firstname');
            $table->string('avatar')->nullable()->after('lastname');
            $table->foreignIdFor(Family::class)->nullable()->after('id')->constrained()->nullOnDelete();
            $table->boolean('is_admin')->default(false)->after('remember_token');
            $table->boolean('darkmode')->nullable()->after('remember_token');

            $table->dropColumn('name');

            $table->index(['firstname', 'lastname']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Family::class);
            $table->dropColumn('firstname');
            $table->dropColumn('lastname');

            $table->string('name')->after('id');
        });
    }
};

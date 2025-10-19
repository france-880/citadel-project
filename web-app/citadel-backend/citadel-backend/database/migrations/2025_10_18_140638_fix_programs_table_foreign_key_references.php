<?php

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
        // Drop the existing foreign key constraint
        Schema::table('programs', function (Blueprint $table) {
            $table->dropForeign(['program_head_id']);
        });

        // Recreate the foreign key constraint to reference accounts table
        Schema::table('programs', function (Blueprint $table) {
            $table->foreign('program_head_id')->references('id')->on('accounts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the foreign key constraint
        Schema::table('programs', function (Blueprint $table) {
            $table->dropForeign(['program_head_id']);
        });

        // Recreate the original foreign key constraint to reference users table
        Schema::table('programs', function (Blueprint $table) {
            $table->foreign('program_head_id')->references('id')->on('users')->onDelete('set null');
        });
    }
};

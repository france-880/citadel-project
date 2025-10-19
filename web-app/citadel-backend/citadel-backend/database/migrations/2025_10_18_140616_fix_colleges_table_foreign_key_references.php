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
        Schema::table('colleges', function (Blueprint $table) {
            $table->dropForeign(['college_dean_id']);
        });

        // Recreate the foreign key constraint to reference accounts table
        Schema::table('colleges', function (Blueprint $table) {
            $table->foreign('college_dean_id')->references('id')->on('accounts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the foreign key constraint
        Schema::table('colleges', function (Blueprint $table) {
            $table->dropForeign(['college_dean_id']);
        });

        // Recreate the original foreign key constraint to reference users table
        Schema::table('colleges', function (Blueprint $table) {
            $table->foreign('college_dean_id')->references('id')->on('users')->onDelete('set null');
        });
    }
};

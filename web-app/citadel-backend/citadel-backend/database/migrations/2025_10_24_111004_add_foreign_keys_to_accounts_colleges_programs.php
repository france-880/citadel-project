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
        Schema::table('colleges', function (Blueprint $table) {
            $table->foreign('dean_id')->references('id')->on('accounts')->nullOnDelete();
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->foreign('college_id')->references('id')->on('colleges')->nullOnDelete();
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->foreign('college_id')->references('id')->on('colleges')->cascadeOnDelete();
            $table->foreign('program_head_id')->references('id')->on('accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropForeign(['college_id']);
            $table->dropForeign(['program_head_id']);
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['college_id']);
        });

        Schema::table('colleges', function (Blueprint $table) {
            $table->dropForeign(['dean_id']);
        });
    }
};

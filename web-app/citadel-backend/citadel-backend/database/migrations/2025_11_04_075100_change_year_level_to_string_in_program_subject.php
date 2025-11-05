<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Changes year_level from integer to string to match section_offerings table
     * and allow values like "First Year", "Second Year", etc.
     */
    public function up(): void
    {
        Schema::table('program_subject', function (Blueprint $table) {
            // Change year_level from integer to string
            $table->string('year_level')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_subject', function (Blueprint $table) {
            // Revert to integer (note: this may cause data loss if string values exist)
            $table->integer('year_level')->nullable()->change();
        });
    }
};


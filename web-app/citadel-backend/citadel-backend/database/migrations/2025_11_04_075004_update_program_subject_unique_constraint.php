<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Updates unique constraint to allow same subject in same program
     * if it's for different semester/year_level combination.
     * Note: MySQL treats NULLs as distinct in unique constraints, so NULL values
     * will allow multiple entries with the same program_id and subject_id.
     */
    public function up(): void
    {
        Schema::table('program_subject', function (Blueprint $table) {
            // Drop old unique constraint
            $table->dropUnique(['program_id', 'subject_id']);
            
            // Add new unique constraint that includes semester and year_level
            // This allows same subject to be assigned multiple times if semester/year_level differs
            // Note: NULL values in semester/year_level are treated as distinct
            $table->unique(['program_id', 'subject_id', 'semester', 'year_level'], 'program_subject_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_subject', function (Blueprint $table) {
            // Drop new unique constraint
            $table->dropUnique('program_subject_unique');
            
            // Restore old unique constraint
            $table->unique(['program_id', 'subject_id']);
        });
    }
};


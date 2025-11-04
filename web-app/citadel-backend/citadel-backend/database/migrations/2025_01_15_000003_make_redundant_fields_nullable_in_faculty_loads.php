<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Makes redundant fields nullable so they can be derived from section_offering relationship
     * when section_offering_id is provided.
     */
    public function up(): void
    {
        // Only run if faculty_loads table exists
        if (Schema::hasTable('faculty_loads')) {
            Schema::table('faculty_loads', function (Blueprint $table) {
                // Make these nullable since they can be derived from section_offering when linked
                $table->unsignedBigInteger('subject_id')->nullable()->change();
                $table->string('subject_code')->nullable()->change();
                $table->string('subject_description')->nullable()->change();
                $table->integer('lec_hours')->nullable()->change();
                $table->integer('lab_hours')->nullable()->change();
                $table->integer('units')->nullable()->change();
                $table->string('section')->nullable()->change();
                $table->string('schedule')->nullable()->change();
                $table->string('room')->nullable()->change();
                $table->string('academic_year')->nullable()->change();
                $table->string('semester')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faculty_loads', function (Blueprint $table) {
            // Revert to required fields (may cause issues if data exists)
            $table->unsignedBigInteger('subject_id')->nullable(false)->change();
            $table->string('subject_code')->nullable(false)->change();
            $table->string('subject_description')->nullable(false)->change();
            $table->integer('lec_hours')->default(0)->nullable(false)->change();
            $table->integer('lab_hours')->default(0)->nullable(false)->change();
            $table->integer('units')->default(0)->nullable(false)->change();
            $table->string('section')->nullable(false)->change();
            $table->string('schedule')->nullable(false)->change();
            $table->string('room')->nullable(false)->change();
            $table->string('academic_year')->nullable(false)->change();
            $table->string('semester')->nullable(false)->change();
        });
    }
};


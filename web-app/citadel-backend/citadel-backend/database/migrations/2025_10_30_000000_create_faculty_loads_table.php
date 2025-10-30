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
        Schema::create('faculty_loads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('faculty_id');
            $table->unsignedBigInteger('subject_id')->nullable(); // Nullable for manual entries
            $table->string('subject_code');
            $table->string('subject_description');
            $table->integer('lec_hours')->default(0);
            $table->integer('lab_hours')->default(0);
            $table->integer('units')->default(0);
            $table->string('section');
            $table->string('schedule');
            $table->string('room');
            $table->string('type')->default('Part-time'); // Part-time or Full-time
            $table->string('academic_year'); // e.g., 2526
            $table->string('semester'); // First, Second, Summer
            $table->timestamps();

            // Foreign keys
            $table->foreign('faculty_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('subject_id')
                ->references('id')
                ->on('subjects')
                ->onDelete('set null');

            // Indexes for better query performance
            $table->index('faculty_id');
            $table->index('academic_year');
            $table->index('semester');
            $table->index(['faculty_id', 'academic_year', 'semester']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faculty_loads');
    }
};


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
        Schema::table('subjects', function (Blueprint $table) {
            $table->json('days')->nullable()->comment('Selected days (Mon, Tue, Wed, Thu, Fri, Sat)');
            $table->integer('lab_hours')->nullable()->comment('Laboratory hours');
            $table->integer('lec_hours')->nullable()->comment('Lecture hours');
            $table->integer('units')->nullable()->comment('Total units (lab + lec)');
            $table->string('time')->nullable()->comment('Time in 12-hour format');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn(['days', 'lab_hours', 'lec_hours', 'units', 'time']);
        });
    }
};

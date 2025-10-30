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
        Schema::table('section_offerings', function (Blueprint $table) {
            $table->integer('lec_hours')->nullable()->after('slots');
            $table->integer('lab_hours')->nullable()->after('lec_hours');
            $table->text('schedules')->nullable()->after('lab_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('section_offerings', function (Blueprint $table) {
            $table->dropColumn(['lec_hours', 'lab_hours', 'schedules']);
        });
    }
};

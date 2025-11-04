<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('fullname');
            $table->string('student_no')->unique();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('year_section_id')->nullable();
            $table->string('status')->default('Regular');
            $table->date('dob');
            $table->string('gender');
            $table->string('email')->unique();
            $table->string('contact');
            $table->string('address');
            $table->string('guardian_name');
            $table->string('guardian_contact');
            $table->string('guardian_email')->nullable();
            $table->string('guardian_address');
            $table->string('username')->unique();
            $table->string('password'); // hashed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
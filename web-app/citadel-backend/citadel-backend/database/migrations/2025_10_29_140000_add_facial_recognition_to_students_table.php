<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->boolean('has_facial_recognition')->default(false)->after('password');
            $table->text('facial_recognition_data')->nullable()->after('has_facial_recognition');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['has_facial_recognition', 'facial_recognition_data']);
        });
    }
};


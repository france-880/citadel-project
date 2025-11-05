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
        Schema::table('faculty_loads', function (Blueprint $table) {
            $table->unsignedBigInteger('section_offering_id')->nullable()->after('faculty_id');
            
            $table->foreign('section_offering_id')
                ->references('id')
                ->on('section_offerings')
                ->onDelete('set null');
                
            $table->index('section_offering_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faculty_loads', function (Blueprint $table) {
            $table->dropForeign(['section_offering_id']);
            $table->dropIndex(['section_offering_id']);
            $table->dropColumn('section_offering_id');
        });
    }
};


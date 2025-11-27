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
        Schema::table('user_courses', function (Blueprint $table) {
            if (!Schema::hasColumn('user_courses', 'final_exam_score')) {
                $table->decimal('final_exam_score', 5, 2)->nullable()->after('progress_percentage');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_courses', function (Blueprint $table) {
            if (Schema::hasColumn('user_courses', 'final_exam_score')) {
                $table->dropColumn('final_exam_score');
            }
        });
    }
};
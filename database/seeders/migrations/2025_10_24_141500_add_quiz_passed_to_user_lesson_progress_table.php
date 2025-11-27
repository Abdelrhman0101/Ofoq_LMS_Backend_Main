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
        Schema::table('user_lesson_progress', function (Blueprint $table) {
            if (!Schema::hasColumn('user_lesson_progress', 'quiz_passed')) {
                $table->boolean('quiz_passed')->default(false)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_lesson_progress', function (Blueprint $table) {
            if (Schema::hasColumn('user_lesson_progress', 'quiz_passed')) {
                $table->dropColumn('quiz_passed');
            }
        });
    }
};
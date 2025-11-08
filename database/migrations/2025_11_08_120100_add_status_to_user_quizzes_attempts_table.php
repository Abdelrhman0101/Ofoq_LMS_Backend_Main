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
        Schema::table('user_quizzes_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('user_quizzes_attempts', 'status')) {
                $table->string('status')->default('in_progress')->after('start_time');
            }
            $table->index(['user_id', 'quiz_id', 'status'], 'user_quiz_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_quizzes_attempts', function (Blueprint $table) {
            if (Schema::hasColumn('user_quizzes_attempts', 'status')) {
                $table->dropColumn('status');
            }
            $table->dropIndex('user_quiz_status_idx');
        });
    }
};
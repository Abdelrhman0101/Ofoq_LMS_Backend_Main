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
            //
            $table->timestamp('start_time')->nullable()->after('quiz_id');
            $table->decimal('score', 5, 2)->nullable()->change();
            $table->integer('total_questions')->nullable()->change();
            $table->integer('correct_answers')->nullable()->change();
            $table->json('answers')->nullable()->change();
            $table->integer('time_taken')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_quizzes_attempts', function (Blueprint $table) {
            //
            $table->dropColumn('start_time');
            $table->decimal('score', 5, 2)->nullable(false)->change();
            $table->integer('total_questions')->nullable(false)->change();
            $table->integer('correct_answers')->nullable(false)->change();
            $table->json('answers')->nullable(false)->change();
            $table->integer('time_taken')->nullable(false)->change();
        });
    }
};

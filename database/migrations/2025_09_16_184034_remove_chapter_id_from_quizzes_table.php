<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropForeign(['chapter_id']);
            $table->dropColumn('chapter_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ensure the column exists and is nullable before adding the foreign key
        if (!Schema::hasColumn('quizzes', 'chapter_id')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->unsignedBigInteger('chapter_id')->nullable();
            });
        } else {
            // Make existing column nullable to allow sanitization
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE `quizzes` MODIFY `chapter_id` BIGINT UNSIGNED NULL');
        }

        // Sanitize any invalid references to avoid FK violations
        DB::statement('UPDATE quizzes q LEFT JOIN chapters c ON q.chapter_id = c.id SET q.chapter_id = NULL WHERE q.chapter_id IS NOT NULL AND c.id IS NULL');

        // Now safely add the foreign key constraint
        Schema::table('quizzes', function (Blueprint $table) {
            $table->foreign('chapter_id')->references('id')->on('chapters')->onDelete('cascade');
        });
    }
};

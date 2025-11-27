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
            if (!Schema::hasColumn('quizzes', 'chapter_id')) {
                $table->unsignedBigInteger('chapter_id')->nullable()->after('id');
            }
        });

        // Sanitize invalid references
        DB::statement('UPDATE quizzes q LEFT JOIN chapters c ON q.chapter_id = c.id SET q.chapter_id = NULL WHERE q.chapter_id IS NOT NULL AND c.id IS NULL');

        Schema::table('quizzes', function (Blueprint $table) {
            // Add foreign key if not present
            $table->foreign('chapter_id')->references('id')->on('chapters')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            // Drop foreign key and column if exists
            if (Schema::hasColumn('quizzes', 'chapter_id')) {
                $table->dropForeign(['chapter_id']);
                $table->dropColumn('chapter_id');
            }
        });
    }
};
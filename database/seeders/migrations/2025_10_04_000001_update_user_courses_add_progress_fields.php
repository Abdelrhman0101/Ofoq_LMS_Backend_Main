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
            if (!Schema::hasColumn('user_courses', 'status')) {
                $table->string('status', 20)->default('in_progress')->after('course_id');
            }
            if (!Schema::hasColumn('user_courses', 'progress_percentage')) {
                $table->unsignedInteger('progress_percentage')->default(0)->after('status');
            }
            if (!Schema::hasColumn('user_courses', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('progress_percentage');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_courses', function (Blueprint $table) {
            if (Schema::hasColumn('user_courses', 'completed_at')) {
                $table->dropColumn('completed_at');
            }
            if (Schema::hasColumn('user_courses', 'progress_percentage')) {
                $table->dropColumn('progress_percentage');
            }
            if (Schema::hasColumn('user_courses', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
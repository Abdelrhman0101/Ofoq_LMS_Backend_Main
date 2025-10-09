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
        Schema::table('featured_courses', function (Blueprint $table) {
            if (!Schema::hasColumn('featured_courses', 'priority')) {
                $table->unsignedInteger('priority')->default(1)->after('course_id');
            }
            if (!Schema::hasColumn('featured_courses', 'featured_at')) {
                $table->timestamp('featured_at')->nullable()->after('priority');
            }
            if (!Schema::hasColumn('featured_courses', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('featured_at');
            }
            if (Schema::hasColumn('featured_courses', 'is_active')) {
                // keep as is
            } else {
                $table->boolean('is_active')->default(true)->after('expires_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('featured_courses', function (Blueprint $table) {
            if (Schema::hasColumn('featured_courses', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
            if (Schema::hasColumn('featured_courses', 'featured_at')) {
                $table->dropColumn('featured_at');
            }
            if (Schema::hasColumn('featured_courses', 'priority')) {
                $table->dropColumn('priority');
            }
        });
    }
};
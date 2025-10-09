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
        Schema::table('instructors', function (Blueprint $table) {
            if (!Schema::hasColumn('instructors', 'students_count')) {
                $table->unsignedBigInteger('students_count')->default(0)->after('avg_rate');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instructors', function (Blueprint $table) {
            if (Schema::hasColumn('instructors', 'students_count')) {
                $table->dropColumn('students_count');
            }
        });
    }
};

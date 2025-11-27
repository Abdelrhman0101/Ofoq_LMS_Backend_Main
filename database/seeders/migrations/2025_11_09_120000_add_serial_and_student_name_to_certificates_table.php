<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            if (!Schema::hasColumn('certificates', 'student_name')) {
                $table->string('student_name')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('certificates', 'serial_number')) {
                $table->string('serial_number')->nullable()->unique()->after('verification_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            if (Schema::hasColumn('certificates', 'serial_number')) {
                $table->dropUnique(['serial_number']);
                $table->dropColumn('serial_number');
            }
            if (Schema::hasColumn('certificates', 'student_name')) {
                $table->dropColumn('student_name');
            }
        });
    }
};
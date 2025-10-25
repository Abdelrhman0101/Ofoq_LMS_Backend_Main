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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'qualification')) {
                $table->string('qualification')->nullable()->after('nationality');
            }
            if (!Schema::hasColumn('users', 'media_work_sector')) {
                $table->string('media_work_sector')->nullable()->after('qualification');
            }
            if (!Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('media_work_sector');
            }
            if (!Schema::hasColumn('users', 'previous_field')) {
                $table->string('previous_field')->nullable()->after('date_of_birth');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'previous_field')) {
                $table->dropColumn('previous_field');
            }
            if (Schema::hasColumn('users', 'date_of_birth')) {
                $table->dropColumn('date_of_birth');
            }
            if (Schema::hasColumn('users', 'media_work_sector')) {
                $table->dropColumn('media_work_sector');
            }
            if (Schema::hasColumn('users', 'qualification')) {
                $table->dropColumn('qualification');
            }
        });
    }
};
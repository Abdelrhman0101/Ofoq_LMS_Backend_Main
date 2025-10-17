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
        Schema::table('blocked_users', function (Blueprint $table) {
            if (!Schema::hasColumn('blocked_users', 'is_blocked')) {
                $table->boolean('is_blocked')->default(true)->after('reason');
            }
        });

        // Drop old blocked_until column if exists
        if (Schema::hasColumn('blocked_users', 'blocked_until')) {
            Schema::table('blocked_users', function (Blueprint $table) {
                $table->dropColumn('blocked_until');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blocked_users', function (Blueprint $table) {
            if (Schema::hasColumn('blocked_users', 'is_blocked')) {
                $table->dropColumn('is_blocked');
            }
            if (!Schema::hasColumn('blocked_users', 'blocked_until')) {
                $table->timestamp('blocked_until')->nullable();
            }
        });
    }
};
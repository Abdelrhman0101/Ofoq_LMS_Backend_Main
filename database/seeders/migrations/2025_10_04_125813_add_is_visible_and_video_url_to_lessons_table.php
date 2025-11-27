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
        Schema::table('lessons', function (Blueprint $table) {
            // Add is_visible column if it doesn't exist
            if (!Schema::hasColumn('lessons', 'is_visible')) {
                $table->boolean('is_visible')->default(false)->after('order');
            }
            
            // Add video_url column if it doesn't exist
            if (!Schema::hasColumn('lessons', 'video_url')) {
                $table->string('video_url')->nullable()->after('is_visible');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            //
            $table->dropColumn(['is_visible', 'video_url']);
        });
    }
};

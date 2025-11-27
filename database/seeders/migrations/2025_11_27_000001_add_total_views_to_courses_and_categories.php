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
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedBigInteger('total_views')->default(0)->after('rank');
        });

        Schema::table('category_of_course', function (Blueprint $table) {
            $table->unsignedBigInteger('total_views')->default(0)->after('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('total_views');
        });

        Schema::table('category_of_course', function (Blueprint $table) {
            $table->dropColumn('total_views');
        });
    }
};

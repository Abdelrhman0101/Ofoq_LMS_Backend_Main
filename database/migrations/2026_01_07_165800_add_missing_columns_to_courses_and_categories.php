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
            if (!Schema::hasColumn('courses', 'rank')) {
                $table->integer('rank')->nullable()->after('cover_image');
            }
            if (!Schema::hasColumn('courses', 'total_views')) {
                $table->integer('total_views')->default(0)->after('rank');
            }
        });

        Schema::table('category_of_course', function (Blueprint $table) {
            if (!Schema::hasColumn('category_of_course', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('category_of_course', 'slug')) {
                $table->string('slug')->unique()->nullable()->after('description');
            }
            if (!Schema::hasColumn('category_of_course', 'is_published')) {
                $table->boolean('is_published')->default(true)->after('slug');
            }
            if (!Schema::hasColumn('category_of_course', 'is_free')) {
                $table->boolean('is_free')->default(true)->after('is_published');
            }
            if (!Schema::hasColumn('category_of_course', 'price')) {
                $table->decimal('price', 10, 2)->default(0)->after('is_free');
            }
            if (!Schema::hasColumn('category_of_course', 'cover_image')) {
                $table->string('cover_image')->nullable()->after('price');
            }
            if (!Schema::hasColumn('category_of_course', 'display_order')) {
                $table->integer('display_order')->nullable()->after('cover_image');
            }
            if (!Schema::hasColumn('category_of_course', 'total_views')) {
                $table->integer('total_views')->default(0)->after('display_order');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('category_of_course', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'slug',
                'is_published',
                'is_free',
                'price',
                'cover_image',
                'display_order',
                'total_views'
            ]);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['rank', 'total_views']);
        });
    }
};

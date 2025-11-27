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
        Schema::table('category_of_course', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->string('slug')->nullable()->unique()->after('name');
            $table->boolean('is_published')->default(true)->after('slug');
            $table->boolean('is_free')->default(true)->after('is_published');
            $table->decimal('price', 10, 2)->default(0)->after('is_free');
            $table->string('cover_image')->nullable()->after('price');
            $table->unsignedInteger('display_order')->default(0)->after('cover_image');
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
            ]);
        });
    }
};
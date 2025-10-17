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
            // Add missing columns that are in the Course model fillable but not in database
            if (!Schema::hasColumn('courses', 'duration')) {
                $table->integer('duration')->default(0)->after('description');
            }
            if (!Schema::hasColumn('courses', 'rating')) {
                $table->decimal('rating', 3, 1)->default(0)->after('duration');
            }
            if (!Schema::hasColumn('courses', 'discount_price')) {
                $table->decimal('discount_price', 8, 2)->nullable()->after('price');
            }
            if (!Schema::hasColumn('courses', 'discount_ends_at')) {
                $table->timestamp('discount_ends_at')->nullable()->after('discount_price');
            }
            if (!Schema::hasColumn('courses', 'name_instructor')) {
                $table->string('name_instructor')->nullable()->after('instructor_id');
            }
            if (!Schema::hasColumn('courses', 'bio_instructor')) {
                $table->text('bio_instructor')->nullable()->after('name_instructor');
            }
            if (!Schema::hasColumn('courses', 'image_instructor')) {
                $table->string('image_instructor')->nullable()->after('bio_instructor');
            }
            if (!Schema::hasColumn('courses', 'title_instructor')) {
                $table->string('title_instructor')->nullable()->after('image_instructor');
            }
            if (!Schema::hasColumn('courses', 'chapters_count')) {
                $table->integer('chapters_count')->default(0)->after('title_instructor');
            }
            if (!Schema::hasColumn('courses', 'students_count')) {
                $table->integer('students_count')->default(0)->after('chapters_count');
            }
            if (!Schema::hasColumn('courses', 'hours_count')) {
                $table->integer('hours_count')->default(0)->after('students_count');
            }
            if (!Schema::hasColumn('courses', 'reviews_count')) {
                $table->integer('reviews_count')->default(0)->after('hours_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $columns = [
                'duration', 'rating', 'discount_price', 'discount_ends_at',
                'name_instructor', 'bio_instructor', 'image_instructor', 
                'title_instructor', 'chapters_count', 'students_count', 
                'hours_count', 'reviews_count'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('courses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
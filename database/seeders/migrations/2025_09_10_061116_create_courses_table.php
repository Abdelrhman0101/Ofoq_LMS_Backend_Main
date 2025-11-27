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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            // $table->string('instructor_image')->nullable();
            $table->integer('duration')->default(0);
            $table->decimal('rating', 3, 1)->default(0);
            $table->decimal('discount_price', 8, 2)->nullable();
            $table->timestamp('discount_ends_at')->nullable();
            $table->string('title');
            $table->text('description');
            $table->decimal('price', 8, 2)->default(0);
            $table->boolean('is_free')->default(true);
            $table->unsignedBigInteger('instructor_id')->nullable();
            $table->string('name_instructor')->nullable();
            $table->string('bio_instructor')->nullable();
            $table->string('image_instructor')->nullable();
            $table->string('title_instructor')->nullable();
            $table->integer('chapters_count')->default(0);
            $table->integer('students_count')->default(0);
            $table->integer('hours_count')->default(0);
            $table->integer('reviews_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};

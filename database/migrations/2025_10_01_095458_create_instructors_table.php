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
        Schema::create('instructors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title');
            $table->string('email')->unique()->nullable();
            $table->string('bio')->nullable();
            $table->string('image')->nullable();
            $table->decimal('rating', 3, 1)->default(0);
            $table->integer('courses_count')->default(0);
            $table->integer('students_count')->default(0);
            $table->decimal('avg_rate', 3, 1)->default(0); // متوسط تقييم كورسات الانستراكتور
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instructors');
    }
};

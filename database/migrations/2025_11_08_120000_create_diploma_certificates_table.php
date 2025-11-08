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
        Schema::create('diploma_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // Categories are stored in table `category_of_course`
            $table->foreignId('category_id')->constrained('category_of_course')->onDelete('cascade');
            $table->foreignId('user_category_enrollment_id')->constrained()->onDelete('cascade');
            $table->uuid('verification_token')->unique();
            $table->string('file_path')->nullable();
            $table->json('certificate_data')->nullable();
            $table->timestamp('issued_at');
            $table->timestamps();

            // Ensure unique certificate per user per diploma
            $table->unique(['user_id', 'category_id']);
            $table->unique('user_category_enrollment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diploma_certificates');
    }
};
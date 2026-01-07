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
        Schema::create('sections', function (Blueprint $綱) {
            $綱->id();
            $綱->string('name');
            $綱->string('slug')->unique();
            $綱->string('icon')->nullable();
            $綱->boolean('is_published')->default(true);
            $綱->integer('display_order')->nullable();
            $綱->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};

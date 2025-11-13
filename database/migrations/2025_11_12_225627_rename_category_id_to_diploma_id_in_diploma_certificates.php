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
        Schema::table('diploma_certificates', function (Blueprint $table) {
            if (Schema::hasColumn('diploma_certificates', 'category_id')) {
                $table->renameColumn('category_id', 'diploma_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diploma_certificates', function (Blueprint $table) {
            if (Schema::hasColumn('diploma_certificates', 'diploma_id')) {
                $table->renameColumn('diploma_id', 'category_id');
            }
        });
    }
};

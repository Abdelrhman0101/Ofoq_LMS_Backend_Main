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
            // Add status enum field with default 'pending'
            if (!Schema::hasColumn('diploma_certificates', 'status')) {
                $table->enum('status', ['pending', 'completed', 'failed'])->default('pending')->after('file_path');
            }
            
            // Update issued_at to be nullable
            if (Schema::hasColumn('diploma_certificates', 'issued_at')) {
                $table->timestamp('issued_at')->nullable()->change();
            }
            
            // Skip adding unique constraint for serial_number as it already exists
            // This prevents duplicate key errors
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diploma_certificates', function (Blueprint $table) {
            if (Schema::hasColumn('diploma_certificates', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};

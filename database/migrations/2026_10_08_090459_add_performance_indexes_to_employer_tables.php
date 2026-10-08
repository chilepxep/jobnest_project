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
        Schema::table('jobs', function (Blueprint $table) {
            $table->index(['company_id', 'trang_thai'], 'idx_jobs_company_status');
            $table->index(['company_id', 'created_at'], 'idx_jobs_company_created');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->index(['job_id', 'ngay_ung_tuyen'], 'idx_applications_job_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex('idx_jobs_company_status');
            $table->dropIndex('idx_jobs_company_created');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex('idx_applications_job_date');
        });
    }
};
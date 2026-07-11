<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maternal_monitoring_records', function (Blueprint $table) {
            $table->foreignId('staff_mother_casefile_id')
                ->nullable()
                ->after('mother_id')
                ->constrained('staff_mother_casefiles')
                ->nullOnDelete();
            $table->foreignId('recorded_by_staff_id')
                ->nullable()
                ->after('staff_mother_casefile_id')
                ->constrained('program_staff')
                ->nullOnDelete();
            $table->decimal('temperature', 4, 1)->nullable()->after('hemoglobin');
            $table->unsignedSmallInteger('heart_rate')->nullable()->after('temperature');
        });
    }

    public function down(): void
    {
        Schema::table('maternal_monitoring_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('staff_mother_casefile_id');
            $table->dropConstrainedForeignId('recorded_by_staff_id');
            $table->dropColumn(['temperature', 'heart_rate']);
        });
    }
};

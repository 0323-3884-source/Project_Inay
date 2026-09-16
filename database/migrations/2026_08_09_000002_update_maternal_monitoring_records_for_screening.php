<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maternal_monitoring_records', function (Blueprint $table) {
            if (! Schema::hasColumn('maternal_monitoring_records', 'blood_sugar_test_type')) {
                $table->string('blood_sugar_test_type')->nullable()->after('blood_sugar');
            }

            if (! Schema::hasColumn('maternal_monitoring_records', 'pre_pregnancy_weight')) {
                $table->decimal('pre_pregnancy_weight', 5, 2)->nullable()->after('weight');
            }

            if (! Schema::hasColumn('maternal_monitoring_records', 'pre_pregnancy_bmi')) {
                $table->decimal('pre_pregnancy_bmi', 4, 1)->nullable()->after('pre_pregnancy_weight');
            }

            if (! Schema::hasColumn('maternal_monitoring_records', 'weight_change_from_previous')) {
                $table->decimal('weight_change_from_previous', 5, 2)->nullable()->after('pre_pregnancy_bmi');
            }

            if (! Schema::hasColumn('maternal_monitoring_records', 'bp_status')) {
                $table->string('bp_status')->nullable()->after('heart_rate');
            }

            if (! Schema::hasColumn('maternal_monitoring_records', 'blood_sugar_status')) {
                $table->string('blood_sugar_status')->nullable()->after('bp_status');
            }

            if (! Schema::hasColumn('maternal_monitoring_records', 'hemoglobin_status')) {
                $table->string('hemoglobin_status')->nullable()->after('blood_sugar_status');
            }

            if (! Schema::hasColumn('maternal_monitoring_records', 'weight_status')) {
                $table->string('weight_status')->nullable()->after('hemoglobin_status');
            }

            if (! Schema::hasColumn('maternal_monitoring_records', 'temperature_status')) {
                $table->string('temperature_status')->nullable()->after('weight_status');
            }

            if (! Schema::hasColumn('maternal_monitoring_records', 'heart_rate_status')) {
                $table->string('heart_rate_status')->nullable()->after('temperature_status');
            }

            if (! Schema::hasColumn('maternal_monitoring_records', 'screening_summary_status')) {
                $table->string('screening_summary_status')->nullable()->after('heart_rate_status');
            }

            if (! Schema::hasColumn('maternal_monitoring_records', 'measurement_units')) {
                $table->json('measurement_units')->nullable()->after('screening_summary_status');
            }

            if (! Schema::hasColumn('maternal_monitoring_records', 'screening_explanations')) {
                $table->json('screening_explanations')->nullable()->after('measurement_units');
            }

            if (! Schema::hasColumn('maternal_monitoring_records', 'screening_guidelines')) {
                $table->json('screening_guidelines')->nullable()->after('screening_explanations');
            }

            if (! Schema::hasColumn('maternal_monitoring_records', 'confirmed_unusual_at')) {
                $table->timestamp('confirmed_unusual_at')->nullable()->after('screening_guidelines');
            }

            if (! Schema::hasColumn('maternal_monitoring_records', 'confirmed_unusual_by_staff_id')) {
                $table->foreignId('confirmed_unusual_by_staff_id')->nullable()->after('confirmed_unusual_at');
            }
        });

        if (! $this->foreignKeyExists('maternal_monitoring_records', 'mmr_confirmed_unusual_staff_fk')) {
            Schema::table('maternal_monitoring_records', function (Blueprint $table) {
                $table->foreign('confirmed_unusual_by_staff_id', 'mmr_confirmed_unusual_staff_fk')
                    ->references('id')
                    ->on('program_staff')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('maternal_monitoring_records', function (Blueprint $table) {
            if ($this->foreignKeyExists('maternal_monitoring_records', 'mmr_confirmed_unusual_staff_fk')) {
                $table->dropForeign('mmr_confirmed_unusual_staff_fk');
            }

            $table->dropColumn([
                'blood_sugar_test_type',
                'pre_pregnancy_weight',
                'pre_pregnancy_bmi',
                'weight_change_from_previous',
                'bp_status',
                'blood_sugar_status',
                'hemoglobin_status',
                'weight_status',
                'temperature_status',
                'heart_rate_status',
                'screening_summary_status',
                'measurement_units',
                'screening_explanations',
                'screening_guidelines',
                'confirmed_unusual_at',
            ]);
        });
    }

    private function foreignKeyExists(string $table, string $foreignKey): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return true;
        }

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $foreignKey)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};

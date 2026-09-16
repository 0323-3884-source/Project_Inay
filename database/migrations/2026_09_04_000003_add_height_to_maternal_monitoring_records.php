<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maternal_monitoring_records', function (Blueprint $table) {
            if (! Schema::hasColumn('maternal_monitoring_records', 'height_cm')) {
                $table->decimal('height_cm', 5, 2)->nullable()->after('weight');
            }

            if (Schema::hasColumn('maternal_monitoring_records', 'pre_pregnancy_bmi')) {
                $table->decimal('pre_pregnancy_bmi', 5, 2)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('maternal_monitoring_records', function (Blueprint $table) {
            if (Schema::hasColumn('maternal_monitoring_records', 'height_cm')) {
                $table->dropColumn('height_cm');
            }

            if (Schema::hasColumn('maternal_monitoring_records', 'pre_pregnancy_bmi')) {
                $table->decimal('pre_pregnancy_bmi', 4, 1)->nullable()->change();
            }
        });
    }
};

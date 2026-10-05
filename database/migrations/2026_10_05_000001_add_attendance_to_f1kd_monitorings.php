<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('f1kd_monitorings', function (Blueprint $table) {
            $table->string('attendance_status', 20)->nullable();
            $table->string('remark_code', 40)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('f1kd_monitorings', function (Blueprint $table) {
            $table->dropColumn(['attendance_status', 'remark_code']);
        });
    }
};

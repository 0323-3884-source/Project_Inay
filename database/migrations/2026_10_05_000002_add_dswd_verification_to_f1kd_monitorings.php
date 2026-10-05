<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('f1kd_monitorings', function (Blueprint $table) {
            $table->unsignedBigInteger('recorded_by_staff_id')->nullable()->change();
            $table->foreignId('verified_by_dswd_staff_id')->nullable()->constrained('dswd_staff')->nullOnDelete();
            $table->timestamp('dswd_verified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('f1kd_monitorings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by_dswd_staff_id');
            $table->dropColumn('dswd_verified_at');
        });
        // Keep staff nullable: reverting it would reject DSWD-origin records.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inay_kaalaman_checkups', function (Blueprint $table) {
            $table->foreignId('recorded_by_staff_id')->nullable()->constrained('program_staff')->nullOnDelete();
            $table->timestamp('recorded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inay_kaalaman_checkups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recorded_by_staff_id');
            $table->dropColumn('recorded_at');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('infants', function (Blueprint $table) {
            $table->timestamp('vaccine_schedule_initialized_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('infants', function (Blueprint $table) {
            $table->dropColumn('vaccine_schedule_initialized_at');
        });
    }
};

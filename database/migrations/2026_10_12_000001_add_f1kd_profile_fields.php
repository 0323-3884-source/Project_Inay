<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('infants', function (Blueprint $table) {
            $table->string('dswd_entry_number', 30)->nullable()->unique();
        });
        Schema::table('mothers', function (Blueprint $table) {
            $table->date('last_menstrual_period')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('infants', function (Blueprint $table) {
            $table->dropUnique(['dswd_entry_number']);
            $table->dropColumn('dswd_entry_number');
        });
        Schema::table('mothers', function (Blueprint $table) {
            $table->dropColumn('last_menstrual_period');
        });
    }
};

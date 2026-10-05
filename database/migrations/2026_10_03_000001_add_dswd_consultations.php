<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('mother_id')->nullable()->change();
            $table->unsignedBigInteger('program_staff_id')->nullable()->change();
            $table->foreignId('dswd_staff_id')->nullable()->constrained('dswd_staff')->cascadeOnDelete();
            $table->unique(['dswd_staff_id', 'mother_id']);
            $table->unique(['dswd_staff_id', 'program_staff_id']);
        });
    }

    public function down(): void
    {
        // Do not silently discard conversations when rolling back this feature.
        if (\Illuminate\Support\Facades\DB::table('conversations')->whereNotNull('dswd_staff_id')->exists()) {
            throw new RuntimeException('Export and remove DSWD conversations before rolling back this migration.');
        }
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique(['dswd_staff_id', 'mother_id']);
            $table->dropUnique(['dswd_staff_id', 'program_staff_id']);
            $table->dropConstrainedForeignId('dswd_staff_id');
            $table->unsignedBigInteger('mother_id')->nullable(false)->change();
            $table->unsignedBigInteger('program_staff_id')->nullable(false)->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $createdTable = false;

        if (! Schema::hasTable('staff_availabilities')) {
            Schema::create('staff_availabilities', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('staff_id')->constrained('program_staff')->cascadeOnDelete();
                $table->unsignedTinyInteger('day_of_week');
                $table->time('start_time');
                $table->time('end_time');
                $table->string('appointment_type', 64);
                $table->string('meeting_type', 32);
                $table->string('location')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['staff_id', 'day_of_week', 'start_time'], 'staff_avail_staff_day_start_idx');
                $table->index(['day_of_week', 'appointment_type', 'is_active'], 'staff_avail_day_type_active_idx');
            });

            $createdTable = true;
        }

        if (! $createdTable) {
            Schema::table('staff_availabilities', function (Blueprint $table): void {
                $table->index(['day_of_week', 'appointment_type', 'is_active'], 'staff_avail_day_type_active_idx');
            });
        }

        if (! Schema::hasColumn('appointments', 'staff_availability_id')) {
            Schema::table('appointments', function (Blueprint $table): void {
                $table->foreignId('staff_availability_id')
                    ->nullable()
                    ->after('staff_id')
                    ->constrained('staff_availabilities')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('appointments', 'staff_availability_id')) {
            Schema::table('appointments', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('staff_availability_id');
            });
        }

        Schema::dropIfExists('staff_availabilities');
    }
};

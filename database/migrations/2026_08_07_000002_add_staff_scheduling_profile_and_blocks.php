<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_staff', function (Blueprint $table): void {
            if (! Schema::hasColumn('program_staff', 'assigned_barangay')) {
                $table->string('assigned_barangay')->nullable();
            }

            if (! Schema::hasColumn('program_staff', 'assigned_facility')) {
                $table->string('assigned_facility')->nullable();
            }

            if (! Schema::hasColumn('program_staff', 'accepting_appointments')) {
                $table->boolean('accepting_appointments')->default(true);
            }

            if (! Schema::hasColumn('program_staff', 'max_appointments_per_day')) {
                $table->unsignedSmallInteger('max_appointments_per_day')->default(8);
            }
        });

        DB::table('program_staff')
            ->whereNull('accepting_appointments')
            ->update(['accepting_appointments' => true]);

        DB::table('program_staff')
            ->whereNull('max_appointments_per_day')
            ->update(['max_appointments_per_day' => 8]);

        if (! Schema::hasTable('staff_availability_blocks')) {
            Schema::create('staff_availability_blocks', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('staff_id')->constrained('program_staff')->cascadeOnDelete();
                $table->date('blocked_date');
                $table->string('reason')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['staff_id', 'blocked_date'], 'staff_blocks_staff_date_unique');
                $table->index(['blocked_date', 'staff_id'], 'staff_blocks_date_staff_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_availability_blocks');

        foreach ([
            'max_appointments_per_day',
            'accepting_appointments',
            'assigned_facility',
            'assigned_barangay',
        ] as $column) {
            if (Schema::hasColumn('program_staff', $column)) {
                Schema::table('program_staff', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};

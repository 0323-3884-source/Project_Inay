<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('program_staff', 'role')) {
            Schema::table('program_staff', function (Blueprint $table): void {
                $table->string('role')->nullable()->after('position');
            });

            DB::table('program_staff')
                ->whereNull('role')
                ->update(['role' => DB::raw('position')]);
        }

        if (! Schema::hasColumn('program_staff', 'healthcare_worker_id_photo_path')) {
            Schema::table('program_staff', function (Blueprint $table): void {
                $table->string('healthcare_worker_id_photo_path')->nullable()->after('contact_number');
            });
        }

        if (! Schema::hasColumn('program_staff', 'healthcare_worker_id_verified_at')) {
            Schema::table('program_staff', function (Blueprint $table): void {
                $table->timestamp('healthcare_worker_id_verified_at')->nullable()->after('healthcare_worker_id_photo_path');
            });
        }

        if (! Schema::hasColumn('program_staff', 'healthcare_worker_id_verified_by_admin_id')) {
            Schema::table('program_staff', function (Blueprint $table): void {
                $table->foreignId('healthcare_worker_id_verified_by_admin_id')
                    ->nullable()
                    ->after('healthcare_worker_id_verified_at')
                    ->constrained('admin_users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('program_staff', 'healthcare_worker_id_verified_by_admin_id')) {
            Schema::table('program_staff', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('healthcare_worker_id_verified_by_admin_id');
            });
        }

        foreach ([
            'healthcare_worker_id_verified_at',
            'healthcare_worker_id_photo_path',
            'role',
        ] as $column) {
            if (Schema::hasColumn('program_staff', $column)) {
                Schema::table('program_staff', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};

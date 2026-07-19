<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('program_staff', 'approval_status')) {
            Schema::table('program_staff', function (Blueprint $table): void {
                $table->string('approval_status', 24)->default('approved')->after('healthcare_worker_id_verified_by_admin_id');
            });
        }

        if (! Schema::hasColumn('program_staff', 'approved_at')) {
            Schema::table('program_staff', function (Blueprint $table): void {
                $table->timestamp('approved_at')->nullable()->after('approval_status');
            });
        }

        if (! Schema::hasColumn('program_staff', 'approved_by_admin_id')) {
            Schema::table('program_staff', function (Blueprint $table): void {
                $table->foreignId('approved_by_admin_id')
                    ->nullable()
                    ->after('approved_at')
                    ->constrained('admin_users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('program_staff', 'rejected_at')) {
            Schema::table('program_staff', function (Blueprint $table): void {
                $table->timestamp('rejected_at')->nullable()->after('approved_by_admin_id');
            });
        }

        if (! Schema::hasColumn('program_staff', 'rejection_reason')) {
            Schema::table('program_staff', function (Blueprint $table): void {
                $table->text('rejection_reason')->nullable()->after('rejected_at');
            });
        }

        DB::table('program_staff')
            ->whereNull('approval_status')
            ->orWhere('approval_status', '')
            ->update(['approval_status' => 'approved']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('program_staff', 'approved_by_admin_id')) {
            Schema::table('program_staff', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('approved_by_admin_id');
            });
        }

        foreach ([
            'rejection_reason',
            'rejected_at',
            'approved_at',
            'approval_status',
        ] as $column) {
            if (Schema::hasColumn('program_staff', $column)) {
                Schema::table('program_staff', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};

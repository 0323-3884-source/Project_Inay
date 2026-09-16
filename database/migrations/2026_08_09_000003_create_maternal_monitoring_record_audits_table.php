<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            if (! Schema::hasTable('maternal_monitoring_record_audits')) {
                Schema::create('maternal_monitoring_record_audits', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('maternal_monitoring_record_id')
                        ->nullable()
                        ->constrained('maternal_monitoring_records')
                        ->nullOnDelete();
                    $table->foreignId('mother_id')
                        ->nullable()
                        ->constrained('mothers')
                        ->nullOnDelete();
                    $table->foreignId('staff_id')
                        ->nullable()
                        ->constrained('program_staff')
                        ->nullOnDelete();
                    $table->string('action');
                    $table->json('before_values')->nullable();
                    $table->json('after_values')->nullable();
                    $table->timestamp('created_at')->nullable();

                    $table->index(['mother_id', 'created_at']);
                });
            }

            return;
        }

        if (! Schema::hasTable('maternal_monitoring_record_audits')) {
            Schema::create('maternal_monitoring_record_audits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('maternal_monitoring_record_id')->nullable();
                $table->foreignId('mother_id')->nullable();
                $table->foreignId('staff_id')->nullable();
                $table->string('action');
                $table->json('before_values')->nullable();
                $table->json('after_values')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        Schema::table('maternal_monitoring_record_audits', function (Blueprint $table) {
            if (! $this->indexExists('maternal_monitoring_record_audits', 'mmr_audits_mother_created_idx')) {
                $table->index(['mother_id', 'created_at'], 'mmr_audits_mother_created_idx');
            }

            if (! $this->foreignKeyExists('maternal_monitoring_record_audits', 'mmr_audits_record_fk')) {
                $table->foreign('maternal_monitoring_record_id', 'mmr_audits_record_fk')
                    ->references('id')
                    ->on('maternal_monitoring_records')
                    ->nullOnDelete();
            }

            if (! $this->foreignKeyExists('maternal_monitoring_record_audits', 'mmr_audits_mother_fk')) {
                $table->foreign('mother_id', 'mmr_audits_mother_fk')
                    ->references('id')
                    ->on('mothers')
                    ->nullOnDelete();
            }

            if (! $this->foreignKeyExists('maternal_monitoring_record_audits', 'mmr_audits_staff_fk')) {
                $table->foreign('staff_id', 'mmr_audits_staff_fk')
                    ->references('id')
                    ->on('program_staff')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maternal_monitoring_record_audits');
    }

    private function indexExists(string $table, string $index): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return false;
        }

        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->exists();
    }

    private function foreignKeyExists(string $table, string $foreignKey): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return true;
        }

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $foreignKey)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};

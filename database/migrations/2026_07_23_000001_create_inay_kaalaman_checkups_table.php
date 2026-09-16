<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inay_kaalaman_checkups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mother_id')->constrained('mothers')->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->date('checkup_date');
            $table->string('healthcare_worker_name')->nullable();
            $table->string('facility_name')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('verified_by_staff_id')->nullable()->constrained('program_staff')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->timestamps();

            $table->unique(['mother_id', 'month'], 'kaalaman_checkups_unique_month');
            $table->index(['mother_id', 'month', 'verified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inay_kaalaman_checkups');
    }
};

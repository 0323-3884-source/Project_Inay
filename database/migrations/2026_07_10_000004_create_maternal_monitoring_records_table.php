<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maternal_monitoring_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mother_id')->constrained('mothers')->cascadeOnDelete();
            $table->unsignedTinyInteger('pregnancy_week')->nullable();
            $table->unsignedTinyInteger('pregnancy_month')->nullable();
            $table->unsignedSmallInteger('bp_systolic')->nullable();
            $table->unsignedSmallInteger('bp_diastolic')->nullable();
            $table->decimal('blood_sugar', 5, 1)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('hemoglobin', 4, 1)->nullable();
            $table->string('risk_level')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maternal_monitoring_records');
    }
};

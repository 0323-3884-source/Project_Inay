<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('infants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mother_id')->constrained('mothers')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('sex', 20)->default('female');
            $table->date('birth_date')->nullable();
            $table->decimal('birth_weight', 5, 2)->nullable();
            $table->decimal('birth_height', 5, 2)->nullable();
            $table->string('facility')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('infant_growth_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('infant_id')->constrained('infants')->cascadeOnDelete();
            $table->foreignId('recorded_by_staff_id')->nullable()->constrained('program_staff')->nullOnDelete();
            $table->date('measured_at');
            $table->unsignedSmallInteger('age_months')->default(0);
            $table->decimal('weight', 5, 2);
            $table->decimal('height', 5, 2);
            $table->decimal('head_circumference', 5, 2)->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['infant_id', 'measured_at']);
        });

        Schema::create('infant_vaccine_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('infant_id')->constrained('infants')->cascadeOnDelete();
            $table->foreignId('recorded_by_staff_id')->nullable()->constrained('program_staff')->nullOnDelete();
            $table->string('vaccine_group');
            $table->string('vaccine_name');
            $table->string('dose_label');
            $table->date('due_date')->nullable();
            $table->string('status', 30)->default('upcoming');
            $table->date('administered_at')->nullable();
            $table->string('facility')->nullable();
            $table->string('lot_number')->nullable();
            $table->string('vaccinator')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['infant_id', 'vaccine_name', 'dose_label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('infant_vaccine_records');
        Schema::dropIfExists('infant_growth_records');
        Schema::dropIfExists('infants');
    }
};

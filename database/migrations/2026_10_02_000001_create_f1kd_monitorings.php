<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('f1kd_monitorings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mother_id')->constrained()->cascadeOnDelete();
            $table->foreignId('infant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('subject_key', 40);
            $table->date('reporting_month');
            $table->string('classification', 20);
            $table->string('barangay')->nullable();
            $table->string('municipality_city')->nullable();
            $table->json('checklist');
            $table->string('status', 30);
            $table->foreignId('recorded_by_staff_id')->constrained('program_staff');
            $table->timestamps();
            $table->unique(['subject_key', 'reporting_month']);
            $table->index(['reporting_month', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('f1kd_monitorings');
    }
};

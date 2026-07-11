<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_mother_casefiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('program_staff')->cascadeOnDelete();
            $table->foreignId('mother_id')->constrained('mothers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['staff_id', 'mother_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_mother_casefiles');
    }
};

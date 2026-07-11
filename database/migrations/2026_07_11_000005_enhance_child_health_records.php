<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('infants', function (Blueprint $table) {
            $table->string('blood_type', 12)->nullable()->after('birth_height');
            $table->string('photo_path')->nullable()->after('blood_type');
        });

        Schema::create('child_health_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('infant_id')->constrained('infants')->cascadeOnDelete();
            $table->foreignId('created_by_staff_id')->nullable()->constrained('program_staff')->nullOnDelete();
            $table->string('alert_type', 64)->default('follow_up_needed');
            $table->string('title');
            $table->text('notes')->nullable();
            $table->string('status', 24)->default('active');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['infant_id', 'status']);
            $table->index(['created_by_staff_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_health_alerts');

        Schema::table('infants', function (Blueprint $table) {
            $table->dropColumn(['blood_type', 'photo_path']);
        });
    }
};

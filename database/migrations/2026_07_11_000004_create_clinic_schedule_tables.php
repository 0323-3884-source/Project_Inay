<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mother_id')->constrained('mothers')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('program_staff')->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained('conversations')->nullOnDelete();
            $table->string('appointment_type', 64);
            $table->string('meeting_type', 32);
            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 32)->default('pending');
            $table->string('decline_reason')->nullable();
            $table->text('reschedule_reason')->nullable();
            $table->date('preferred_date')->nullable();
            $table->time('preferred_start_time')->nullable();
            $table->unsignedBigInteger('created_by_id');
            $table->string('created_by_role', 32);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['mother_id', 'appointment_date', 'start_time']);
            $table->index(['staff_id', 'appointment_date', 'start_time']);
            $table->index(['status', 'appointment_date']);
            $table->index(['created_by_id', 'created_by_role']);
        });

        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recipient_id');
            $table->string('recipient_role', 32);
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->string('type', 64);
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['recipient_id', 'recipient_role', 'appointment_id', 'type'], 'app_notifications_once');
            $table->index(['recipient_id', 'recipient_role', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
        Schema::dropIfExists('appointments');
    }
};

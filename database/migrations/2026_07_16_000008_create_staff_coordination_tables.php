<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_coordination_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_one_id')->constrained('program_staff')->cascadeOnDelete();
            $table->foreignId('staff_two_id')->constrained('program_staff')->cascadeOnDelete();
            $table->unsignedBigInteger('last_message_id')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['staff_one_id', 'staff_two_id']);
            $table->index(['staff_two_id', 'staff_one_id']);
            $table->index('last_message_at');
        });

        Schema::create('staff_coordination_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('staff_coordination_threads')->cascadeOnDelete();
            $table->foreignId('sender_staff_id')->constrained('program_staff')->cascadeOnDelete();
            $table->foreignId('receiver_staff_id')->constrained('program_staff')->cascadeOnDelete();
            $table->text('message')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_unsent')->default(false);
            $table->timestamp('unsent_at')->nullable();
            $table->timestamps();

            $table->index(['thread_id', 'created_at']);
            $table->index(['receiver_staff_id', 'is_read']);
            $table->index('sender_staff_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_coordination_messages');
        Schema::dropIfExists('staff_coordination_threads');
    }
};

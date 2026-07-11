<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mother_id')->constrained('mothers')->cascadeOnDelete();
            $table->foreignId('program_staff_id')->constrained('program_staff')->cascadeOnDelete();
            $table->unsignedBigInteger('last_message_id')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['mother_id', 'program_staff_id']);
            $table->index('last_message_at');
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->unsignedBigInteger('sender_id');
            $table->string('sender_role', 32);
            $table->unsignedBigInteger('receiver_id');
            $table->string('receiver_role', 32);
            $table->string('message_type', 32)->default('text');
            $table->text('message')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_unsent')->default(false);
            $table->timestamp('unsent_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['receiver_id', 'receiver_role', 'is_read']);
            $table->index(['sender_id', 'sender_role']);
        });

        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->unsignedBigInteger('caller_id');
            $table->string('caller_role', 32);
            $table->unsignedBigInteger('receiver_id');
            $table->string('receiver_role', 32);
            $table->string('call_type', 16);
            $table->string('status', 16)->default('ringing');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['receiver_id', 'receiver_role', 'status']);
            $table->index(['caller_id', 'caller_role', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calls');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};

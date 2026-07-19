<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_staff_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->constrained('admin_users')->cascadeOnDelete();
            $table->foreignId('program_staff_id')->constrained('program_staff')->cascadeOnDelete();
            $table->unsignedBigInteger('last_message_id')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['admin_user_id', 'program_staff_id']);
            $table->index(['program_staff_id', 'admin_user_id']);
            $table->index('last_message_at');
        });

        Schema::create('admin_staff_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('admin_staff_threads')->cascadeOnDelete();
            $table->string('sender_role', 30);
            $table->text('message')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_unsent')->default(false);
            $table->timestamp('unsent_at')->nullable();
            $table->timestamps();

            $table->index(['thread_id', 'created_at']);
            $table->index(['thread_id', 'sender_role', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_staff_messages');
        Schema::dropIfExists('admin_staff_threads');
    }
};

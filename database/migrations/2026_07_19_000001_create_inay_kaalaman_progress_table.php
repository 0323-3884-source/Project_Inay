<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inay_kaalaman_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mother_id')->constrained('mothers')->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->string('activity_type', 32);
            $table->string('item_key');
            $table->string('item_title')->nullable();
            $table->string('status', 32)->default('not_started');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['mother_id', 'month', 'activity_type', 'item_key'], 'kaalaman_progress_unique_item');
            $table->index(['mother_id', 'month', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inay_kaalaman_progress');
    }
};

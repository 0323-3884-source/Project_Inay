<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('offline_sync_receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->uuid('operation_id');
            $table->string('payload_hash', 64);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['staff_id', 'operation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_sync_receipts');
    }
};

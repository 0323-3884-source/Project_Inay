<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_notifications', function (Blueprint $table): void {
            $table->foreignId('mother_id')
                ->nullable()
                ->after('appointment_id')
                ->constrained('mothers')
                ->nullOnDelete();

            $table->unique(['recipient_id', 'recipient_role', 'mother_id', 'type'], 'app_notifications_mother_once');
        });
    }

    public function down(): void
    {
        Schema::table('app_notifications', function (Blueprint $table): void {
            $table->dropUnique('app_notifications_mother_once');
            $table->dropConstrainedForeignId('mother_id');
        });
    }
};

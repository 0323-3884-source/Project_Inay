<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('attachment_mime')->nullable();
            $table->unsignedInteger('attachment_duration')->nullable();
        });

        Schema::table('calls', function (Blueprint $table) {
            $table->json('offer_payload')->nullable();
            $table->json('answer_payload')->nullable();
            $table->json('ice_candidates')->nullable();
            $table->index(['conversation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropIndex(['conversation_id', 'status']);
            $table->dropColumn(['offer_payload', 'answer_payload', 'ice_candidates']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['attachment_mime', 'attachment_duration']);
        });
    }
};
